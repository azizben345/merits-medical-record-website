<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to get staff info by email
    $app->get('/staff/info/{email:.+}', function (Request $request, Response $response, $args) {
        // $email = (string)$args['email'];
        $email = str_replace('XYZ', '.', urldecode($args['email']));
        // $email = $args['email'];

        $db = new db();
        $pdo = $db->getPDO();

        if ($email === null || $email === false || trim($email) === '') {
            $response->getBody()->write(json_encode(['error' => 'Invalid email provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $pdo->prepare("SELECT *,
            (SELECT session_date FROM checkup_sessions WHERE staff_email = s.staff_email ORDER BY session_date DESC LIMIT 1 OFFSET 0) AS date_of_this_assessment,
            (SELECT session_date FROM checkup_sessions WHERE staff_email = s.staff_email ORDER BY session_date DESC LIMIT 1 OFFSET 1) AS date_of_last_assessment
            FROM staff s
            WHERE s.staff_email = :email");
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();
        $staff = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($staff === false || $staff === null)  {
            $response->getBody()->write(json_encode(['error' => 'Staff not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // --- age calculation logic ---
        $storedAge = (int)$staff['age'];
        $dateOfBirth = $staff['date_of_birth'];
        $newAge = null;
        $shouldUpdate = false;

        // Check if date_of_birth exists and is a valid date string
        if (!empty($dateOfBirth)) {
            try {
                // Calculate age by finding the difference in years between today and DOB
                $dob = new \DateTime($dateOfBirth);
                $now = new \DateTime();
                $interval = $now->diff($dob);
                $newAge = $interval->y; // Get the difference in years
                
                // Compare calculated age with stored age
                if ($newAge !== $storedAge) {
                    $shouldUpdate = true;
                    $staff['age'] = $newAge; // Update the returned data array immediately
                }
            } catch (\Exception $e) {
                // Handle invalid date format if necessary
                // skip the age update
            }
        }

        // --- 3. Database Update ---
        if ($shouldUpdate) {
            $updateStmt = $pdo->prepare("UPDATE staff SET age = :new_age WHERE staff_email = :email");
            $updateStmt->bindParam(':new_age', $newAge, \PDO::PARAM_INT);
            $updateStmt->bindParam(':email', $email, \PDO::PARAM_STR);
            $updateStmt->execute();
        }
        // --- End Age Logic ---

        $response->getBody()->write(json_encode($staff));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    })->add($jwtMiddleware);

    // to edit staff info by email
    $app->post('/staff/edit-info/{email:.+}', function (Request $request, Response $response, $args) {
        
        $email = str_replace('XYZ', '.', urldecode($args['email']));
        $data = json_decode($request->getBody()->getContents(), true);

        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = new db();
        $pdo = $db->getPDO();

        // Check if staff exists
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_email = :email");
        //$stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->execute([':email' => $email]);
        $existingStaff = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingStaff === false || $existingStaff === null)  {
            $response->getBody()->write(json_encode(['error' => 'Staff not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update staff info
        $stmt = $pdo->prepare("UPDATE staff 
                                SET staff_name = :name, 
                                marital_status = :marital_status,
                                sex = :sex, 
                                date_of_birth = :date_of_birth, 
                                year_of_born = :year_of_born, 
                                age = :age, 
                                ic_passport = :ic_passport, 
                                nationality = :nationality, 
                                job_title = :job_title,
                                department = :department,
                                staff_no = :staff_no, 
                                phone_no = :phone_no, 
                                address = :address, 
                                personal_doctor_email = :personal_doctor_email, 
                                doctor_phone_no = :doctor_phone_no, 
                                reason_for_examination = :reason_for_examination 
                                WHERE staff_email = :email");
        $stmt->execute([
            ':name' => $data['staff_name'],
            ':marital_status' => $data['marital_status'],
            ':sex' => $data['sex'],
            ':date_of_birth' => $data['date_of_birth'],
            ':year_of_born' => $data['year_of_born'],
            ':age' => $data['age'],
            ':ic_passport' => $data['ic_passport'],
            ':nationality' => $data['nationality'],
            ':job_title' => $data['job_title'],
            ':department' => $data['department'],
            ':staff_no' => $data['staff_no'],
            ':phone_no' => $data['phone_no'],
            ':address' => $data['address'],
            ':personal_doctor_email' => $data['personal_doctor_email'],
            ':doctor_phone_no' => $data['doctor_phone_no'],
            ':reason_for_examination' => $data['reason_for_examination'],
            ':email' => $email
        ]);

        $response->getBody()->write(json_encode(['message' => 'Staff info updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    })->add($jwtMiddleware);

    // to get staff list (staff_email, staff_name)
    $app->get('/admin/staff-list', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT staff_email, staff_name FROM staff");
        $stmt->execute();
        $staffList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($staffList));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

    // GET /admin/non-session/staff/{staff_email}
    $app->get('/admin/non-session/staff/{staff_email}', function ($req, $res, $args) {
        $db = new db(); $pdo = $db->getPDO();
        $email = str_replace('XYZ', '.', urldecode($args['staff_email']));

        // staff basic
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_email = :e");
        $stmt->execute([':e' => $email]);
        $staff = $stmt->fetch(\PDO::FETCH_ASSOC);

        // occ history (0..N)
        $stmt = $pdo->prepare("SELECT * FROM occupational_history WHERE staff_email = :e ORDER BY year DESC, oh_id DESC");
        $stmt->execute([':e' => $email]);
        $occ = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // family members (0..N)
        $stmt = $pdo->prepare("SELECT * FROM family_history WHERE staff_email = :e ORDER BY relationship, fh_id DESC");
        $stmt->execute([':e' => $email]);
        $fam = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // family disease (0/1)
        $stmt = $pdo->prepare("SELECT * FROM family_history_disease WHERE staff_email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $fhd = $stmt->fetch(\PDO::FETCH_ASSOC);

        $res->getBody()->write(json_encode([
            'staff' => $staff ?: null,
            'occupational_history' => $occ,
            'family_history' => $fam,
            'family_history_disease' => $fhd ?: null
        ]));
        return $res->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get staff_email, staff_name by session_id
    $app->get('/staff-session/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT s.staff_email, s.session_id, st.staff_name 
                            FROM checkup_sessions s 
                            LEFT JOIN staff st 
                            ON s.staff_email = st.staff_email 
                            WHERE s.session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $staff = $stmt->fetch(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($staff));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

    // // to get staff record by email //unused
    // $app->get('/staff/record/{email:.+}', function (Request $request, Response $response, $args) {
    //     $email = str_replace('XYZ', '.', urldecode($args['email']));

    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     if ($email === null || $email === false || trim($email) === '') {
    //         $response->getBody()->write(json_encode(['error' => 'Invalid email provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     $stmt = $pdo->prepare("SELECT * FROM medical_record WHERE staff_email = :email");
    //     $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
    //     $stmt->execute();
    //     $staffRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

    //     if ($staffRecord === false || $staffRecord === null)  {
    //         $response->getBody()->write(json_encode(['error' => 'Staff record not found']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    //     }

    //     $response->getBody()->write(json_encode($staffRecord));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    // })->add($jwtMiddleware);

    // // to edit staff record by email //unused
    // $app->post('/staff/edit-record/{email:.+}', function (Request $request, Response $response, $args) {
        
    //     $email = str_replace('XYZ', '.', urldecode($args['email']));
    //     $data = json_decode($request->getBody()->getContents(), true);

    //     if ($data === null || $data === false) {
    //         $response->getBody()->write(json_encode(['error' => 'No data provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     // Check if staff record exists
    //     $stmt = $pdo->prepare("SELECT * FROM medical_record WHERE staff_email = :email");
    //     $stmt->execute([':email' => $email]);
    //     $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

    //     if ($existingRecord === false || $existingRecord === null)  {
    //         $response->getBody()->write(json_encode(['error' => 'Staff record not found']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    //     }

    //     // Update staff record
    //     $stmt = $pdo->prepare("UPDATE medical_record 
    //                             SET last_updated = NOW(),
    //                             updated_by = :email,
    //                             blood_type = :blood_type, 
    //                             allergies = :allergies, 
    //                             existing_conditions = :existing_conditions, 
    //                             medications = :medications, 
    //                             emergency_contact_name = :emergency_contact_name,
    //                             emergency_contact_phone_no = :emergency_contact_phone_no,
    //                             emergency_contact_relationship = :emergency_contact_relationship
    //                             WHERE staff_email = :email");
    //     $stmt->execute([
    //         ':blood_type' => $data['blood_type'],
    //         ':allergies' => $data['allergies'],
    //         ':existing_conditions' => $data['existing_conditions'],
    //         ':medications' => $data['medications'],
    //         ':emergency_contact_name' => $data['emergency_contact_name'],
    //         ':emergency_contact_phone_no' => $data['emergency_contact_phone_no'],
    //         ':emergency_contact_relationship' => $data['emergency_contact_relationship'],
    //         ':email' => $email
    //     ]);

    //     $response->getBody()->write(json_encode(['message' => 'Staff record updated successfully']));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    // })->add($jwtMiddleware);

};