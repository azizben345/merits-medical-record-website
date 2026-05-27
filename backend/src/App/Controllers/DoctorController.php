<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to get doctor info by email
    $app->get('/doctor/info/{email:.+}', function (Request $request, Response $response, $args) {
        $email = str_replace('XYZ', '.', urldecode($args['email']));
        // $email = $args['email'];

        $db = new db();
        $pdo = $db->getPDO();

        if ($email === null || $email === false || trim($email) === '') {
            $response->getBody()->write(json_encode(['error' => 'Invalid email provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $pdo->prepare("SELECT * FROM doctor WHERE doctor_email = :email");
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();
        $doctor = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($doctor === false || $doctor === null)  {
            $response->getBody()->write(json_encode(['error' => 'Doctor not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($doctor));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    })->add($jwtMiddleware);

    // to get doctor list (doctor_email, doctor_name)
    $app->get('/admin/doctor-list', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT doctor_email, doctor_name FROM doctor");
        $stmt->execute();
        $doctorList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($doctorList));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

    // to edit doctor info by doctor_email
    $app->post('/doctor/edit-info/{email:.+}', function (Request $request, Response $response, $args) {
        
        // $doctor_uid = (int)$args['doctor_uid'];  // fetch doctor UID
        $email = str_replace('XYZ', '.', urldecode($args['email']));
        // $user_id = (int)$args['user-id'];  // fetch user ID
        $data = json_decode($request->getBody()->getContents(), true);

        // validate data
        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM doctor WHERE doctor_email = :email");
        $stmt->execute([':email' => $email]);
        $existingDoctor = $stmt->fetch(\PDO::FETCH_ASSOC);

        // If doctor not found, return an error
        if ($existingDoctor === false || $existingDoctor === null) {
            $response->getBody()->write(json_encode(['error' => 'Doctor not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update doctor info (using doctor_uid)
        $stmt = $pdo->prepare("UPDATE doctor SET 
                                doctor_name = :name, 
                                phone_no = :phone_no 
                                WHERE doctor_email = :email");
        $stmt->execute([
            ':name' => $data['doctor_name'],
            ':phone_no' => $data['phone_no'],
            ':email' => $email
        ]);

        // // Update email in users table (to keep it in sync)
        // $stmt = $pdo->prepare("UPDATE users SET 
        //                         email = :email, 
        //                         fullname = :name 
        //                         WHERE user_id = :user_id");
        // $stmt->execute([
        //     ':email' => $data['doctor_email'],
        //     ':name' => $data['doctor_name'],
        //     ':user_id' => $user_id
        // ]);

        // Return success message
        $response->getBody()->write(json_encode(['message' => 'Doctor info updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    })->add($jwtMiddleware);

    // to edit staff record by staff_email // unused
    $app->post('/doctor/edit-record/{email:.+}', function (Request $request, Response $response, $args) {
        
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
        
        $stmt->execute([':email' => $email]);
        $existingStaff = $stmt->fetch(\PDO::FETCH_ASSOC);

        // if staff not found, return an error
        if ($existingStaff === false || $existingStaff === null) {
            $response->getBody()->write(json_encode(['error' => 'Staff not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // check if medical record exists for the staff
        $stmt = $pdo->prepare("SELECT * FROM medical_record WHERE staff_email = :email");
        
        $stmt->execute([':email' => $email]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        // if medical record not found, return an error
        if ($existingRecord === false || $existingRecord === null) {
            $response->getBody()->write(json_encode(['error' => 'Medical record not found for the specified staff']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // update staff record - doctor can update remarks field
        $stmt = $pdo->prepare("UPDATE medical_record SET
                                blood_type = :blood_type, 
                                allergies = :allergies, 
                                existing_conditions = :existing_conditions, 
                                medications = :medications, 
                                doctor_email = :doctor_email,
                                doctor_name = :doctor_name,
                                remarks = :remarks,
                                last_updated = NOW(),
                                updated_by = :email
                                WHERE staff_email = :email");
        $stmt->execute([
            ':blood_type' => $data['blood_type'],
            ':allergies' => $data['allergies'],
            ':existing_conditions' => $data['existing_conditions'],
            ':medications' => $data['medications'],
            ':doctor_email' => $data['doctor_email'],
            ':doctor_name' => $data['doctor_name'],
            ':remarks' => $data['remarks'],
            ':email' => $email
        ]);

        $response->getBody()->write(json_encode(['message' => 'Staff record updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    })->add($jwtMiddleware);

};