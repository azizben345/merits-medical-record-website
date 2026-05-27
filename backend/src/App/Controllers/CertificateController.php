<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // GET Certificate for a Session
    $app->get('/sessions/{session_id}/fitness-certificate', function ($req, $res, $args) {
        $pdo = (new db())->getPDO();
        $sessionId = $args['session_id'];

        try {
            // 1. Try to find an existing certificate
            $stmt = $pdo->prepare("SELECT * FROM fitness_work_certificates WHERE session_id = :sid");
            $stmt->execute([':sid' => $sessionId]);
            $cert = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($cert) {
                $res->getBody()->write(json_encode($cert));
                return $res->withHeader('Content-Type', 'application/json');
            } 
            
            // 2. Fetch Defaults (Safe Mode)
            $sqlDefaults = "
                SELECT 
                    cs.staff_email,
                    s.staff_name, 
                    s.ic_passport
                FROM checkup_sessions cs
                LEFT JOIN staff s ON cs.staff_email = s.staff_email
                WHERE cs.session_id = :sid
            ";
            $stmtDef = $pdo->prepare($sqlDefaults);
            $stmtDef->execute([':sid' => $sessionId]);
            $defaults = $stmtDef->fetch(\PDO::FETCH_ASSOC);

            if (!$defaults) {
                $res->getBody()->write(json_encode(['error' => 'Session not found']));
                return $res->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // 3. Prepare Safe Payload
            $staffName = $defaults['staff_name'] ?? 'Unknown Staff';
            $staffIcPass   = $defaults['ic_passport'] ?? '-'; 

            $payload = [
                'certificate_id' => null,
                'session_id' => $sessionId,
                'staff_name' => $staffName,
                'staff_ic_passport' => $staffIcPass,
                'doctor_name_qualifications' => '', 
                'assessment_date' => date('Y-m-d'),
                'fitness_category' => null, 
                'restrictions_text' => ''
            ];

            $res->getBody()->write(json_encode($payload));
            return $res->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            // CATCHES THE 500 ERROR and prints the real reasons
            $res->getBody()->write(json_encode(['error' => 'Server Error: ' . $e->getMessage()]));
            return $res->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // POST Upsert (Save/Update) Certificate
    $app->post('/sessions/{session_id}/update-certificate', function ($req, $res, $args) {
        $pdo = (new db())->getPDO();
        $sessionId = $args['session_id'];
        $data = $req->getParsedBody();

        if (is_null($data) || !is_array($data)) {
            $contents = (string) $req->getBody();
            $data = json_decode($contents, true);
        }
        if (!is_array($data)) {
            $res->getBody()->write(json_encode(['error' => 'Invalid JSON Data Received']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // // Basic Validation
        // if (!in_array($data['fitness_category'] ?? '', ['A', 'B', 'C'])) {
        //     return $res->withStatus(400)->write(json_encode(['error' => 'Invalid Fitness Category. Must be A, B, or C.']));
        // }

        // Check if exists
        $checkSql = "SELECT ftw_cert_id FROM fitness_work_certificates WHERE session_id = :sid";
        $stmtCheck = $pdo->prepare($checkSql);
        $stmtCheck->execute([':sid' => $sessionId]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            // UPDATE
            $sql = "UPDATE fitness_work_certificates SET
                        staff_name = :sname,
                        staff_ic_passport = :sic,
                        doctor_name_qualifications = :doc,
                        assessment_date = :date,
                        fitness_category = :cat,
                        restrictions_text = :rest
                    WHERE session_id = :sid";
        } else {
            // INSERT
            $sql = "INSERT INTO fitness_work_certificates 
                        (session_id, staff_name, staff_ic_passport, doctor_name_qualifications, assessment_date, fitness_category, restrictions_text)
                    VALUES 
                        (:sid, :sname, :sic, :doc, :date, :cat, :rest)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sid'   => $sessionId,
            ':sname' => $data['staff_name'] ?? '',          // Use ?? '' for strings
            ':sic'   => $data['staff_ic_passport'] ?? '',
            ':doc'   => $data['doctor_name_qualifications'] ?? '',
            ':date'  => $data['assessment_date'] ?? date('Y-m-d'),
            ':cat'   => !empty($data['fitness_category']) ? $data['fitness_category'] : null, // Handle NULL logic
            ':rest'  => $data['restrictions_text'] ?? ''
        ]);

        $res->getBody()->write(json_encode(['message' => 'Certificate saved successfully']));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

};