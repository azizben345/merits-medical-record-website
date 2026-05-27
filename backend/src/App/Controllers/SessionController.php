<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // Helpers (as local closures)
    $normEmail = function (string $email): string {
        return strtolower(trim($email));
    };

    // (default true)
    // $initTables = 
    //     array_key_exists('init_tables', $data) 
    //     ? (bool)$data['init_tables'] : true;

    $findLatestSessionId = function (\PDO $pdo, string $staffEmail, ?string $beforeDate = null): ?int {
        $sql = "SELECT session_id
                FROM `checkup_sessions`
                WHERE staff_email = :email"
            . ($beforeDate ? " AND session_date < :beforeDate" : "")
            . " ORDER BY session_date DESC
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $params = [':email' => $staffEmail];
        if ($beforeDate) $params[':beforeDate'] = $beforeDate;
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['session_id'] : null;
    };

    $initEmptyRow = function (\PDO $pdo, string $table, string $staffEmail, int $sessionId): void {
        // Insert a blank/seed row tied to session_id + staff_email
        $sql = "INSERT INTO `{$table}` (`staff_email`, `session_id`) VALUES (:email, :sid)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $staffEmail, ':sid' => $sessionId]);
    };

    /**
     * Duplicate the latest/only row for a given session-linked table into a new session.
     * - Auto-detects the table’s auto_increment PK and unsets it
     * - Unsets created_at/updated_at if present (DB will set fresh values)
     * - Forces session_id + staff_email to target values
     */
    $duplicateRowForTable = function (\PDO $pdo, string $table, string $staffEmail, int $fromSessionId, int $toSessionId): void {
        // 0) Get table columns (to detect PK + timestamp presence)
        $colsStmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}`");
        $colsStmt->execute();
        $colDefs = $colsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // find auto_increment PK (if exists)
        $pkCol = null;
        $allColNames = [];
        foreach ($colDefs as $c) {
            $allColNames[] = $c['Field'];
            if (!empty($c['Extra']) && stripos($c['Extra'], 'auto_increment') !== false) {
                $pkCol = $c['Field'];
            }
        }
        $hasCreatedAt = in_array('created_at', $allColNames, true);
        $hasUpdatedAt = in_array('updated_at', $allColNames, true);

        // 1) Load the source row for this session; if none, seed an empty row
        $sel = $pdo->prepare("SELECT * FROM `{$table}` WHERE `staff_email` = :email AND `session_id` = :sid LIMIT 1");
        $sel->execute([':email' => $staffEmail, ':sid' => $fromSessionId]);
        $src = $sel->fetch(\PDO::FETCH_ASSOC);

        if (!$src) {
            // If no row to copy from: create an empty row
            $seed = $pdo->prepare("INSERT INTO `{$table}` (`staff_email`, `session_id`) VALUES (:email, :sid)");
            $seed->execute([':email' => $staffEmail, ':sid' => $toSessionId]);
            return;
        }

        // 2) Prepare a new row: remove PK + timestamps
        if ($pkCol && array_key_exists($pkCol, $src)) {
            unset($src[$pkCol]);
        }
        if (array_key_exists('created_at', $src)) unset($src['created_at']);
        if (array_key_exists('updated_at', $src)) unset($src['updated_at']);

        // enforce new FK values
        $src['session_id']  = $toSessionId;
        $src['staff_email'] = $staffEmail;

        // inject now() for created_at/updated_at
        $injectNow = [];
        if ($hasCreatedAt) $injectNow['created_at'] = true;
        if ($hasUpdatedAt) $injectNow['updated_at'] = true;

        // 3) Build INSERT dynamically
        $fields = array_keys($src);
        // Include created_at/updated_at if they exist but weren’t in $src
        if ($hasCreatedAt && !in_array('created_at', $fields, true)) $fields[] = 'created_at';
        if ($hasUpdatedAt && !in_array('updated_at', $fields, true)) $fields[] = 'updated_at';

        $columnsSql = '`' . implode('`,`', $fields) . '`';
        $valuesSqlParts = [];
        $params = [];

        foreach ($fields as $f) {
            if (isset($injectNow[$f])) {
                $valuesSqlParts[] = 'NOW()';
            } else {
                $valuesSqlParts[] = ':' . $f;
                // if field wasn't in src (e.g., timestamps) set null
                $params[':' . $f] = array_key_exists($f, $src) ? $src[$f] : null;
            }
        }
        $valuesSql = implode(',', $valuesSqlParts);

        $ins = $pdo->prepare("INSERT INTO `{$table}` ({$columnsSql}) VALUES ({$valuesSql})");
        $ins->execute($params);
    };

    // list of session-linked tables
    $SESSION_TABLES = [
        'medical_history',
        'lifestyle',
        'physical_exams',
        'physical_exams_2',
        'investigations',
        'investigations_lab',
    ];

    // POST /create-session  (supports duplicate_recent flag)
    // --------------------------------------------------------
    $app->post('/create-session', function (Request $request, Response $response) use (
        $SESSION_TABLES, $normEmail, $findLatestSessionId, $initEmptyRow, $duplicateRowForTable
    ) {
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode((string) $request->getBody(), true) ?? [];
        $staffEmail   = $normEmail($data['staff_email'] ?? '');
        $sessionDate  = trim($data['session_date'] ?? '');
        // $sessionType  = trim($data['session_type'] ?? 'annual');
        $sessionType  = trim($data['session_type'] ?? 'periodic');
        $status       = trim($data['status'] ?? 'draft');
        $createdBy    = trim($data['created_by']);
        $duplicate    = !empty($data['duplicate_recent']); // boolean

        $initTables = array_key_exists('init_tables', $data)
            ? (bool)$data['init_tables']
            : (strtolower($sessionType) !== 'followup');

        if (!$staffEmail || !$sessionDate || !$sessionType) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $pdo->beginTransaction();

            // 1) Create session
            $ins = $pdo->prepare("INSERT INTO `checkup_sessions`
                (`staff_email`, `session_date`, `session_type`, `status`, `created_by`)
                VALUES (:email, :dt, :typ, :st, :by)");
            $ins->execute([
                ':email' => $staffEmail,
                ':dt'    => $sessionDate,
                ':typ'   => $sessionType,
                ':st'    => $status,
                ':by'    => $createdBy,
            ]);
            $newSessionId = (int) $pdo->lastInsertId();

            // if ($duplicate) {
            //     // find previous session (< new session_date)
            //     $prevId = $findLatestSessionId($pdo, $staffEmail, $sessionDate);
            //     if ($prevId) {
            //         foreach ($SESSION_TABLES as $tbl) {
            //             $duplicateRowForTable($pdo, $tbl, $staffEmail, (int)$prevId, $newSessionId);
            //         }
            //     } else {
            //         // fallback to empty init if none to duplicate
            //         foreach ($SESSION_TABLES as $tbl) {
            //             $initEmptyRow($pdo, $tbl, $staffEmail, $newSessionId);
            //         }
            //     }
            // } else {
            //     // create empty rows for each table
            //     foreach ($SESSION_TABLES as $tbl) {
            //         $initEmptyRow($pdo, $tbl, $staffEmail, $newSessionId);
            //     }
            // }
            if ($initTables) {
                if ($duplicate) {
                    $prevId = $findLatestSessionId($pdo, $staffEmail, $sessionDate);
                    if ($prevId) {
                        foreach ($SESSION_TABLES as $tbl) {
                            $duplicateRowForTable($pdo, $tbl, $staffEmail, (int)$prevId, $newSessionId);
                        }
                    } else {
                        foreach ($SESSION_TABLES as $tbl) {
                            $initEmptyRow($pdo, $tbl, $staffEmail, $newSessionId);
                        }
                    }
                } else {
                    foreach ($SESSION_TABLES as $tbl) {
                        $initEmptyRow($pdo, $tbl, $staffEmail, $newSessionId);
                    }
                }
            }

            $pdo->commit();

            $response->getBody()->write(json_encode([
                'ok' => true,
                'session_id' => $newSessionId,
                'duplicated' => $duplicate
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // POST /sessions/duplicate-last  (explicit duplicate action)
    // body: { staff_email, session_date?, session_type? }
    // --------------------------------------------------------
    $app->post('/sessions/duplicate-last', function (Request $request, Response $response) use (
        $SESSION_TABLES, $normEmail, $findLatestSessionId, $duplicateRowForTable, $initEmptyRow
    ) {
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode((string) $request->getBody(), true) ?? [];
        $staffEmail  = $normEmail($data['staff_email'] ?? '');
        $sessionDate = trim($data['session_date'] ?? date('Y-m-d'));
        $sessionType = trim($data['session_type'] ?? 'annual');
        $status = trim($data['status'] ?? 'draft');
        $createdBy = trim($data['created_by']);

        $initTables = array_key_exists('init_tables', $data)
            ? (bool)$data['init_tables']
            : (strtolower($sessionType) !== 'followup');

        if (!$staffEmail) {
            $response->getBody()->write(json_encode(['error' => 'staff_email is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $pdo->beginTransaction();

            // Find the most recent session (before the new date, if you want strict chronology)
            $prevId = $findLatestSessionId($pdo, $staffEmail, $sessionDate);

            // Create the new session 
            $ins = $pdo->prepare("INSERT INTO `checkup_sessions`
                (`staff_email`, `session_date`, `session_type`, `status`, `created_by`)
                VALUES (:email, :dt, :typ, :st, :by)");
            $ins->execute([
                ':email' => $staffEmail,
                ':dt'    => $sessionDate,
                ':typ'   => $sessionType,
                ':st'    => $status,
                ':by'    => $createdBy
            ]);
            $newSessionId = (int) $pdo->lastInsertId();

            // if ($prevId) {
            //     // Duplicate each table from prev to new
            //     foreach ($SESSION_TABLES as $tbl) {
            //         $duplicateRowForTable($pdo, $tbl, $staffEmail, (int)$prevId, $newSessionId);
            //     }
            //     $duplicated = true;
            // } else {
            //     // No previous: initialize empty rows
            //     foreach ($SESSION_TABLES as $tbl) {
            //         $initEmptyRow($pdo, $tbl, $staffEmail, $newSessionId);
            //     }
            //     $duplicated = false;
            // }
            $duplicated = false;
            if ($initTables) {
                if ($prevId) {
                    foreach ($SESSION_TABLES as $tbl) {
                        $duplicateRowForTable($pdo, $tbl, $staffEmail, (int)$prevId, $newSessionId);
                    }
                    $duplicated = true;
                } else {
                    foreach ($SESSION_TABLES as $tbl) {
                       $initEmptyRow($pdo, $tbl, $staffEmail, $newSessionId);
                    }
                }
            }

            $pdo->commit();

            $response->getBody()->write(json_encode([
                'ok' => true,
                'session_id' => $newSessionId,
                'duplicated' => $duplicated,
                'from_session_id' => $prevId
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // to get all checkup_sessions
    $app->get('/checkup-sessions', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM checkup_sessions");
        $stmt->execute();
        $checkup_sessions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($checkup_sessions));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

    // to get checkup_session by staff_email (in staff MySession)
    $app->get('/checkup-sessions/{staff_email}', function (Request $request, Response $response, $args) {
        $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
        $db = new db();
        $pdo = $db->getPDO();

        if ($staffEmail === null || $staffEmail === false || trim($staffEmail) === '') {
            $response->getBody()->write(json_encode(['error' => 'Invalid staff email provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "
        SELECT
            cs.*, -- Select all columns from checkup_sessions
            
            -- Select PK from Joined Tables for status checks
            i.inv_id AS inv_id,
            il.ilab_id AS ilab_id,
            l.lifestyle_id AS lifestyle_id,
            mh.mh_id AS mh_id,
            pe1.pe_id AS pe1_id,
            pe2.pe2_id AS pe2_id
            
        FROM checkup_sessions cs
        LEFT JOIN investigations i ON i.session_id = cs.session_id
        LEFT JOIN investigations_lab il ON il.session_id = cs.session_id
        LEFT JOIN lifestyle l ON l.session_id = cs.session_id
        LEFT JOIN medical_history mh ON mh.session_id = cs.session_id
        LEFT JOIN physical_exams pe1 ON pe1.session_id = cs.session_id
        LEFT JOIN physical_exams_2 pe2 ON pe2.session_id = cs.session_id

        WHERE cs.staff_email = :staff_email
        ORDER BY cs.session_date DESC
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':staff_email', $staffEmail, \PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Shape the data, calculate boolean flags, and clean up the temporary ID fields
            $checkup_sessions = array_map(function($r) {
                
                // Calculate boolean flags using the null check on the primary key
                $r['has_investigations'] = $r['inv_id'] !== null;
                $r['has_ilab'] = $r['ilab_id'] !== null;
                $r['has_lifestyle'] = $r['lifestyle_id'] !== null;
                $r['has_mh'] = $r['mh_id'] !== null;
                $r['has_pe1'] = $r['pe1_id'] !== null;
                $r['has_pe2'] = $r['pe2_id'] !== null;

                // Remove the temporary ID fields (cleaner output)
                unset($r['inv_id']);
                unset($r['ilab_id']);
                unset($r['lifestyle_id']);
                unset($r['mh_id']);
                unset($r['pe1_id']);
                unset($r['pe2_id']);
                
                // Ensure session_id is cast to int if needed, bcos JOIN can sometimes change types
                if (isset($r['session_id'])) {
                    $r['session_id'] = (int)$r['session_id'];
                }

                return $r;
            }, $rows);

            $response->getBody()->write(json_encode($checkup_sessions));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add($jwtMiddleware);

    // to get session list for admin view: GET /admin/sessions (in admin ManageSession)
    $app->get('/admin/sessions', function (\Psr\Http\Message\ServerRequestInterface $request,
                                        \Psr\Http\Message\ResponseInterface $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $queryParams = $request->getQueryParams();

        $q       = isset($queryParams['q'])       ? trim($queryParams['q']) : '';
        $year    = isset($queryParams['year'])    ? trim($queryParams['year']) : '';
        $status  = isset($queryParams['status'])  ? trim($queryParams['status']) : '';
        $limit   = isset($queryParams['limit'])   ? (int)$queryParams['limit'] : 200;  // sensible default
        $offset  = isset($queryParams['offset'])  ? (int)$queryParams['offset'] : 0;

        // clamp basic pagination to avoid abuse
        if ($limit < 1)   $limit = 1;
        if ($limit > 500) $limit = 500;
        if ($offset < 0)  $offset = 0;

        // Base SQL: join sessions with staff to get name/email for table
        $sql = "
        SELECT
            cs.session_id,
            cs.staff_email,
            s.staff_name,
            -- Fetch Staff Status using alias 'u_staff'
            u_staff.deleted_at AS staff_deleted_at,
            -- Fetch Doctor Status using alias 'u_doc'
            u_doc.deleted_at AS doctor_deleted_at,
            cs.session_date,
            cs.session_type,
            cs.status,
            cs.created_by,
            cs.updated_by,
            cs.created_at,
            cs.updated_at,
            cs.assigned_doctor_email,
            u_doc.fullname AS doctor_name,
            cs.session_remarks,

            i.inv_id, -- from investigations
            il.ilab_id, -- from investigations_lab
            l.lifestyle_id, -- from lifestyle
            mh.mh_id, -- from medical_history
            pe1.pe_id, -- from physical_exams
            pe2.pe2_id -- from physical_exams_2

        FROM checkup_sessions cs
        LEFT JOIN staff s ON s.staff_email = cs.staff_email
        -- Get Staff User Info (Alias: u_staff)
        LEFT JOIN users u_staff ON u_staff.email = cs.staff_email
        -- Get Doctor User Info (Alias: u_doc)
        LEFT JOIN users u_doc ON u_doc.email = cs.assigned_doctor_email
        LEFT JOIN investigations i ON i.session_id = cs.session_id
        LEFT JOIN investigations_lab il ON il.session_id = cs.session_id
        LEFT JOIN lifestyle l ON l.session_id = cs.session_id
        LEFT JOIN medical_history mh ON mh.session_id = cs.session_id
        LEFT JOIN physical_exams pe1 ON pe1.session_id = cs.session_id
        LEFT JOIN physical_exams_2 pe2 ON pe2.session_id = cs.session_id

        WHERE 1=1
        ";

        $bind = [];

        // Search by staff_name or staff_email
        if ($q !== '') {
            $sql     .= " AND (LOWER(s.staff_name) LIKE :q OR LOWER(cs.staff_email) LIKE :q) ";
            $bind[':q'] = '%' . strtolower($q) . '%';
        }

        // Filter by whole calendar year using a half-open range (index-friendly)
        if ($year !== '' && preg_match('/^\d{4}$/', $year)) {
            $start = sprintf('%04d-01-01 00:00:00', (int)$year);
            $end   = sprintf('%04d-01-01 00:00:00', (int)$year + 1);
            $sql  .= " AND cs.session_date >= :start AND cs.session_date < :end ";
            $bind[':start'] = $start;
            $bind[':end']   = $end;
        }

        // Filter by status
        if ($status !== '') {
            // safety: only accept known values
            $allowed = ['draft','submitted','locked'];
            if (in_array($status, $allowed, true)) {
                $sql            .= " AND cs.status = :st ";
                $bind[':st']     = $status;
            }
        }

        // Order newest updated first
        $sql .= " ORDER BY cs.updated_at DESC, cs.session_date DESC ";
        // Pagination
        $sql .= " LIMIT :lim OFFSET :off ";

        $stmt = $pdo->prepare($sql);

        // bind scalars first
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        // bind limit/offset as ints
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // shape matches frontend expectations
        $payload = array_map(function($r) {

            $has_investigations = $r['inv_id'] !== null;
            $has_ilab = $r['ilab_id'] !== null;
            $has_lifestyle = $r['lifestyle_id'] !== null;
            $has_mh = $r['mh_id'] !== null;
            $has_pe1 = $r['pe_id'] !== null;
            $has_pe2 = $r['pe2_id'] !== null;

            return [
                'session_id'    => (int)$r['session_id'],
                'staff_email'   => $r['staff_email'],
                'staff_name'    => $r['staff_name'] ?? null,
                'staff_deleted_at' => $r['staff_deleted_at'] ?? null,
                'session_date'  => $r['session_date'],
                'session_type'  => $r['session_type'],
                'status'        => $r['status'],
                'created_by'    => $r['created_by'],
                'created_at'    => $r['created_at'],
                'updated_by'    => $r['updated_by'],
                'updated_at'    => $r['updated_at'],
                'assigned_doctor_email' => $r['assigned_doctor_email'],
                'doctor_name'   => $r['doctor_name'] ?? null,
                'doctor_deleted_at' => $r['doctor_deleted_at'] ?? null,
                'session_remarks' => $r['session_remarks'],

                'has_investigations' => $has_investigations,
                'has_ilab' => $has_ilab,
                'has_lifestyle' => $has_lifestyle,
                'has_mh' => $has_mh,
                'has_pe1' => $has_pe1,
                'has_pe2' => $has_pe2
            ];
        }, $rows);

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get session status cache by session_id
    $app->get('/session-header/{session_id}', function (Request $req, Response $res, $args) {
        $pdo = (new db())->getPDO();
        $sid = (int)$args['session_id'];

        $sql = "
            SELECT 
                cs.session_id,
                cs.staff_email,
                s.staff_name,
                cs.session_date,
                cs.session_type,
                cs.status,
                cs.created_by,
                cs.updated_by,
                cs.created_at,
                cs.updated_at
            FROM checkup_sessions cs
            LEFT JOIN staff s ON s.staff_email = cs.staff_email
            WHERE cs.session_id = :sid
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':sid' => $sid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) { 
            $res->getBody()->write(json_encode(['error' => 'Session not found']));
            return $res->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $res->getBody()->write(json_encode($row));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get non-session summary: GET /admin/non-session/summary?q=&page=&page_size=
    // GET /admin/non-session/summary?q=&page=&page_size=
    $app->get('/admin/non-session/summary', function ($req, $res) {
        $pdo = (new db())->getPDO();

        // --- query params
        $params   = $req->getQueryParams();
        $q        = isset($params['q']) ? trim($params['q']) : '';
        $page     = max(1, (int)($params['page'] ?? 1));
        $pageSize = max(5, min(100, (int)($params['page_size'] ?? 20)));
        $offset   = ($page - 1) * $pageSize;

        // --- simple search filter on name/email/staff_no
        $where = '';
        $bind  = [];
        if ($q !== '') {
            $where = "WHERE (LOWER(s.staff_name) LIKE :kw OR LOWER(s.staff_email) LIKE :kw OR LOWER(s.staff_no) LIKE :kw)";
            $bind[':kw'] = '%' . strtolower($q) . '%';
        }

        // --- aggregates
        // occ: keep for display (not used in completeness)
        // fam: count only rows with non-empty relative_name
        // fhd: answered iff updated_at > created_at
        $sql = "
        SELECT
            s.staff_email,
            s.staff_name,
            s.job_title,
            s.department,
            s.staff_no,
            s.nationality,
            s.date_of_birth,
            s.year_of_born,
            s.phone_no,
            s.address,
            s.personal_doctor_email,
            s.updated_at AS staff_updated_at,
            s.created_at AS staff_created_at,
            u.deleted_at AS deleted_at,

            COALESCE(oh.occ_count, 0) AS occ_count,
            oh.occ_updated_at AS occ_updated_at,

            COALESCE(fh.fam_named_count, 0) AS fam_named_count,
            fh.fam_updated_at AS fam_updated_at,

            CASE
            WHEN fhd.staff_email IS NULL THEN 0
            WHEN fhd.updated_at > fhd.created_at THEN 1
            ELSE 0
            END AS fhd_answered,
            fhd.updated_at AS fhd_updated_at,

            COALESCE(cs.session_count, 0) AS session_count

        FROM staff s
        LEFT JOIN users u 
            ON s.staff_email = u.email
        LEFT JOIN (
            SELECT staff_email,
                COUNT(*) AS occ_count,
                MAX(updated_at) AS occ_updated_at
            FROM occupational_history
            GROUP BY staff_email
        ) oh ON oh.staff_email = s.staff_email

        LEFT JOIN (
            SELECT staff_email,
                SUM(CASE WHEN TRIM(COALESCE(relative_name,'')) <> '' THEN 1 ELSE 0 END) AS fam_named_count,
                MAX(updated_at) AS fam_updated_at
            FROM family_history
            GROUP BY staff_email
        ) fh ON fh.staff_email = s.staff_email

        LEFT JOIN family_history_disease fhd
        ON fhd.staff_email = s.staff_email

        LEFT JOIN (
            SELECT staff_email,
                COUNT(*) AS session_count
            FROM checkup_sessions
            GROUP BY staff_email
        ) cs ON cs.staff_email = s.staff_email

        $where
        ORDER BY s.staff_name ASC
        LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($bind as $k => $v) $stmt->bindValue($k, $v, \PDO::PARAM_STR);
        $stmt->bindValue(':limit',  $pageSize, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // compute: staff_info_complete, family_min_ok (>=2 named), fhd_answered (already 0/1), score 0..3
        $mustHave = ['staff_name','nationality','date_of_birth','year_of_born','phone_no','address'];

        foreach ($rows as &$r) {
            // staff completeness
            $allOk = true;
            foreach ($mustHave as $f) {
                if (!isset($r[$f]) || $r[$f] === '' || $r[$f] === null) { $allOk = false; break; }
            }
            $r['staff_info_complete'] = $allOk ? 1 : 0;

            // family named >= 2
            $famNamed = (int)($r['fam_named_count'] ?? 0);
            $r['family_min_ok'] = $famNamed >= 2 ? 1 : 0;

            // score excludes occupational
            $fhdAns = (int)($r['fhd_answered'] ?? 0);
            $r['non_session_score'] = ($r['staff_info_complete'] ? 1 : 0)
                                    + ($r['family_min_ok'] ? 1 : 0)
                                    + ($fhdAns ? 1 : 0);
        }
        unset($r);

        // count logic:
        // Logic 1: Staff Info (matches your $mustHave array)
        // Checks if fields are Not Null and Not Empty string
        $sqlStaffOk = "
            (s.staff_name > '' AND s.nationality > '' AND s.date_of_birth IS NOT NULL
            AND s.year_of_born > '' AND s.phone_no > '' AND s.address > '')
        ";

        // Logic 2: Family (matches fam_named_count >= 2)
        // We check the joined count
        $sqlFamOk = "
            (COALESCE(fh_stats.cnt, 0) >= 2)
        ";

        // Logic 3: FHD (matches updated > created)
        $sqlFhdOk = "
            (fhd.updated_at > fhd.created_at)
        ";

        // We do NOT include $where here, because you wanted 'All' counts
        // to ignore the search filter.
        $statsSql = "
            SELECT
                COUNT(*) as total_staff,
                SUM(
                    CASE WHEN
                        $sqlStaffOk AND $sqlFamOk AND $sqlFhdOk
                    THEN 1 ELSE 0 END
                ) as total_complete
            FROM staff s
            -- Minimal Join for Family Count
            LEFT JOIN (
                SELECT staff_email, COUNT(*) as cnt
                FROM family_history
                WHERE TRIM(COALESCE(relative_name,'')) <> ''
                GROUP BY staff_email
            ) fh_stats ON fh_stats.staff_email = s.staff_email
            -- Minimal Join for FHD
            LEFT JOIN family_history_disease fhd ON fhd.staff_email = s.staff_email
        ";

        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(\PDO::FETCH_ASSOC);

        $total = (int)($stats['total_staff'] ?? 0);
        $comp  = (int)($stats['total_complete'] ?? 0);
        $incomp = $total - $comp;

        $res->getBody()->write(json_encode([
            'page'       => $page,
            'page_size'  => $pageSize,
            'items'      => $rows,
            'counts'    => [
                'complete'   => $comp,
                'incomplete' => $incomp
            ]
        ]));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to edit a session by session_id
    $app->put('/edit-session/{session_id}', function (Request $req, Response $res, $args) {
        // NOTE: In a real Slim application, Request/Response/db would be injected or properly used.
        // We are simulating the execution environment here.
        $db = new db(); // Assume db class exists
        $pdo = $db->getPDO();
        $id = (int)$args['session_id'];
        $data = json_decode((string)$req->getBody(), true) ?? [];

        // 1. --- Update checkup_sessions table ---
        $sql = "UPDATE checkup_sessions
                SET 
                    assigned_doctor_email = :assigned_doctor_email,
                    session_remarks = :remarks,
                    session_date = :dt,
                    session_type = :typ,
                    updated_by = :by,
                    updated_at = NOW(), -- explicit
                    status = :st
                WHERE session_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':assigned_doctor_email' => $data['assigned_doctor_email'] ?? null,
            ':remarks' => $data['session_remarks'] ?? null,
            ':dt' => $data['session_date'] ?? null,
            ':typ'=> $data['session_type'] ?? null,
            ':st' => $data['status'] ?? null,
            ':by' => $data['updated_by'] ?? null,
            ':id' => $id
        ]);

        // 2. --- Conditional Staff Update Logic ---
        $doctorEmail = $data['assigned_doctor_email'] ?? null;

        if (!empty($doctorEmail)) {
            // 2a. Find the staff_email linked to this session
            $sql_fetch_staff = "SELECT staff_email FROM checkup_sessions WHERE session_id = :id";
            $stmt_fetch = $pdo->prepare($sql_fetch_staff);
            $stmt_fetch->execute([':id' => $id]);
            $staffEmailResult = $stmt_fetch->fetch(\PDO::FETCH_ASSOC);
            $staffEmail = $staffEmailResult['staff_email'] ?? null;

            if (!empty($staffEmail)) {
                // 2b. Update the staff record
                // Use a subquery to fetch the doctor's phone number atomically
                $sql_update_staff = "
                    UPDATE staff s
                    SET 
                        s.personal_doctor_email = :doctor_email,
                        s.doctor_phone_no = (
                            SELECT d.phone_no 
                            FROM doctor d 
                            WHERE d.doctor_email = :doctor_email_sub 
                            LIMIT 1
                        )
                    WHERE s.staff_email = :staff_email
                ";

                $stmt_update_staff = $pdo->prepare($sql_update_staff);
                $stmt_update_staff->execute([
                    ':doctor_email'      => $doctorEmail,
                    ':doctor_email_sub'  => $doctorEmail, // Bind value for the subquery
                    ':staff_email'       => $staffEmail
                ]);
            }
        }

        $res->getBody()->write(json_encode(['ok'=>true]));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // to edit only status (to submitted/locked/draft)
    $app->put('/edit-status-session/{session_id}', function ($request, $response, $args) {
        $pdo = (new db())->getPDO();
        $id = (int)$args['session_id'];
        $data = json_decode((string)$request->getBody(), true);
        
        // 1. Input Validation (Security)
        $newStatus = $data['status'] ?? '';
        $allowed = ['draft', 'submitted', 'locked'];
        
        if (!in_array($newStatus, $allowed)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid status value']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 2. Update DB
        $sql = "UPDATE checkup_sessions SET status = :st WHERE session_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':st' => $newStatus, ':id' => $id]);

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);

    // to delete a session by session_id
    $app->delete('/delete-session/{session_id}', function (Request $req, Response $res, $args) {
        $db = new db(); $pdo = $db->getPDO();
        $id = (int)$args['session_id'];

        $sql = "DELETE FROM checkup_sessions WHERE session_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $res->getBody()->write(json_encode(['ok'=>true]));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);


};