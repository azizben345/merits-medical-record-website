<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to get admin info by email
    $app->get('/admin/info/{email:.+}', function (Request $request, Response $response, $args) {
        // $email = str_replace('XYZ', '.', urldecode($args['email']));
        $email = str_replace(['XYZ', 'UVW'], ['.', '+'], urldecode($args['email']));

        $db = new db();
        $pdo = $db->getPDO();

        if ($email === null || $email === false || trim($email) === '') {
            $response->getBody()->write(json_encode(['error' => 'Invalid email provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_email = :email");
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($admin === false || $admin === null)  {
            $response->getBody()->write(json_encode(['error' => 'Admin not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($admin));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    })->add($jwtMiddleware);

    // to get list of all doctor
    $app->get('/admin/doctors', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM doctor");
        $doctors = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($doctors as &$doctor) {
            // get user_id from users table by matching email
            $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $userStmt->execute([$doctor['doctor_email']]);
            $user = $userStmt->fetch(\PDO::FETCH_ASSOC);
            $doctor['user_id'] = $user ? $user['user_id'] : null;
        }
        unset($doctor);

        $response->getBody()->write(json_encode($doctors));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);

    // to get all staff records // unused
    $app->get('/admin/staff-records', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM medical_record");
        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($records as &$record) {
            // get user_id from users table by matching email
            $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $userStmt->execute([$record['staff_email']]);
            $user = $userStmt->fetch(\PDO::FETCH_ASSOC);
            $record['user_id'] = $user ? $user['user_id'] : null;

            // get staff_uid from staff table by matching email
            $staffStmt = $pdo->prepare("SELECT staff_uid FROM staff WHERE staff_email = ?");
            $staffStmt->execute([$record['staff_email']]);
            $staff = $staffStmt->fetch(\PDO::FETCH_ASSOC);
            $record['staff_uid'] = $staff ? $staff['staff_uid'] : null;
        }
        unset($record);

        $response->getBody()->write(json_encode($records));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);

    // to edit staff record by staff_email //unused 
    $app->post('/admin/edit-record/{email:.+}', function (Request $request, Response $response, $args) {
        
        // $email = str_replace('XYZ', '.', urldecode($args['email']));
        $email = str_replace(['XYZ', 'UVW'], ['.', '+'], urldecode($args['email']));
        $data = json_decode($request->getBody()->getContents(), true);

        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = new db();
        $pdo = $db->getPDO();

        // Check if staff exists
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_email = :email");
        
        $stmt->execute([':email' => $email]);
        $existingStaff = $stmt->fetch(\PDO::FETCH_ASSOC);

        // If staff not found, return an error
        if ($existingStaff === false || $existingStaff === null) {
            $response->getBody()->write(json_encode(['error' => 'Staff not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Check if medical record exists for the staff
        $stmt = $pdo->prepare("SELECT * FROM medical_record WHERE staff_email = :email");
        
        $stmt->execute([':email' => $email]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        // If medical record not found, return an error
        if ($existingRecord === false || $existingRecord === null) {
            $response->getBody()->write(json_encode(['error' => 'Medical record not found for the specified staff']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update staff record
        $stmt = $pdo->prepare("UPDATE medical_record SET
                                blood_type = :blood_type, 
                                allergies = :allergies, 
                                existing_conditions = :existing_conditions, 
                                medications = :medications, 
                                doctor_email = :doctor_email,
                                doctor_name = :doctor_name
                                WHERE staff_email = :email");
        $stmt->execute([
            ':blood_type' => $data['blood_type'],
            ':allergies' => $data['allergies'],
            ':existing_conditions' => $data['existing_conditions'],
            ':medications' => $data['medications'],
            ':doctor_email' => $data['doctor_email'],
            ':doctor_name' => $data['doctor_name'],
            ':email' => $email
        ]);

        $response->getBody()->write(json_encode(['message' => 'Staff record updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    })->add($jwtMiddleware);

    // to clear assigned doctor for a staff by staff_email // unused
    $app->post('/admin/clear-doctor/{email:.+}', function (Request $request, Response $response, $args) {
        
        // $email = str_replace('XYZ', '.', urldecode($args['email']));
        $email = str_replace(['XYZ', 'UVW'], ['.', '+'], urldecode($args['email']));

        $db = new db();
        $pdo = $db->getPDO();

        // Check if staff exists
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_email = :email");
        
        $stmt->execute([':email' => $email]);
        $existingStaff = $stmt->fetch(\PDO::FETCH_ASSOC);

        // If staff not found, return an error
        if ($existingStaff === false || $existingStaff === null) {
            $response->getBody()->write(json_encode(['error' => 'Staff not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Check if medical record exists for the staff
        $stmt = $pdo->prepare("SELECT * FROM medical_record WHERE staff_email = :email");
        
        $stmt->execute([':email' => $email]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        // If medical record not found, return an error
        if ($existingRecord === false || $existingRecord === null) {
            $response->getBody()->write(json_encode(['error' => 'Medical record not found for the specified staff']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Clear assigned doctor in staff record
        $stmt = $pdo->prepare("UPDATE medical_record SET
                                doctor_email = NULL,
                                doctor_name = NULL
                                WHERE staff_email = :email");
        $stmt->execute([
            ':email' => $email
        ]);

        $response->getBody()->write(json_encode(['message' => 'Assigned doctor cleared successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    })->add($jwtMiddleware);

};    