<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {
    
    // to upload a report
    $app->post('/sessions/{session_id}/upload-report', function (Request $req, Response $res, $args) use ($jwtMiddleware) {
        $session_id = (int)$args['session_id'];

        // 1) Basic fetch of session to get staff_email (and permission checks)
        $pdo = (new db())->getPDO();
        $stmt = $pdo->prepare("SELECT staff_email FROM checkup_sessions WHERE session_id = :sid");
        $stmt->execute([':sid' => $session_id]);
        $session = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$session) {
            $res->getBody()->write(json_encode(['error' => 'Session not found']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $staff_email = $session['staff_email'];

        // 2) Check file
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $res->getBody()->write(json_encode(['error' => 'Missing file or upload error']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $file = $_FILES['file'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            $res->getBody()->write(json_encode(['error' => 'File too large']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(413);
        }

        // Accept only PDF
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== 'application/pdf') {
            $res->getBody()->write(json_encode(['error' => 'Only PDF allowed']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(415);
        }

        // 3) Build storage path and safe file name
        $safeStaff = str_replace(['@','.'], ['_at_','_dot_'], $staff_email);
        $dir = __DIR__ . '/../../storage/reports/' . $safeStaff . '/' . $session_id;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $title = $_POST['title'] ?? null;
        $ext = '.pdf';
        $finalName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . $ext; // e.g. 20251013_103000_ab12cd34.pdf
        $destPath = $dir . '/' . $finalName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $res->getBody()->write(json_encode(['error' => 'Failed to move file']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        // 4) Save DB record // UPSERT not working yet (doesnt have ALTER TABLE checkup_reports ADD UNIQUE KEY uq_report_session (session_id); )
        $uploader = $req->getAttribute('user_email') ?? 'unknown@system'; // set by your JWT middleware
        $stmt = $pdo->prepare("
            INSERT INTO checkup_reports (session_id, staff_email, title, file_name, file_path, file_size, mime_type, uploaded_by)
            VALUES (:sid, :staff, :title, :fname, :fpath, :fsize, :mime, :uploader)
            ON DUPLICATE KEY UPDATE
                report_id   = LAST_INSERT_ID(report_id), -- capture existing id on update
                title       = VALUES(title),
                file_name   = VALUES(file_name),
                file_path   = VALUES(file_path),
                file_size   = VALUES(file_size),
                mime_type   = VALUES(mime_type),
                uploaded_by = VALUES(uploaded_by),
                uploaded_at  = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':sid'     => $session_id,
            ':staff'   => $staff_email,
            ':title'   => $title,
            ':fname'   => $finalName,
            ':fpath'   => $destPath,
            ':fsize'   => $file['size'],
            ':mime'    => $mime,
            ':uploader'=> $uploader
        ]);

        $id = $pdo->lastInsertId();
        $res->getBody()->write(json_encode(['message' => 'Uploaded', 'report_id' => $id]));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(201);
    })->add($jwtMiddleware);

    // GET /sessions/{session_id}/reports  -> list
    $app->get('/sessions/{session_id}/reports', function (Request $req, Response $res, $args) {
        $pdo = (new db())->getPDO();
        $stmt = $pdo->prepare("SELECT report_id, session_id, staff_email, title, file_name, file_size, mime_type, uploaded_by, uploaded_at 
                            FROM checkup_reports WHERE session_id = :sid ORDER BY uploaded_at DESC");
        $stmt->execute([':sid' => (int)$args['session_id']]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $res->getBody()->write(json_encode($rows));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to download: GET /reports/{report_id}/download 
    $app->get('/reports/{report_id}/download', function (Request $req, Response $res, $args) {
        $pdo = (new db())->getPDO();
        $stmt = $pdo->prepare("SELECT file_path, file_name, mime_type FROM checkup_reports WHERE report_id = :id");
        $stmt->execute([':id' => (int)$args['report_id']]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row || !is_file($row['file_path'])) {
            $res->getBody()->write('Not found');
            return $res->withStatus(404);
        }
        $stream = new \Slim\Psr7\Stream(fopen($row['file_path'], 'rb'));
        return $res
            ->withBody($stream)
            ->withHeader('Content-Type', $row['mime_type'])
            ->withHeader('Content-Disposition', 'attachment; filename="' . $row['file_name'] . '"')
            ->withHeader('Content-Length', (string)filesize($row['file_path']))
            ->withStatus(200);
    }); // token not needed

    // DELETE /reports/{report_id}
    $app->delete('/reports/{report_id}', function (Request $req, Response $res, $args) {
        $pdo = (new db())->getPDO();
        $reportId = (int)$args['report_id'];

        // small helper: remove a directory only if it's empty
        $removeDirIfEmpty = function (string $dir): void {
            if (!is_dir($dir)) return;
            $items = array_diff(@scandir($dir) ?: [], ['.', '..']);
            if (empty($items)) @rmdir($dir);
        };

        // 1) Look up the file path first
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT file_path FROM checkup_reports WHERE report_id = :id FOR UPDATE");
        $stmt->execute([':id' => $reportId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $pdo->rollBack();
            $res->getBody()->write(json_encode(['error' => 'Report not found']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $filePath = $row['file_path'];

        // 2) Delete the DB row (cascade will handle nothing else here)
        $del = $pdo->prepare("DELETE FROM checkup_reports WHERE report_id = :id");
        $del->execute([':id' => $reportId]);
        $pdo->commit();

        // 3) Best-effort: remove the file and prune empty folders
        if ($filePath && is_file($filePath)) {
            @unlink($filePath);

            // assuming structure: storage/reports/<email_safe>/<session_id>/<file>
            $sessionDir = dirname($filePath);        // .../<email>/<session_id>
            $staffDir   = dirname($sessionDir);      // .../<email>

            $removeDirIfEmpty($sessionDir);
            $removeDirIfEmpty($staffDir);
        }

        $res->getBody()->write(json_encode(['ok' => true]));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get report data by session_id: GET /admin/checkup-reports/(session_id)
    $app->get('/admin/checkup-reports/{session_id}', function (Request $req, Response $res, $args) {
        $pdo = (new db())->getPDO();
        $stmt = $pdo->prepare("SELECT report_id, session_id, staff_email, title, file_name, file_size, mime_type, uploaded_by, uploaded_at 
                            FROM checkup_reports WHERE session_id = :sid ORDER BY uploaded_at DESC");
        $stmt->execute([':sid' => (int)$args['session_id']]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $res->getBody()->write(json_encode($rows));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get report data by staff_email: GET /staff/checkup-reports/(staff_email)
    // $app->get('/staff/checkup-reports/{staff_email}', function (Request $req, Response $res, $args) {
    //     $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
        
    //     $pdo = (new db())->getPDO();
    //     $stmt = $pdo->prepare("SELECT report_id, session_id, staff_email, title, file_name, file_size, mime_type, uploaded_by, uploaded_at 
    //                         FROM checkup_reports WHERE staff_email = :sid ORDER BY uploaded_at DESC");
    //     $stmt->execute([':sid' => $staffEmail]);
    //     $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    //     $res->getBody()->write(json_encode($rows));
    //     return $res->withHeader('Content-Type', 'application/json');
    // })->add($jwtMiddleware);

};