<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;
use PDO;

// require_once __DIR__ . '/../utils/db.php';

// // Helper: enforce admin role from JWT
// function requireAdmin(Request $req): void {
//     $jwt = $req->getAttribute('jwt') ?: [];
//     $role = $jwt['role'] ?? $jwt['data']['role'] ?? null;
//     if ($role !== 'admin') {
//         throw new \RuntimeException('FORBIDDEN_ADMIN');
//     }
// }

// Helper: parse date range + groupBy with safe whitelist
function parseAnalyticsParams(Request $req): array {
    $qp = $req->getQueryParams();

    // date filters (inclusive from, inclusive to)
    $from = $qp['from'] ?? null; // e.g. 2025-01-01
    $to   = $qp['to']   ?? null; // e.g. 2025-12-31

    // groupBy whitelist -> (expression, labelFormat)
    $gb = strtolower($qp['groupBy'] ?? 'month');
    $groupMap = [
        'day'     => ["DATE(cs.session_date)", "%Y-%m-%d"],
        'week'    => ["STR_TO_DATE(CONCAT(YEARWEEK(cs.session_date, 3),' Monday'), '%X%V %W')", "%x-W%v"], // ISO week
        'month'   => ["DATE_FORMAT(cs.session_date, '%Y-%m-01')", "%Y-%m"],
        'quarter' => ["CONCAT(YEAR(cs.session_date), '-Q', QUARTER(cs.session_date))", "%Y-Q%q"],
        'year'    => ["DATE_FORMAT(cs.session_date, '%Y-01-01')", "%Y"],
    ];
    if (!isset($groupMap[$gb])) $gb = 'month';

    // optional staff filter
    $staffEmail = null;
    if (!empty($qp['staff_email'])) {
        $staffEmail = str_replace('XYZ', '.', $qp['staff_email']);
    }

    return [
        'from' => $from,
        'to' => $to,
        'groupBy' => $gb,
        'groupExpr' => $groupMap[$gb][0],
        'labelFmt' => $groupMap[$gb][1],
        'staffEmail' => $staffEmail,
    ];
}

// Helper: add common WHERE clauses safely
function buildWhere(array $p): array {
    $where = ["cs.status IN ('submitted','locked')"];
    $binds = [];

    if (!empty($p['from'])) { $where[] = "cs.session_date >= :from"; $binds[':from'] = $p['from']; }
    if (!empty($p['to']))   { $where[] = "cs.session_date <= :to";   $binds[':to']   = $p['to']; }
    if (!empty($p['staffEmail'])) { $where[] = "cs.staff_email = :staff"; $binds[':staff'] = $p['staffEmail']; }

    return [$where, $binds];
}

return function ($app, $jwtMiddleware) {

    // GET /checkup-sessions/{staff_emailXYZ}
    $app->get('/checkup-sessions/stats/{staff_email}', function($req,$res,$args){
        $pdo = (new db())->getPDO();
        $staff = str_replace('XYZ','.',$args['staff_email']);
        $q = $pdo->prepare("
            SELECT session_id, session_date, session_type, status
            FROM checkup_sessions
            WHERE staff_email = :staff
            ORDER BY session_date DESC
        ");
        $q->execute([':staff'=>$staff]);
        $res->getBody()->write(json_encode($q->fetchAll(\PDO::FETCH_ASSOC)));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // GET /stats/vitals/{staff_emailXYZ}
    $app->get('/stats/vitals/{staff_email}', function($req,$res,$args){
        $pdo = (new db())->getPDO();
        $staff = str_replace('XYZ','.',$args['staff_email']);
        $sql = "
            SELECT cs.session_id, cs.session_date,
                pe.weight_kg, pe.height_m, pe.bmi, pe.bp_sys, pe.bp_dia, pe.pulse_bpm
            FROM checkup_sessions cs
            LEFT JOIN physical_exams pe ON pe.session_id = cs.session_id
            WHERE cs.staff_email = :staff AND cs.status IN ('submitted','locked')
            ORDER BY cs.session_date ASC";
        $st = $pdo->prepare($sql); $st->execute([':staff'=>$staff]);
        $res->getBody()->write(json_encode($st->fetchAll(\PDO::FETCH_ASSOC)));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // GET /stats/labs/{staff_emailXYZ}?fields=ldl_result,tchol_result,hdl_result
    $app->get('/stats/labs/{staff_email}', function($req,$res,$args){
        $pdo = (new db())->getPDO();
        $staff = str_replace('XYZ','.',$args['staff_email']);
        $fields = $req->getQueryParams()['fields'] ?? 'ldl_result,tchol_result,hdl_result';
        // Whitelist to prevent SQL injection:
        $allowed = ['ldl_result','tchol_result','hdl_result','tg_result','hba1c_result','fbs_result','rbs_result'];
        $cols = array_values(array_intersect(explode(',',$fields), $allowed));
        if (empty($cols)) $cols = ['ldl_result','tchol_result','hdl_result'];

        $select = "cs.session_id, cs.session_date, ".implode(',', array_map(fn($c)=>"il.$c",$cols));
        $sql = "
            SELECT $select
            FROM checkup_sessions cs
            LEFT JOIN investigations_lab il ON il.session_id = cs.session_id
            WHERE cs.staff_email = :staff AND cs.status IN ('submitted','locked')
            ORDER BY cs.session_date ASC";
        $st = $pdo->prepare($sql); $st->execute([':staff'=>$staff]);
        $res->getBody()->write(json_encode($st->fetchAll(\PDO::FETCH_ASSOC)));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // GET /stats/labs-value/{staff_emailXYZ}?fields=ua_value,tchol_value
    $app->get('/stats/labs-value/{staff_email}', function($req,$res,$args){
        $pdo = (new db())->getPDO();
        $staff = str_replace('XYZ','.',$args['staff_email']);
        $fields = $req->getQueryParams()['fields'] ?? 'ua_value,tchol_value';
        // Whitelist to prevent SQL injection:
        $allowed = ['ua_value','tchol_value'];
        $cols = array_values(array_intersect(explode(',',$fields), $allowed));
        if (empty($cols)) $cols = ['ua_value','tchol_value'];

        $select = "cs.session_id, cs.session_date, ".implode(',', array_map(fn($c)=>"il.$c",$cols));
        $sql = "
            SELECT $select
            FROM checkup_sessions cs
            LEFT JOIN investigations_lab il ON il.session_id = cs.session_id
            WHERE cs.staff_email = :staff AND cs.status IN ('submitted','locked')
            ORDER BY cs.session_date ASC";
        $st = $pdo->prepare($sql); $st->execute([':staff'=>$staff]);
        $res->getBody()->write(json_encode($st->fetchAll(\PDO::FETCH_ASSOC)));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // GET /staff/my-health-history/{staff_email}
    $app->get('/staff/my-health-history/{staff_email}', function ($request, $response, $args) {
        
        $pdo = (new db())->getPDO();
        $email = str_replace('XYZ', '.', $args['staff_email']);

        // THE UNIFIED QUERY
        // Fetches Session Info + Vitals + Labs + Computed Statuses
        // No "WHERE status IN..." filter!
        $sql = "
            SELECT 
                -- 1. Session Details
                cs.session_id,
                cs.session_date,
                cs.status, -- 'draft', 'submitted', 'locked'
                cs.session_type,

                -- 2. Vitals (Physical Exam)
                pe.weight_kg,
                pe.height_m,
                pe.bmi,
                pe.bp_sys,
                pe.bp_dia,
                pe.pulse_bpm,
                
                -- 3. Lab Values (Raw Numbers)
                il.fbs_value    AS glucose_val,
                il.tchol_value  AS chol_val,
                il.ldl_value    AS ldl_val,
                il.hdl_value    AS hdl_val,
                il.ua_value     AS uric_val,

                -- 4. Status Indicators (1/0 or Text)
                -- We use COALESCE to handle 'Draft' sessions where these might be NULL
                COALESCE(mh.diabetes, 'N') AS diabetes,
                COALESCE(inv.electrocardiograph_status, 'Not done') AS ecg_status,
                COALESCE(inv.spirometry_status, 'Not done') AS spiro_status,
                COALESCE(inv.audiometry_status, 'Not done') AS audio_status

            FROM checkup_sessions cs
            
            -- LEFT JOIN ensures we keep the session even if medical data is missing (Drafts)
            LEFT JOIN physical_exams pe ON cs.session_id = pe.session_id
            LEFT JOIN medical_history mh ON cs.session_id = mh.session_id
            LEFT JOIN investigations inv ON cs.session_id = inv.session_id
            LEFT JOIN investigations_lab il ON cs.session_id = il.session_id
            
            WHERE cs.staff_email = :email
            ORDER BY cs.session_date DESC
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add year_label helper
            foreach ($data as &$row) {
                $row['year_label'] = date('Y', strtotime($row['session_date']));
            }

            $payload = ['history' => $data];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

    })->add($jwtMiddleware);

    // GET /stats/flags/{staff_emailXYZ}
    $app->get('/stats/flags/{staff_email}', function($req,$res,$args){
        $pdo = (new db())->getPDO();
        $staff = str_replace('XYZ','.',$args['staff_email']);
        $sql = "
            SELECT cs.session_id, cs.session_date,
            -- count abnormal across investigations_lab (Normal/Abnormal/Not done)
            (
                (il.ldl_result='Abnormal') + (il.tchol_result='Abnormal') + (il.hdl_result='Abnormal') +
                (il.tg_result='Abnormal') + (il.fbs_result='Abnormal') + (il.rbs_result='Abnormal') +
                (il.na_result='Abnormal') + (il.k_result='Abnormal') + (il.cl_result='Abnormal') +
                (il.ast_result='Abnormal') + (il.alt_result='Abnormal') + (il.ggT_result='Abnormal')
            ) AS abnormal_lab_count,

            -- physical_exams_2 abnormal counts
            (
                (pe2.head='Abnormal') + (pe2.eyes='Abnormal') + (pe2.ears_and_drums='Abnormal') +
                (pe2.hearing='Abnormal') + (pe2.nose_and_sinuses='Abnormal') + (pe2.mouth_teeth_throat='Abnormal') +
                (pe2.neck_and_thyroid='Abnormal') + (pe2.chest_and_lungs='Abnormal') + (pe2.breasts='Abnormal') +
                (pe2.heart='Abnormal') + (pe2.peripheral_arteries='Abnormal') + (pe2.peripheral_veins='Abnormal') +
                (pe2.abdomen='Abnormal') + (pe2.hernia_orifices='Abnormal') + (pe2.genitalia='Abnormal') +
                (pe2.rectal_examination='Abnormal') + (pe2.upper_limbs='Abnormal') + (pe2.lower_limbs='Abnormal') +
                (pe2.spine='Abnormal') + (pe2.skin='Abnormal') + (pe2.lymph_nodes='Abnormal') +
                (pe2.neurological='Abnormal') + (pe2.psychiatric='Abnormal')
            ) AS abnormal_exam_count
            FROM checkup_sessions cs
            LEFT JOIN investigations_lab il ON il.session_id = cs.session_id
            LEFT JOIN physical_exams_2 pe2 ON pe2.session_id = cs.session_id
            WHERE cs.staff_email = :staff AND cs.status IN ('submitted','locked')
            ORDER BY cs.session_date ASC";
        $st = $pdo->prepare($sql); $st->execute([':staff'=>$staff]);
        $res->getBody()->write(json_encode($st->fetchAll(\PDO::FETCH_ASSOC)));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // to get admin view of statistics:

    // to get charts for admin view:
    // =================================================================================
    // ROUTE 1: HISTORICAL TRENDS (Risk Prevalence %)
    // Returns the % of staff at risk per year (e.g., "In 2023, 15% were Obese")
    // =================================================================================
    // GET /admin/stats/trends-risk
    $app->get('/admin/stats/trends-risk', function (Request $request, Response $response) {
        $db = new \App\db();
        $pdo = $db->getPDO();
        $params = $request->getQueryParams();
        
        // NEW: Accept explicit start/end years
        $currentYear = (int)date("Y");
        $startYear = isset($params['start_year']) ? (int)$params['start_year'] : ($currentYear - 5);
        $endYear   = isset($params['end_year'])   ? (int)$params['end_year']   : $currentYear;

        $sql = "
            WITH yearly_ranked AS (
                SELECT 
                    YEAR(cs.session_date) as yr,
                    pe.bmi,
                    pe.bp_sys, pe.bp_dia, pe.pulse_bpm,
                    ROW_NUMBER() OVER (PARTITION BY YEAR(cs.session_date), cs.staff_email ORDER BY cs.session_date DESC) as rn
                FROM checkup_sessions cs
                LEFT JOIN physical_exams pe ON cs.session_id = pe.session_id
                WHERE cs.status IN ('submitted', 'locked')
                -- NEW: Explicit Range Logic
                AND YEAR(cs.session_date) >= :startYear 
                AND YEAR(cs.session_date) <= :endYear
            )
            SELECT 
                yr as year_label,
                COUNT(*) as total_staff,
                
                -- RISK CALCULATIONS
                ROUND(SUM(CASE WHEN bmi >= 25 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as risk_bmi,
                ROUND(SUM(CASE WHEN (bp_sys > 140 OR bp_dia > 90) THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as risk_bp,
                ROUND(SUM(CASE WHEN pulse_bpm > 100 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as risk_pulse,

                -- BMI COUNTS 
                SUM(CASE WHEN bmi < 18.5 THEN 1 ELSE 0 END) as count_under,
                SUM(CASE WHEN bmi >= 18.5 AND bmi <= 24.9 THEN 1 ELSE 0 END) as count_normal,
                SUM(CASE WHEN bmi >= 25 THEN 1 ELSE 0 END) as count_over,
                -- Count the Missing Ones explicitly
                SUM(CASE WHEN bmi IS NULL OR bmi = 0 THEN 1 ELSE 0 END) as count_unknown

            FROM yearly_ranked
            WHERE rn = 1
            GROUP BY yr
            ORDER BY yr ASC
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['startYear' => $startYear, 'endYear' => $endYear]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode(['series' => $data]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // =================================================================================
    // ROUTE: YEARLY SNAPSHOT (Labs + General + BMI)
    // =================================================================================
    $app->get('/admin/stats/snapshot-groups', function (Request $request, Response $response) {
        $db = new \App\db();
        $pdo = $db->getPDO();
        $params = $request->getQueryParams();
        $year = isset($params['year']) ? (int)$params['year'] : date("Y");

        $sql = "
            WITH latest_snapshot AS (
                SELECT 
                    cs.session_id,
                    
                    -- LABS (Text Results)
                    il.tchol_result, il.ldl_result, il.hdl_result, il.tg_result,
                    il.fbs_result, il.rbs_result,
                    il.alt_result, il.ast_result, il.ggt_result, il.tbil_result,
                    il.creat_result, il.ua_result, il.bu_result,

                    -- GENERAL INVESTIGATIONS (Correct column names)
                    ig.electrocardiograph_status, 
                    ig.spirometry_status, 
                    ig.audiometry_status, 
                    ig.chest_xray_status,

                    -- PHYSICAL (Calculate BMI Class on the fly)
                    pe.bmi,
                    CASE 
                        WHEN pe.bmi < 18.5 THEN 'Underweight'
                        WHEN pe.bmi >= 18.5 AND pe.bmi <= 24.9 THEN 'Normal'
                        WHEN pe.bmi >= 25.0 THEN 'Overweight' -- Includes Obese for simplicity
                        ELSE 'Unknown'
                    END as calculated_bmi_class,
                    
                    ROW_NUMBER() OVER (PARTITION BY cs.staff_email ORDER BY cs.session_date DESC) as rn
                FROM checkup_sessions cs
                LEFT JOIN investigations_lab il ON cs.session_id = il.session_id
                LEFT JOIN investigations ig ON cs.session_id = ig.session_id
                LEFT JOIN physical_exams pe ON cs.session_id = pe.session_id
                
                WHERE cs.status = 'Submitted' 
                AND YEAR(cs.session_date) = :year
            )
            SELECT 
                -- 1. CHOLESTEROL
                COUNT(CASE WHEN tchol_result IS NOT NULL OR ldl_result IS NOT NULL THEN 1 END) as chol_checked,
                COUNT(CASE WHEN (tchol_result = 'Abnormal' OR ldl_result = 'Abnormal' OR hdl_result = 'Abnormal' OR tg_result = 'Abnormal') THEN 1 END) as chol_abnormal_unique,
                COUNT(CASE WHEN tchol_result = 'Abnormal' THEN 1 END) as count_high_tchol,
                COUNT(CASE WHEN ldl_result = 'Abnormal' THEN 1 END) as count_high_ldl,
                COUNT(CASE WHEN hdl_result = 'Abnormal' THEN 1 END) as count_low_hdl,
                COUNT(CASE WHEN tg_result = 'Abnormal' THEN 1 END) as count_high_tg,

                -- 2. GLUCOSE
                COUNT(CASE WHEN fbs_result IS NOT NULL OR rbs_result IS NOT NULL THEN 1 END) as glucose_checked,
                COUNT(CASE WHEN (fbs_result = 'Abnormal' OR rbs_result = 'Abnormal') THEN 1 END) as glucose_abnormal_unique,
                COUNT(CASE WHEN fbs_result = 'Abnormal' THEN 1 END) as count_high_fbs,
                COUNT(CASE WHEN rbs_result = 'Abnormal' THEN 1 END) as count_high_rbs,

                -- 3. LIVER
                COUNT(CASE WHEN alt_result IS NOT NULL OR ast_result IS NOT NULL THEN 1 END) as liver_checked,
                COUNT(CASE WHEN (alt_result = 'Abnormal' OR ast_result = 'Abnormal' OR ggt_result = 'Abnormal' OR tbil_result = 'Abnormal') THEN 1 END) as liver_abnormal_unique,
                COUNT(CASE WHEN alt_result = 'Abnormal' THEN 1 END) as count_high_alt,
                COUNT(CASE WHEN ast_result = 'Abnormal' THEN 1 END) as count_high_ast,
                COUNT(CASE WHEN ggt_result = 'Abnormal' THEN 1 END) as count_high_ggt,
                COUNT(CASE WHEN tbil_result = 'Abnormal' THEN 1 END) as count_high_tbil,

                -- 4. RENAL
                COUNT(CASE WHEN creat_result IS NOT NULL OR bu_result IS NOT NULL THEN 1 END) as renal_checked,
                COUNT(CASE WHEN (creat_result = 'Abnormal' OR bu_result = 'Abnormal' OR ua_result = 'Abnormal') THEN 1 END) as renal_abnormal_unique,
                COUNT(CASE WHEN creat_result = 'Abnormal' THEN 1 END) as count_high_creat,
                COUNT(CASE WHEN bu_result = 'Abnormal' THEN 1 END) as count_high_urea,
                COUNT(CASE WHEN ua_result = 'Abnormal' THEN 1 END) as count_high_ua,

                -- 5. GENERAL INVESTIGATIONS (Updated Column Names)
                COUNT(CASE WHEN electrocardiograph_status IS NOT NULL OR spirometry_status IS NOT NULL THEN 1 END) as general_checked,
                COUNT(CASE WHEN (electrocardiograph_status = 'Abnormal' OR spirometry_status = 'Abnormal' OR audiometry_status = 'Abnormal' OR chest_xray_status = 'Abnormal') THEN 1 END) as general_abnormal_unique,
                COUNT(CASE WHEN electrocardiograph_status = 'Abnormal' THEN 1 END) as count_abnormal_ecg,
                COUNT(CASE WHEN spirometry_status = 'Abnormal' THEN 1 END) as count_abnormal_spiro,
                COUNT(CASE WHEN audiometry_status = 'Abnormal' THEN 1 END) as count_abnormal_audio,
                COUNT(CASE WHEN chest_xray_status = 'Abnormal' THEN 1 END) as count_abnormal_cxr,

                -- 6. BMI BREAKDOWN (Using the calculated class)
                COUNT(CASE WHEN bmi IS NOT NULL THEN 1 END) as bmi_checked,
                COUNT(CASE WHEN calculated_bmi_class = 'Underweight' THEN 1 END) as count_bmi_under,
                COUNT(CASE WHEN calculated_bmi_class = 'Normal' THEN 1 END) as count_bmi_normal,
                COUNT(CASE WHEN calculated_bmi_class = 'Overweight' THEN 1 END) as count_bmi_over

            FROM latest_snapshot
            WHERE rn = 1
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['year' => $year]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Just copy-paste the same PHP JSON formatting logic from before
            $groups = [
                'cholesterol' => [
                    'title' => 'Cholesterol Profile',
                    'checked' => $row['chol_checked'],
                    'abnormal_unique' => $row['chol_abnormal_unique'],
                    'details' => [
                        ['label' => 'Total Chol', 'count' => $row['count_high_tchol']],
                        ['label' => 'LDL', 'count' => $row['count_high_ldl']],
                        ['label' => 'HDL', 'count' => $row['count_low_hdl']],
                        ['label' => 'Triglycerides', 'count' => $row['count_high_tg']]
                    ]
                ],
                'glucose' => [
                    'title' => 'Glucose Profile',
                    'checked' => $row['glucose_checked'],
                    'abnormal_unique' => $row['glucose_abnormal_unique'],
                    'details' => [
                        ['label' => 'Fasting (FBS)', 'count' => $row['count_high_fbs']],
                        ['label' => 'Random (RBS)', 'count' => $row['count_high_rbs']]
                    ]
                ],
                'liver' => [
                    'title' => 'Liver Function',
                    'checked' => $row['liver_checked'],
                    'abnormal_unique' => $row['liver_abnormal_unique'],
                    'details' => [
                        ['label' => 'ALT', 'count' => $row['count_high_alt']],
                        ['label' => 'AST', 'count' => $row['count_high_ast']],
                        ['label' => 'GGT', 'count' => $row['count_high_ggt']],
                        ['label' => 'Bilirubin', 'count' => $row['count_high_tbil']]
                    ]
                ],
                'renal' => [
                    'title' => 'Renal Function',
                    'checked' => $row['renal_checked'],
                    'abnormal_unique' => $row['renal_abnormal_unique'],
                    'details' => [
                        ['label' => 'Creatinine', 'count' => $row['count_high_creat']],
                        ['label' => 'Urea', 'count' => $row['count_high_urea']],
                        ['label' => 'Uric Acid', 'count' => $row['count_high_ua']]
                    ]
                ],
                // NEW: GENERAL INVESTIGATIONS
                'general' => [
                    'title' => 'General Investigations',
                    'checked' => $row['general_checked'],
                    'abnormal_unique' => $row['general_abnormal_unique'],
                    'details' => [
                        ['label' => 'ECG', 'count' => $row['count_abnormal_ecg']],
                        ['label' => 'Spirometry', 'count' => $row['count_abnormal_spiro']],
                        ['label' => 'Audiometry', 'count' => $row['count_abnormal_audio']],
                        ['label' => 'Chest X-Ray', 'count' => $row['count_abnormal_cxr']]
                    ]
                ],
                // NEW: BMI BREAKDOWN
                'bmi' => [
                    'title' => 'BMI Categories',
                    'checked' => $row['bmi_checked'],
                    // Abnormal = Underweight + Overweight
                    'abnormal_unique' => (int)$row['count_bmi_over'] + (int)$row['count_bmi_under'], 
                    'details' => [
                        ['label' => 'Underweight (<18.5)', 'count' => $row['count_bmi_under']],
                        ['label' => 'Normal (18.5-24.9)', 'count' => $row['count_bmi_normal']],
                        ['label' => 'Overweight/Obese (≥25)', 'count' => $row['count_bmi_over']]
                    ]
                ]
            ];

            $response->getBody()->write(json_encode(['groups' => $groups]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // GET /admin/stats/bmi-distribution - for scatter plot
    $app->get('/admin/stats/bmi-distribution', function (Request $request, Response $response) {
        $db = new \App\db();
        $pdo = $db->getPDO();
        $year = $request->getQueryParams()['year'] ?? date("Y");

        $sql = "
            WITH latest_bmi AS (
                SELECT 
                    cs.staff_email,
                    pe.bmi,
                    -- Rank by date to get the latest session for this specific year
                    ROW_NUMBER() OVER (PARTITION BY cs.staff_email ORDER BY cs.session_date DESC) as rn
                FROM checkup_sessions cs
                JOIN physical_exams pe ON cs.session_id = pe.session_id
                WHERE cs.status = 'Submitted' 
                AND YEAR(cs.session_date) = :year
                AND pe.bmi IS NOT NULL AND pe.bmi > 0 -- Exclude nulls/zeros
            )
            SELECT staff_email, bmi
            FROM latest_bmi
            WHERE rn = 1
            ORDER BY bmi ASC -- Sorting helps the chart look like a smooth curve
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['year' => $year]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $response->getBody()->write(json_encode(['points' => $data]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    });
    // end get charts for admin view

    /**
     * GET /admin/stats/summary
     * High-level KPIs + small tables for dashboard cards:
     * - total_sessions
     * - unique_staff_seen
     * - sessions_by_status
     * - sessions_by_period (grouped by groupBy)
     * - top_staff_by_sessions (limit=5)
     */

    // GET /admin/stats/available-years to validate if the current year is a valid year to fetch data
    $app->get('/admin/stats/available-years', function (Request $req, Response $res) {
        $pdo = (new db())->getPDO();
        
        // config: (at least) how many sessions constitute a "Valid Data Collection"?
        $minSessions = 1; 

        $sql = "
            SELECT 
                YEAR(session_date) as year,
                COUNT(*) as count
            FROM checkup_sessions
            WHERE status IN ('submitted', 'locked')
            GROUP BY YEAR(session_date)
            HAVING count >= :min
            ORDER BY year DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['min' => $minSessions]);
        $years = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $res->getBody()->write(json_encode(['years' => $years]));
        return $res->withHeader('Content-Type', 'application/json');
    });//->add($jwtMiddleware);

    $app->get('/admin/stats/summary', function(Request $req, Response $res) {
        // try { requireAdmin($req); } catch (\RuntimeException $e) {
        //     return $res->withStatus(403)->withHeader('Content-Type','application/json')
        //         ->write(json_encode(['error'=>'forbidden','message'=>'Admin only']));
        // }

        $pdo = (new db())->getPDO();
        $p = parseAnalyticsParams($req);
        [$where, $binds] = buildWhere($p);
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // Totals
        $sqlTotals = "
            SELECT
              COUNT(*) AS total_sessions,
              COUNT(DISTINCT cs.staff_email) AS unique_staff_seen
            FROM checkup_sessions cs
            $whereSql
        ";
        $st = $pdo->prepare($sqlTotals); $st->execute($binds);
        $totals = $st->fetch(\PDO::FETCH_ASSOC) ?: ['total_sessions'=>0,'unique_staff_seen'=>0];

        // Sessions by status
        $sqlByStatus = "
            SELECT cs.status, COUNT(*) AS count
            FROM checkup_sessions cs
            $whereSql
            GROUP BY cs.status
        ";
        $st = $pdo->prepare($sqlByStatus); $st->execute($binds);
        $byStatus = $st->fetchAll(\PDO::FETCH_ASSOC);

        // Sessions by period
        $sqlByPeriod = "
            SELECT {$p['groupExpr']} AS bucket, COUNT(*) AS count
            FROM checkup_sessions cs
            $whereSql
            GROUP BY bucket
            ORDER BY bucket ASC
        ";
        $st = $pdo->prepare($sqlByPeriod); $st->execute($binds);
        $byPeriod = $st->fetchAll(\PDO::FETCH_ASSOC);

        // Top staff (limit 5)
        // $limit = 5; // 'LIMIT $limit' to set limit
        $sqlTopStaff = "
            SELECT cs.staff_email, COUNT(*) AS sessions
            FROM checkup_sessions cs
            $whereSql
            GROUP BY cs.staff_email
            ORDER BY sessions DESC
        ";
        $st = $pdo->prepare($sqlTopStaff); $st->execute($binds);
        $topStaff = $st->fetchAll(\PDO::FETCH_ASSOC);

        $payload = [
            'params' => [
                'from'=>$p['from'],'to'=>$p['to'],
                'groupBy'=>$p['groupBy'],
                'staff_email'=>$p['staffEmail'] ?? null
            ],
            'totals' => $totals,
            'sessions_by_status' => $byStatus,
            'sessions_by_period' => array_map(function($r){
                // normalize bucket string for the frontend
                $b = $r['bucket'];
                if ($b instanceof \DateTime) $b = $b->format('Y-m-d');
                return ['bucket'=>$b, 'count'=>(int)$r['count']];
            }, $byPeriod),
            'top_staff_by_sessions' => $topStaff
        ];

        $res->getBody()->write(json_encode($payload));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    /**
     * GET /admin/stats/vitals
     * Average vitals across sessions, grouped by period.
     * Query: fields=weight_kg,height_m,bmi,bp_sys,bp_dia,pulse_bpm (whitelist)
     */
    // to get vitals stats averaged, grouped by year
    $app->get('/admin/stats/vitals', function(Request $req, Response $res) {
        $pdo = (new db())->getPDO();
        $p = parseAnalyticsParams($req);
        // Standardize 'from' date logic for History Range
        $fromDate = $req->getQueryParams()['from'] ?? '2000-01-01';
        
        // [FIX] Group by YEAR instead of Month
        $sql = "
            SELECT 
                YEAR(cs.session_date) as bucket, 
                AVG(pe.bmi) as avg_bmi,
                AVG(pe.bp_sys) as avg_bp_sys,
                AVG(pe.bp_dia) as avg_bp_dia,
                AVG(pe.pulse_bpm) as avg_pulse_bpm
            FROM checkup_sessions cs
            LEFT JOIN physical_exams pe ON pe.session_id = cs.session_id
            WHERE cs.session_date >= :from 
            AND cs.status IN ('submitted', 'locked')
            GROUP BY bucket
            ORDER BY bucket ASC
        ";
        
        $st = $pdo->prepare($sql);
        $st->execute(['from' => $fromDate]);
        $res->getBody()->write(json_encode(['series'=>$st->fetchAll(\PDO::FETCH_ASSOC)]));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    /**
     * GET /admin/stats/labs
     * Counts of Normal/Abnormal/Not done by test, grouped by period.
     * Query: fields=ldl_result,tchol_result,hdl_result,... (whitelist)
     */
    $app->get('/admin/stats/labs', function(Request $req, Response $res) {
        // try { requireAdmin($req); } catch (\RuntimeException $e) {
        //     return $res->withStatus(403)->withHeader('Content-Type','application/json')
        //         ->write(json_encode(['error'=>'forbidden','message'=>'Admin only']));
        // }

        $pdo = (new db())->getPDO();
        $p = parseAnalyticsParams($req);
        [$where, $binds] = buildWhere($p);
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $qp = $req->getQueryParams();
        $requested = isset($qp['fields']) ? explode(',', $qp['fields']) : ['ldl_result','tchol_result','hdl_result'];
        $allowed = ['ldl_result','tchol_result','hdl_result','tg_result','hba1c_result','fbs_result','rbs_result','na_result','k_result','cl_result','ast_result','alt_result','ggT_result'];
        $cols = array_values(array_intersect($requested, $allowed));
        if (!$cols) $cols = ['ldl_result','tchol_result','hdl_result'];

        // Build SUM(...) for each category per test
        $parts = [];
        foreach ($cols as $c) {
            $parts[] = "SUM(il.$c = 'Normal')   AS {$c}_normal";
            $parts[] = "SUM(il.$c = 'Abnormal') AS {$c}_abnormal";
            $parts[] = "SUM(COALESCE(il.$c,'') = '' OR il.$c = 'Not done') AS {$c}_not_done";
        }
        $aggSql = implode(",\n                   ", $parts);

        $sql = "
            SELECT {$p['groupExpr']} AS bucket,
                   COUNT(*) AS n,
                   $aggSql
            FROM checkup_sessions cs
            LEFT JOIN investigations_lab il ON il.session_id = cs.session_id
            $whereSql
            GROUP BY bucket
            ORDER BY bucket ASC
        ";
        $st = $pdo->prepare($sql); $st->execute($binds);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

        $res->getBody()->write(json_encode([
            'params'=>['from'=>$p['from'],'to'=>$p['to'],'groupBy'=>$p['groupBy'],'staff_email'=>$p['staffEmail'] ?? null,'fields'=>$cols],
            'series'=>$rows
        ]));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // get all vitals & sessions for dashboard statistics Admin view
    // ------------------------------------------------------------------
    // ADMIN: Get Vitals Matrix (Corrected for Normalized Schema)
    // ------------------------------------------------------------------
    $app->get('/admin/stats/staff-vitals-matrix', function (Request $request, Response $response) {
        $db = new \App\db();
        $pdo = $db->getPDO();
        $params = $request->getQueryParams();

        // 1. Get Filters
        $scope = $params['scope'] ?? 'latest'; // 'latest' or 'all'
        $from = $params['from'] ?? null;
        $to = $params['to'] ?? null;

        // 2. Build the Core SQL
        // We use a Subquery 'cs_ranked' to calculate the order (rn)
        // This allows us to easily switch between "Rank 1" (Latest) or "All Ranks" (History)
        $sql = "
            WITH cs_ranked AS (
                SELECT 
                    cs.session_id,
                    cs.staff_email,
                    cs.session_date,
                    cs.status,
                    -- Calculate Rank: #1 is the newest session per email
                    ROW_NUMBER() OVER (PARTITION BY cs.staff_email ORDER BY cs.session_date DESC) as rn
                FROM checkup_sessions cs
                WHERE cs.status IN ('submitted', 'locked')
        ";

        $bindings = [];

        // 3. Apply Date Filter INSIDE the ranking logic
        // This ensures that if we pick 'Last Year', we get the latest session FROM LAST YEAR, not today.
        if ($from && $to) {
            $sql .= " AND cs.session_date BETWEEN :from AND :to";
            $bindings['from'] = $from;
            $bindings['to'] = $to;
        }

        $sql .= " ) 
            SELECT 
                -- Staff Info
                s.staff_name, 
                s.staff_email, 
                s.staff_no,
                
                -- Session Info
                latest.session_id, 
                latest.session_date, 
                YEAR(latest.session_date) as year_label,
                latest.status,
                
                -- Medical History
                mh.diabetes AS diabetes, -- Mapped to 'diabetes'
                
                -- Investigations
                inv.electrocardiograph_status AS ecg_status,
                inv.spirometry_status AS spiro_status,
                inv.audiometry_status AS audio_status,
                
                -- Physical Exam
                pe.bmi, 
                pe.bp_sys, 
                pe.bp_dia, 
                
                -- Lab Results
                il.tchol_value AS chol_val, 
                il.fbs_value AS glucose_val, 
                il.ua_value AS uric_val

            FROM cs_ranked latest
            JOIN staff s ON latest.staff_email = s.staff_email
            
            -- JOIN MEDICAL TABLES
            LEFT JOIN physical_exams pe ON latest.session_id = pe.session_id
            LEFT JOIN medical_history mh ON latest.session_id = mh.session_id
            LEFT JOIN investigations inv ON latest.session_id = inv.session_id
            LEFT JOIN investigations_lab il ON latest.session_id = il.session_id
            
            WHERE 1=1
        ";

        // 4. Apply Scope Filter
        // If scope is 'latest', we strictly require Rank #1
        if ($scope === 'latest') {
            $sql .= " AND latest.rn = 1";
        }
        // If scope is 'all', we just don't add the WHERE clause, so all Ranks show up.

        // 5. Final Order
        $sql .= " ORDER BY latest.session_date DESC, s.staff_name ASC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode(['items' => $items]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            // Return 500 with the actual SQL error so you can debug in Network Tab
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });//->add($jwtMiddleware);

    // get latest vitals for dashboard statistics Admin view
    $app->get('/admin/stats/all-latest-vitals', function (Request $req, Response $res) {
        $pdo = (new db())->getPDO();
        
        // 1. Get the optional cutoff date parameter
        $params = $req->getQueryParams();
        $cutoff = $params['cutoff'] ?? null; // Format: YYYY-MM-DD

        $sql = "
            SELECT 
                s.staff_name, s.staff_email, s.staff_no,
                latest.session_id, latest.session_date, latest.status,
                mh.diabetes,
                inv.electrocardiograph_status AS ecg_status,
                inv.spirometry_status AS spiro_status,
                inv.audiometry_status AS audio_status,
                pe.weight_kg, pe.height_m, pe.bmi, pe.bp_sys, pe.bp_dia, pe.pulse_bpm,
                il.tchol_value AS chol_val, il.fbs_value AS glucose_val, il.ua_value AS uric_val

            FROM staff s

            -- JOIN: Get the SINGLE latest valid session per staff (Time Travel Aware)
            INNER JOIN (
                SELECT 
                    cs.session_id,
                    cs.staff_email,
                    cs.session_date,
                    cs.status,
                    ROW_NUMBER() OVER (PARTITION BY staff_email ORDER BY session_date DESC) as rn
                FROM checkup_sessions cs
                WHERE cs.status IN ('submitted', 'locked')
                -- [NEW] Time Travel Logic: Ignore sessions after the cutoff
                AND (:cutoff IS NULL OR cs.session_date <= :cutoff)
            ) latest ON s.staff_email = latest.staff_email AND latest.rn = 1

            LEFT JOIN physical_exams pe ON latest.session_id = pe.session_id
            LEFT JOIN medical_history mh ON latest.session_id = mh.session_id
            LEFT JOIN investigations inv ON latest.session_id = inv.session_id
            LEFT JOIN investigations_lab il ON latest.session_id = il.session_id
            
            ORDER BY latest.session_date DESC
        ";

        try {
            $stmt = $pdo->prepare($sql);
            // Bind NULL if no date is provided, otherwise bind the date string
            $stmt->execute([':cutoff' => $cutoff]); 
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                $row['year_label'] = date('Y', strtotime($row['session_date']));
            }

            $res->getBody()->write(json_encode(['items' => $data]));
            return $res->withHeader('Content-Type', 'application/json');

        } catch (PDOException $e) {
            return $res->withStatus(500)->withHeader('Content-Type', 'application/json')
                    ->write(json_encode(['error' => $e->getMessage()]));
        }
    })->add($jwtMiddleware);

    // to get latest year's counts of smoker by each status 
    $app->get('/admin/stats/lifestyle-smokerstatus', function(Request $req, Response $res) {
        $params = $req->getQueryParams();
        $targetYear = $params['year'] ?? date('Y');

        $pdo = (new db())->getPDO(); 

        $sql = "
            SELECT 
                l.smoking_habit as status,
                COUNT(*) as count
            FROM (
                SELECT 
                    cs.session_id,
                    -- Rank 1 = Latest Valid Session IN THAT YEAR
                    ROW_NUMBER() OVER(PARTITION BY cs.staff_email ORDER BY cs.session_date DESC) as rn
                FROM checkup_sessions cs
                JOIN lifestyle l_inner ON l_inner.session_id = cs.session_id
                WHERE 
                    YEAR(cs.session_date) = :targetYear -- [CRITICAL] Strict Year
                    AND l_inner.smoking_habit IS NOT NULL AND l_inner.smoking_habit != ''
                    AND cs.status IN ('submitted', 'locked')
            ) ranked
            JOIN lifestyle l ON l.session_id = ranked.session_id
            WHERE ranked.rn = 1
            GROUP BY l.smoking_habit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['targetYear' => $targetYear]);
        $res->getBody()->write(json_encode(['series' => $stmt->fetchAll(PDO::FETCH_ASSOC)]));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    /**
     * GET /admin/stats/flags
     * Average abnormal counts by period and ranking by staff.
     * - series_by_period: avg abnormal_lab_count, avg abnormal_exam_count
     * - top_staff_by_abnormality: staff with highest mean abnormal counts
     */
    $app->get('/admin/stats/flags', function(Request $req, Response $res) {
        // try { requireAdmin($req); } catch (\RuntimeException $e) {
        //     return $res->withStatus(403)->withHeader('Content-Type','application/json')
        //         ->write(json_encode(['error'=>'forbidden','message'=>'Admin only']));
        // }

        $pdo = (new db())->getPDO();
        $p = parseAnalyticsParams($req);
        [$where, $binds] = buildWhere($p);
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // Compute per-session counts first (CTE), then aggregate
        $sql = "
            WITH per_session AS (
                SELECT
                  cs.session_id,
                  cs.session_date,
                  cs.staff_email,
                  -- lab abnormal count
                  (
                    (il.ldl_result='Abnormal') + (il.tchol_result='Abnormal') + (il.hdl_result='Abnormal') +
                    (il.tg_result='Abnormal') + (il.fbs_result='Abnormal') + (il.rbs_result='Abnormal') +
                    (il.na_result='Abnormal') + (il.k_result='Abnormal') + (il.cl_result='Abnormal') +
                    (il.ast_result='Abnormal') + (il.alt_result='Abnormal') + (il.ggT_result='Abnormal')
                  ) AS abnormal_lab_count,
                  -- exam abnormal count
                  (
                    (pe2.head='Abnormal') + (pe2.eyes='Abnormal') + (pe2.ears_and_drums='Abnormal') +
                    (pe2.hearing='Abnormal') + (pe2.nose_and_sinuses='Abnormal') + (pe2.mouth_teeth_throat='Abnormal') +
                    (pe2.neck_and_thyroid='Abnormal') + (pe2.chest_and_lungs='Abnormal') + (pe2.breasts='Abnormal') +
                    (pe2.heart='Abnormal') + (pe2.peripheral_arteries='Abnormal') + (pe2.peripheral_veins='Abnormal') +
                    (pe2.abdomen='Abnormal') + (pe2.hernia_orifices='Abnormal') + (pe2.genitalia='Abnormal') +
                    (pe2.rectal_examination='Abnormal') + (pe2.upper_limbs='Abnormal') + (pe2.lower_limbs='Abnormal') +
                    (pe2.spine='Abnormal') + (pe2.skin='Abnormal') + (pe2.lymph_nodes='Abnormal') +
                    (pe2.neurological='Abnormal') + (pe2.psychiatric='Abnormal')
                  ) AS abnormal_exam_count
                FROM checkup_sessions cs
                LEFT JOIN investigations_lab il ON il.session_id = cs.session_id
                LEFT JOIN physical_exams_2 pe2 ON pe2.session_id = cs.session_id
                $whereSql
            )
            SELECT * FROM per_session
        ";
        // fetch CTE materialized rows to reuse in PHP (simpler & portable)
        $st = $pdo->prepare($sql); $st->execute($binds);
        $ps = $st->fetchAll(\PDO::FETCH_ASSOC);

        // Aggregate by period in PHP to avoid repeating SQL (small overhead, clearer output)
        // If dataset is huge, convert this into a SQL GROUP BY using {$p['groupExpr']} too.
        $seriesByPeriod = [];
        $byStaff = []; // for ranking

        foreach ($ps as $r) {
            $date = $r['session_date'];
            // Build period key using MySQL-ish labels via PHP:
            $dt = new \DateTime($date);
            switch ($p['groupBy']) {
                case 'day':     $bucket = $dt->format('Y-m-d'); break;
                case 'week':    $bucket = $dt->format('o-\WW'); break; // ISO week (e.g., 2025-W05)
                case 'month':   $bucket = $dt->format('Y-m');   break;
                case 'quarter': $bucket = $dt->format('Y').'-Q'.ceil(((int)$dt->format('n'))/3); break;
                case 'year':    $bucket = $dt->format('Y');     break;
                default:        $bucket = $dt->format('Y-m');
            }
            $lab = (int)$r['abnormal_lab_count'];
            $exam = (int)$r['abnormal_exam_count'];

            if (!isset($seriesByPeriod[$bucket])) $seriesByPeriod[$bucket] = ['n'=>0,'sum_lab'=>0,'sum_exam'=>0];
            $seriesByPeriod[$bucket]['n']++;
            $seriesByPeriod[$bucket]['sum_lab'] += $lab;
            $seriesByPeriod[$bucket]['sum_exam'] += $exam;

            $staff = $r['staff_email'];
            if (!isset($byStaff[$staff])) $byStaff[$staff] = ['n'=>0,'sum_lab'=>0,'sum_exam'=>0];
            $byStaff[$staff]['n']++;
            $byStaff[$staff]['sum_lab'] += $lab;
            $byStaff[$staff]['sum_exam'] += $exam;
        }

        ksort($seriesByPeriod);
        $series = [];
        foreach ($seriesByPeriod as $bucket=>$agg) {
            $series[] = [
                'bucket'=>$bucket,
                'n'=>$agg['n'],
                'avg_abnormal_lab'  => $agg['n'] ? $agg['sum_lab']/$agg['n'] : 0,
                'avg_abnormal_exam' => $agg['n'] ? $agg['sum_exam']/$agg['n'] : 0,
            ];
        }

        // top staff by combined mean abnormality
        $rank = [];
        foreach ($byStaff as $staff=>$agg) {
            $meanLab  = $agg['n'] ? $agg['sum_lab']/$agg['n'] : 0;
            $meanExam = $agg['n'] ? $agg['sum_exam']/$agg['n'] : 0;
            $rank[] = [
                'staff_email'=>$staff,
                'n'=>$agg['n'],
                'mean_abnormal_lab'=>$meanLab,
                'mean_abnormal_exam'=>$meanExam,
                'mean_combined'=>$meanLab + $meanExam
            ];
        }
        usort($rank, fn($a,$b)=> $b['mean_combined'] <=> $a['mean_combined']);
        $top = array_slice($rank, 0, 10);

        $res->getBody()->write(json_encode([
            'params'=>['from'=>$p['from'],'to'=>$p['to'],'groupBy'=>$p['groupBy'],'staff_email'=>$p['staffEmail'] ?? null],
            'series_by_period'=>$series,
            'top_staff_by_abnormality'=>$top
        ]));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // to accept a strict year instead of a date range - for bmi and chol bar charts
    $app->get('/admin/stats/vitals-2', function (Request $req, Response $res) {
        $params = $req->getQueryParams();
        $groupBy = $params['groupBy'] ?? 'year_latest';
        $targetYear = $params['year'] ?? date('Y');
        
        // Dynamic Thresholds
        $maxUnder = $params['max_under'] ?? 20.0;
        $maxIdeal = $params['max_ideal'] ?? 24.9;

        $pdo = (new db())->getPDO(); 
        $stmt = null; // Initialize to avoid undefined variable errors

        if ($groupBy === 'year_latest') {
            $sql = "
                SELECT 
                    '$targetYear' as bucket,
                    COUNT(CASE WHEN bmi <= :maxUnder THEN 1 END) as count_low,
                    COUNT(CASE WHEN bmi > :maxUnder AND bmi <= :maxIdeal THEN 1 END) as count_mid,
                    COUNT(CASE WHEN bmi > :maxIdeal THEN 1 END) as count_high
                FROM (
                    SELECT 
                        pe.bmi,
                        cs.session_date,
                        ROW_NUMBER() OVER(PARTITION BY cs.staff_email ORDER BY cs.session_date DESC) as rn
                    FROM checkup_sessions cs
                    JOIN physical_exams pe ON pe.session_id = cs.session_id
                    WHERE pe.bmi IS NOT NULL AND pe.bmi > 0
                    AND YEAR(cs.session_date) = :targetYear
                    AND cs.status IN ('submitted', 'locked')
                ) ranked
                WHERE rn = 1
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'targetYear' => $targetYear,
                'maxUnder' => $maxUnder,
                'maxIdeal' => $maxIdeal
            ]);
        } 
        
        // --- KEY FIX STARTS HERE ---
        
        $data = [];
        
        if ($stmt) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            // Since it's a COUNT query, it always returns one row, 
            // so we wrap that single row in an array.
            if ($row) {
                $data = [$row];
            }
        }

        // Wrap in "series" to match Frontend expectation: json.series
        $payload = ['series' => $data];

        $res->getBody()->write(json_encode($payload));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // to get count of  normal/abnormal results of general investigations // trial
    $app->get('/admin/stats/investigations', function(Request $req, Response $res) {
        try {
            $pdo = (new db())->getPDO();
            
            // FIX: Get params directly to guarantee 'year' is captured
            $queryParams = $req->getQueryParams();
            $year = $queryParams['year'] ?? null;

            // 1. Build Dynamic WHERE clause
            // If year is provided, filter by it. Otherwise, get all-time latest.
            $yearCondition = "";
            $params = [];
            
            if (!empty($year) && is_numeric($year)) {
                $yearCondition = "WHERE YEAR(cs.session_date) = :year";
                $params['year'] = $year;
            }

            // 2. The Query
            // We use COALESCE(SUM(...), 0) to ensure we get 0 instead of NULL
            $sql = "
                SELECT 
                    COALESCE(SUM(CASE WHEN i.spirometry_status = 'Normal' THEN 1 ELSE 0 END), 0) as spiro_normal,
                    COALESCE(SUM(CASE WHEN i.spirometry_status = 'Abnormal' THEN 1 ELSE 0 END), 0) as spiro_abnormal,
                    
                    COALESCE(SUM(CASE WHEN i.audiometry_status = 'Normal' THEN 1 ELSE 0 END), 0) as audio_normal,
                    COALESCE(SUM(CASE WHEN i.audiometry_status = 'Abnormal' THEN 1 ELSE 0 END), 0) as audio_abnormal,

                    COALESCE(SUM(CASE WHEN i.chest_xray_status = 'Normal' THEN 1 ELSE 0 END), 0) as xray_normal,
                    COALESCE(SUM(CASE WHEN i.chest_xray_status = 'Abnormal' THEN 1 ELSE 0 END), 0) as xray_abnormal,

                    COALESCE(SUM(CASE WHEN i.electrocardiograph_status = 'Normal' THEN 1 ELSE 0 END), 0) as ecg_normal,
                    COALESCE(SUM(CASE WHEN i.electrocardiograph_status = 'Abnormal' THEN 1 ELSE 0 END), 0) as ecg_abnormal
                FROM (
                    SELECT 
                        cs.session_id,
                        ROW_NUMBER() OVER(PARTITION BY cs.staff_email ORDER BY cs.session_date DESC) as rn
                    FROM checkup_sessions cs
                    JOIN investigations i_inner ON i_inner.session_id = cs.session_id
                    -- Only apply year filter inside the subquery to find 'latest of that year'
                    $yearCondition
                ) ranked
                JOIN investigations i ON i.session_id = ranked.session_id
                WHERE ranked.rn = 1
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            // 3. Fail-safe: If fetch fails entirely (rare), ensure we have an array
            if (!$data) {
                $data = [
                    'spiro_normal' => 0, 'spiro_abnormal' => 0,
                    'audio_normal' => 0, 'audio_abnormal' => 0,
                    'xray_normal' => 0, 'xray_abnormal' => 0,
                    'ecg_normal' => 0, 'ecg_abnormal' => 0
                ];
            }

            $chartData = [
                'Spirometry' => ['normal' => $data['spiro_normal'], 'abnormal' => $data['spiro_abnormal']],
                'Audiometry' => ['normal' => $data['audio_normal'], 'abnormal' => $data['audio_abnormal']],
                'Chest X-Ray' => ['normal' => $data['xray_normal'], 'abnormal' => $data['xray_abnormal']],
                'ECG' => ['normal' => $data['ecg_normal'], 'abnormal' => $data['ecg_abnormal']],
            ];

            $res->getBody()->write(json_encode(['series' => $chartData]));
            return $res->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $res->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $res->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    /**
     * GET /admin/stats/staff-abnormality
     * Returns a list of sessions/staff that match a specific abnormality type.
     * Query: type=cholesterol,liver,glucose,uric_acid,renal,all
     */
    $app->get('/admin/stats/staff-abnormality', function(Request $req, Response $res) {
        $pdo = (new db())->getPDO();
        $p = parseAnalyticsParams($req);
        [$where, $binds] = buildWhere($p);
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $qp = $req->getQueryParams();
        $type = strtolower($qp['type'] ?? '');
        
        // --- NEW PARAMETER HANDLING ---
        $requestedStatus = strtolower($qp['result_status'] ?? 'abnormal');
        $validStatuses = ['normal', 'abnormal', 'not done', 'all'];
        
        // Validate overall type parameter
        $abnormalityConditions = [
            'cholesterol' => ['fields' => ['ldl', 'tchol', 'hdl', 'tg']],
            'liver'       => ['fields' => ['tbil', 'ggt', 'ast', 'alt']],
            'glucose'     => ['fields' => ['fbs', 'rbs']],
            'uric_acid'   => ['fields' => ['ua']],
            'renal'       => ['fields' => ['bu', 'creat', 'na', 'k', 'cl']],
        ];

        // Add support for type='all' to fetch EVERYTHING
        if ($type === 'all') {
            $abnormalityConditions['all'] = ['fields' => array_unique(array_merge(...array_values($abnormalityConditions)))];
        }
        
        if (!isset($abnormalityConditions[$type])) {
            $res->getBody()->write(json_encode([
                'error'=>'invalid_type',
                'message'=>'Invalid or missing abnormality type. Allowed: '.implode(',', array_keys($abnormalityConditions))
            ]));
            return $res->withStatus(400)->withHeader('Content-Type','application/json');
        }

        // Validate result_status parameter
        if (!in_array($requestedStatus, $validStatuses)) {
            $res->getBody()->write(json_encode([
                'error'=>'invalid_status',
                'message'=>'Invalid result_status. Allowed: '.implode(', ', $validStatuses)
            ]));
            return $res->withStatus(400)->withHeader('Content-Type','application/json');
        }

        $config = $abnormalityConditions[$type];
        $selectFields = '';
        foreach ($config['fields'] as $col) {
            $selectFields .= "il.{$col}_result, il.{$col}_remark,";  // Add remarks for averages
        }
        $selectFields = rtrim($selectFields, ',');
        
        // --- BUILD DYNAMIC WHERE CLAUSE FOR LAB RESULTS ---
        $labWhere = null;
        if ($requestedStatus !== 'all') {
            // Filter by a specific status (Normal, Abnormal, Not done)
            $statusCheck = ucfirst($requestedStatus); // E.g., 'abnormal' -> 'Abnormal'
            
            // Build the OR condition: (il.field1 = 'Status' OR il.field2 = 'Status' OR ...)
            $labConditions = [];
            foreach ($config['fields'] as $col) {
                $labConditions[] = "il.{$col}_result = '{$statusCheck}'";
            }
            $labWhere = "(".implode(' OR ', $labConditions).")";
        }
        
        // Combine all WHERE clauses
        $dynamicWhere = $whereSql;
        if ($labWhere) {
            // Prepend AND if $whereSql (session date/staff filter) is already present
            $joinWord = $whereSql ? ' AND ' : ' WHERE ';
            $dynamicWhere .= $joinWord . $labWhere;
        }

        // Final SQL construction
        $sql = "
            SELECT cs.staff_email, s.staff_name, cs.session_id, cs.session_date, $selectFields
            FROM checkup_sessions cs
            JOIN staff s ON s.staff_email = cs.staff_email
            JOIN investigations_lab il ON il.session_id = cs.session_id
            $dynamicWhere
            ORDER BY cs.session_date DESC
        ";

        $st = $pdo->prepare($sql); $st->execute($binds);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

        $res->getBody()->write(json_encode([
            'params'=>['from'=>$p['from'],'to'=>$p['to'],'type'=>$type,'result_status'=>$requestedStatus,'staff_email'=>$p['staffEmail'] ?? null],
            'results'=>$rows
        ]));
        return $res->withHeader('Content-Type','application/json');
    })->add($jwtMiddleware);

    // GET /admin/stats/latest-lab-per-staff
    $app->get('/admin/stats/latest-lab-per-staff', function($req, $res) {
        $pdo = (new db())->getPDO();

        // Added WHERE status = 'Submitted' in two places
        $sql = "
            SELECT 
                il.fbs_result, il.rbs_result, 
                il.creat_result, il.bu_result, il.ua_result, 
                il.na_result, il.k_result, il.cl_result, 
                il.ldl_result, il.tchol_result, il.tg_result, il.hdl_result, 
                il.alt_result, il.ast_result, il.ggt_result, il.bu_result, il.tbil_result,
            -- SELECT il.*, 
            cs.staff_email, cs.session_date
            FROM investigations_lab il
            JOIN checkup_sessions cs ON cs.session_id = il.session_id
            JOIN (
                SELECT staff_email, MAX(session_date) AS max_date
                FROM checkup_sessions
                WHERE status = 'Submitted' -- 1. Only calculate max date based on submitted sessions
                GROUP BY staff_email
            ) latest ON cs.staff_email = latest.staff_email 
                    AND cs.session_date = latest.max_date
            WHERE cs.status = 'Submitted' -- 2. Ensure the joined session is the submitted one
            ORDER BY cs.session_date DESC
        ";

        try {
            $st = $pdo->query($sql);
            $results = $st->fetchAll(\PDO::FETCH_ASSOC);

            $newResponse = $res->withHeader('Content-Type', 'application/json');
            $newResponse->getBody()->write(json_encode(['results' => $results]));

            return $newResponse;

        } catch (\Slim\Exception\HttpNotFoundException $e) {
            $newResponse = $res->withStatus(404)->withHeader('Content-Type', 'application/json');
            $newResponse->getBody()->write(json_encode([
                'error' => 'Not found.',
                'type' => get_class($e)
            ]));
            return $newResponse;
        } catch (\Exception $e) {
            $newResponse = $res->withStatus(500)->withHeader('Content-Type', 'application/json');
            $newResponse->getBody()->write(json_encode([
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ]));
            return $newResponse;
        }
    })->add($jwtMiddleware);

    //analyticscharts2
    // to get staff data of crucial pre-selected data in the sessions over the years
    $app->get('/staff/health-trends/{staff_email}', function ($req, $res, $args) {
        $pdo = (new db())->getPDO();
        $staffEmail = str_replace('XYZ','.',$args['staff_email']);

        if (!$staffEmail) {
            $res->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $res->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $sql = "
            SELECT 
                -- X-Axis Label
                YEAR(cs.session_date) as year_label,
                cs.session_date,

                -- 1. BP
                pe.bp_sys as bp_systolic,
                pe.bp_dia as bp_diastolic,

                -- 2. BMI
                pe.bmi,

                -- 3. Total Cholesterol 
                ilab.tchol_value as chol_score,

                -- 4. Uric Acid
                ilab.ua_value as uric_score,

                -- 5. ECG (1=Normal, 0=Abnormal)
                CASE 
                    WHEN inv.electrocardiograph_status = 'Normal' THEN 1 
                    WHEN inv.electrocardiograph_status = 'Abnormal' THEN 0 
                    ELSE NULL 
                END as ecg_score,

                -- 6. Spirometry
                CASE 
                    WHEN inv.spirometry_status = 'Normal' THEN 1 
                    WHEN inv.spirometry_status = 'Abnormal' THEN 0 
                    ELSE NULL 
                END as spiro_score,

                -- 7. Audiometry
                CASE 
                    WHEN inv.audiometry_status = 'Normal' THEN 1 
                    WHEN inv.audiometry_status = 'Abnormal' THEN 0 
                    ELSE NULL 
                END as audio_score,

                -- 8. Diabetic History
                CASE 
                    WHEN mh.diabetes = 'Y' THEN 1 
                    ELSE 0 
                END as is_diabetic

            FROM checkup_sessions cs
            -- Join Physical Exams
            LEFT JOIN physical_exams pe 
                ON pe.session_id = cs.session_id AND pe.staff_email = cs.staff_email
            
            -- Join Investigations
            LEFT JOIN investigations inv 
                ON inv.session_id = cs.session_id AND inv.staff_email = cs.staff_email
                
            -- Join Lab Results
            LEFT JOIN investigations_lab ilab 
                ON ilab.session_id = cs.session_id AND ilab.staff_email = cs.staff_email

            -- Join Medical History
            LEFT JOIN medical_history mh 
                ON mh.session_id = cs.session_id AND mh.staff_email = cs.staff_email

            WHERE cs.staff_email = :email
            -- [UPDATED] Strict Filter: Only show finalized data for charts
            AND cs.status IN ('submitted', 'locked') 
            
            ORDER BY cs.session_date ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $staffEmail]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $res->getBody()->write(json_encode(['items' => $rows]));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // staff-bp-glucose charts
    // to get data of blood pressure and glucose for each staff by staff_email
    $app->get('/staff/stats/bp-glucose/{staff_email}', function ($request, $response, $args) {
        
        $pdo = (new db())->getPDO();

        // 1. Handle URL Encoding
        $urlEmail = $args['staff_email']; 
        $realEmail = str_replace(['XYZ', 'UVW'], ['.', '+'], $urlEmail);

        try {
            // ---------------------------------------------------------
            // QUERY A: Get Basic Staff Info
            // ---------------------------------------------------------
            $sqlStaff = "SELECT staff_name, staff_no, year_of_born 
                        FROM staff 
                        WHERE staff_email = :email";
            $stmt = $pdo->prepare($sqlStaff);
            $stmt->execute([':email' => $realEmail]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$staff) {
                // FIX: $response is now defined!
                $response->getBody()->write(json_encode(['error' => 'Staff not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Calculate Age
            $currentYear = date("Y");
            $age = $staff['year_of_born'] ? ($currentYear - $staff['year_of_born']) : 'N/A';

            // ---------------------------------------------------------
            // QUERY B: Get Medical History
            // ---------------------------------------------------------
            $sqlHistory = "
                SELECT 
                    cs.session_date,
                    cs.session_type,
                    cs.session_remarks,
                    pe.bp_sys,
                    pe.bp_dia,
                    pe.pulse_bpm,
                    pe.bmi,
                    inv.fbs_value AS fbs_result,
                    inv.hb_value AS hb_result
                FROM checkup_sessions cs
                LEFT JOIN physical_exams pe ON cs.session_id = pe.session_id
                LEFT JOIN investigations_lab inv ON cs.session_id = inv.session_id
                WHERE cs.staff_email = :email
                AND cs.status = 'submitted' OR 'locked'
                ORDER BY cs.session_date DESC
            ";

            $stmtHist = $pdo->prepare($sqlHistory);
            $stmtHist->execute([':email' => $realEmail]);
            $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

            // ---------------------------------------------------------
            // RESPONSE
            // ---------------------------------------------------------
            $payload = [
                'staff' => [
                    'name' => $staff['staff_name'],
                    'id'   => $staff['staff_no'],
                    'age'  => $age
                ],
                'history' => $history 
            ];

            // FIX: $response is now defined!
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (PDOException $e) {
            // Handle DB errors gracefully
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        } catch (RuntimeException $e) {
            // Handle Runtime errors
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

};