<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to get all lifestyle
    $app->get('/lifestyle', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM lifestyle");
        $lifestyle = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($lifestyle));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // // to get lifestyle by email
    // $app->get('/lifestyle/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $stmt = $pdo->prepare("SELECT * FROM lifestyle WHERE staff_email = :staff_email");
    //     $stmt->execute([':staff_email' => $staffEmail]);
    //     $lifestyle = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     $response->getBody()->write(json_encode($lifestyle));
    //     return $response->withHeader('Content-Type', 'application/json');
    // })->add($jwtMiddleware);

    // // to edit lifestyle
    // $app->put('/lifestyle/edit/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $data = json_decode($request->getBody()->getContents(), true);
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     if ($data === null || $data === false) {
    //         $response->getBody()->write(json_encode(['error' => 'No data provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     // Check if lifestyle record exists
    //     $stmt = $pdo->prepare("SELECT * FROM lifestyle WHERE staff_email = :staff_email");
    //     $stmt->execute([':staff_email' => $staffEmail]);
    //     $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

    //     if ($existingRecord === false || $existingRecord === null)  {
    //         $response->getBody()->write(json_encode(['error' => 'Lifestyle record not found']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    //     }

    //     // Update lifestyle record
    //     $stmt = $pdo->prepare("UPDATE lifestyle SET 
    //         staff_name_snapshot = :staff_name_snapshot, 
    //         smoking_habit = :smoking_habit, 
    //         years_smoked = :years_smoked, 
    //         amount_smoke_day = :amount_smoke_day, 
    //         date_stopped = :date_stopped, 
    //         alcohol_drink = :alcohol_drink, 
    //         drink_per_week = :drink_per_week, 
    //         taking_prescribed_drugs = :taking_prescribed_drugs, 
    //         drug_detail = :drug_detail, 
    //         declaration_consent = :declaration_consent, 
    //         consent_signer_name = :consent_signer_name, 
    //         consent_signer_date = :consent_signer_date 
    //         WHERE staff_email = :staff_email");
    //     $stmt->execute([
    //         ':staff_name_snapshot' => $data['staff_name_snapshot'],
    //         ':smoking_habit' => $data['smoking_habit'],
    //         ':years_smoked' => $data['years_smoked'],
    //         ':amount_smoke_day' => $data['amount_smoke_day'],
    //         ':date_stopped' => $data['date_stopped'],
    //         ':alcohol_drink' => $data['alcohol_drink'],
    //         ':drink_per_week' => $data['drink_per_week'],
    //         ':taking_prescribed_drugs' => $data['taking_prescribed_drugs'],
    //         ':drug_detail' => $data['drug_detail'],
    //         ':declaration_consent' => $data['declaration_consent'],
    //         ':consent_signer_name' => $data['consent_signer_name'],
    //         ':consent_signer_date' => $data['consent_signer_date'],
    //         ':staff_email' => $staffEmail
    //     ]);

    //     $response->getBody()->write(json_encode(['message' => 'Lifestyle updated successfully']));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    // })->add($jwtMiddleware);

    // to get lifestyle by session_id
    $app->get('/lifestyle/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM lifestyle WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $lifestyle = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($lifestyle));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to edit lifestyle by session_id
    $app->put('/lifestyle/edit/{session_id}', function (Request $request, Response $response, $args) {
        $data = json_decode($request->getBody()->getContents(), true);
        $db = new db();
        $pdo = $db->getPDO();

        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if lifestyle record exists
        $stmt = $pdo->prepare("SELECT * FROM lifestyle WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'Lifestyle record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update lifestyle record
        $stmt = $pdo->prepare("UPDATE lifestyle SET 
            staff_name_snapshot = :staff_name_snapshot, 
            smoking_habit = :smoking_habit, 
            years_smoked = :years_smoked, 
            amount_smoke_day = :amount_smoke_day, 
            date_stopped = :date_stopped, 
            alcohol_drink = :alcohol_drink, 
            drink_per_week = :drink_per_week, 
            taking_prescribed_drugs = :taking_prescribed_drugs, 
            drug_detail = :drug_detail, 
            declaration_consent = :declaration_consent, 
            consent_signer_name = :consent_signer_name, 
            consent_signer_date = :consent_signer_date 
            WHERE session_id = :session_id");
        $stmt->execute([
            ':staff_name_snapshot' => $data['staff_name_snapshot'],
            ':smoking_habit' => $data['smoking_habit'],
            ':years_smoked' => $data['years_smoked'],
            ':amount_smoke_day' => $data['amount_smoke_day'],
            ':date_stopped' => $data['date_stopped'],
            ':alcohol_drink' => $data['alcohol_drink'],
            ':drink_per_week' => $data['drink_per_week'],
            ':taking_prescribed_drugs' => $data['taking_prescribed_drugs'],
            ':drug_detail' => $data['drug_detail'],
            ':declaration_consent' => $data['declaration_consent'],
            ':consent_signer_name' => $data['consent_signer_name'],
            ':consent_signer_date' => $data['consent_signer_date'],
            ':session_id' => $args['session_id']
        ]);

        // Update checkup_sessions
        $stmt = $pdo->prepare("UPDATE checkup_sessions SET 
            updated_by = :updated_by, 
            updated_at = NOW() 
            WHERE session_id = :session_id");
        $stmt->bindParam(':updated_by', $data['updated_by'], \PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $args['session_id'], \PDO::PARAM_STR);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Lifestyle updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

};