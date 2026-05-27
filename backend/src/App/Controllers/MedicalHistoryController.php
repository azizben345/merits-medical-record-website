<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to get all medical history
    $app->get('/medical-history', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM medical_history");
        $medicalHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($medicalHistory));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // // to get medical history by email
    // $app->get('/medical-history/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $stmt = $pdo->prepare("SELECT * FROM medical_history WHERE staff_email = :staff_email");
    //     $stmt->execute([':staff_email' => $staffEmail]);
    //     $medicalHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     $response->getBody()->write(json_encode($medicalHistory));
    //     return $response->withHeader('Content-Type', 'application/json');
    // })->add($jwtMiddleware);

    // // to edit medical history
    // $app->put('/medical-history/edit/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $data = json_decode($request->getBody()->getContents(), true);

    //     if ($data === null || $data === false) {
    //         $response->getBody()->write(json_encode(['error' => 'No data provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     // Check if medical history record exists
    //     $stmt = $pdo->prepare("SELECT * FROM medical_history WHERE staff_email = :staff_email");
    //     $stmt->execute([':staff_email' => $staffEmail]);
    //     $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

    //     if ($existingRecord === false || $existingRecord === null)  {
    //         $response->getBody()->write(json_encode(['error' => 'Medical history record not found']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    //     }

    //     // Update medical history record
    //     $stmt = $pdo->prepare("UPDATE medical_history SET abnormal_heartbeat = :abnormal_heartbeat, bladder_trouble = :bladder_trouble, 
    //                             dermatitis_eczema = :dermatitis_eczema, depression = :depression, heart_murmur = :heart_murmur, 
    //                             hernia = :hernia, jaundice = :jaundice, kidney_disease = :kidney_disease, 
    //                             peptic_ulcer = :peptic_ulcer, persistent_night_sweats = :persistent_night_sweats, 
    //                             rectal_bleeding = :rectal_bleeding, unintentional_weight_loss = :unintentional_weight_loss, 
    //                             asthma_bronchitis = :asthma_bronchitis, bowel_disorder = :bowel_disorder, diabetes = :diabetes, 
    //                             frequent_indigestion = :frequent_indigestion, high_blood_pressure = :high_blood_pressure, 
    //                             hospitalisation_surgery = :hospitalisation_surgery, migraine_headache = :migraine_headache, 
    //                             psoriasis_skin_disease = :psoriasis_skin_disease, persistent_diarrhoea = :persistent_diarrhoea, 
    //                             renal_colic_stone = :renal_colic_stone, swollen_lymph_glands = :swollen_lymph_glands, anxiety = :anxiety, 
    //                             blood_in_urine = :blood_in_urine, dizziness_giddiness = :dizziness_giddiness, faints_blackouts = :faints_blackouts, 
    //                             hay_fever = :hay_fever, joint_disorder = :joint_disorder, liver_gall_bladder = :liver_gall_bladder, 
    //                             piles_haemorrhoids = :piles_haemorrhoids, rheumatic_fever = :rheumatic_fever, std = :std, tuberculosis = :tuberculosis, 
    //                             none_of_the_above = :none_of_the_above, comment_by_examine_doctor = :comment_by_examine_doctor 
    //                             WHERE staff_email = :staff_email");
    //     $stmt->execute([
    //         ':abnormal_heartbeat' => $data['abnormal_heartbeat'],
    //         ':bladder_trouble' => $data['bladder_trouble'],
    //         ':dermatitis_eczema' => $data['dermatitis_eczema'],
    //         ':depression' => $data['depression'],
    //         ':heart_murmur' => $data['heart_murmur'],
    //         ':hernia' => $data['hernia'],
    //         ':jaundice' => $data['jaundice'],
    //         ':kidney_disease' => $data['kidney_disease'],
    //         ':peptic_ulcer' => $data['peptic_ulcer'],
    //         ':persistent_night_sweats' => $data['persistent_night_sweats'],
    //         ':rectal_bleeding' => $data['rectal_bleeding'],
    //         ':unintentional_weight_loss' => $data['unintentional_weight_loss'],
    //         ':asthma_bronchitis' => $data['asthma_bronchitis'],
    //         ':bowel_disorder' => $data['bowel_disorder'],
    //         ':diabetes' => $data['diabetes'],
    //         ':frequent_indigestion' => $data['frequent_indigestion'],
    //         ':high_blood_pressure' => $data['high_blood_pressure'],
    //         ':hospitalisation_surgery' => $data['hospitalisation_surgery'],
    //         ':migraine_headache' => $data['migraine_headache'],
    //         ':psoriasis_skin_disease' => $data['psoriasis_skin_disease'],
    //         ':persistent_diarrhoea' => $data['persistent_diarrhoea'],
    //         ':renal_colic_stone' => $data['renal_colic_stone'],
    //         ':swollen_lymph_glands' => $data['swollen_lymph_glands'],
    //         ':anxiety' => $data['anxiety'],
    //         ':blood_in_urine' => $data['blood_in_urine'],
    //         ':dizziness_giddiness' => $data['dizziness_giddiness'],
    //         ':faints_blackouts' => $data['faints_blackouts'],
    //         ':hay_fever' => $data['hay_fever'],
    //         ':joint_disorder' => $data['joint_disorder'],
    //         ':liver_gall_bladder' => $data['liver_gall_bladder'],
    //         ':piles_haemorrhoids' => $data['piles_haemorrhoids'],
    //         ':rheumatic_fever' => $data['rheumatic_fever'],
    //         ':std' => $data['std'],
    //         ':tuberculosis' => $data['tuberculosis'],
    //         ':none_of_the_above' => $data['none_of_the_above'],
    //         ':comment_by_examine_doctor' => $data['comment_by_examine_doctor'],
    //         ':staff_email' => $staffEmail
    //     ]);

    //     $response->getBody()->write(json_encode(['message' => 'Medical history updated successfully']));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    // })->add($jwtMiddleware);

    // to get medical history by session_id
    $app->get('/medical-history/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM medical_history WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $medicalHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($medicalHistory));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to edit the medical history by session_id
    $app->put('/medical-history/edit/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode($request->getBody()->getContents(), true);
        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if medical history record exists
        $stmt = $pdo->prepare("SELECT * FROM medical_history WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'Medical history record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update medical history record
        $stmt = $pdo->prepare("UPDATE medical_history SET 
            abnormal_heartbeat = :abnormal_heartbeat,
            bladder_trouble = :bladder_trouble,
            dermatitis_eczema = :dermatitis_eczema,
            depression = :depression,
            heart_murmur = :heart_murmur,
            hernia = :hernia,
            jaundice = :jaundice,
            kidney_disease = :kidney_disease,
            peptic_ulcer = :peptic_ulcer,
            persistent_night_sweats = :persistent_night_sweats,
            rectal_bleeding = :rectal_bleeding,
            unintentional_weight_loss = :unintentional_weight_loss,
            asthma_bronchitis = :asthma_bronchitis,
            bowel_disorder = :bowel_disorder,
            diabetes = :diabetes,
            frequent_indigestion = :frequent_indigestion,
            high_blood_pressure = :high_blood_pressure,
            hospitalisation_surgery = :hospitalisation_surgery,
            migraine_headache = :migraine_headache,
            psoriasis_skin_disease = :psoriasis_skin_disease,
            persistent_diarrhoea = :persistent_diarrhoea,
            renal_colic_stone = :renal_colic_stone,
            swollen_lymph_glands = :swollen_lymph_glands,
            anxiety = :anxiety,
            blood_in_urine = :blood_in_urine,
            dizziness_giddiness = :dizziness_giddiness,
            faints_blackouts = :faints_blackouts,
            hay_fever = :hay_fever,
            joint_disorder = :joint_disorder,
            liver_gall_bladder = :liver_gall_bladder,
            piles_haemorrhoids = :piles_haemorrhoids,
            rheumatic_fever = :rheumatic_fever,
            std = :std,
            tuberculosis = :tuberculosis,
            none_of_the_above = :none_of_the_above,
            comment_by_examine_doctor = :comment_by_examine_doctor
            WHERE session_id = :session_id");

        $stmt->execute([
            ':abnormal_heartbeat' => $data['abnormal_heartbeat'],
            ':bladder_trouble' => $data['bladder_trouble'],
            ':dermatitis_eczema' => $data['dermatitis_eczema'],
            ':depression' => $data['depression'],
            ':heart_murmur' => $data['heart_murmur'],
            ':hernia' => $data['hernia'],
            ':jaundice' => $data['jaundice'],
            ':kidney_disease' => $data['kidney_disease'],
            ':peptic_ulcer' => $data['peptic_ulcer'],
            ':persistent_night_sweats' => $data['persistent_night_sweats'],
            ':rectal_bleeding' => $data['rectal_bleeding'],
            ':unintentional_weight_loss' => $data['unintentional_weight_loss'],
            ':asthma_bronchitis' => $data['asthma_bronchitis'],
            ':bowel_disorder' => $data['bowel_disorder'],
            ':diabetes' => $data['diabetes'],
            ':frequent_indigestion' => $data['frequent_indigestion'],
            ':high_blood_pressure' => $data['high_blood_pressure'],
            ':hospitalisation_surgery' => $data['hospitalisation_surgery'],
            ':migraine_headache' => $data['migraine_headache'],
            ':psoriasis_skin_disease' => $data['psoriasis_skin_disease'],
            ':persistent_diarrhoea' => $data['persistent_diarrhoea'],
            ':renal_colic_stone' => $data['renal_colic_stone'],
            ':swollen_lymph_glands' => $data['swollen_lymph_glands'],
            ':anxiety' => $data['anxiety'],
            ':blood_in_urine' => $data['blood_in_urine'],
            ':dizziness_giddiness' => $data['dizziness_giddiness'],
            ':faints_blackouts' => $data['faints_blackouts'],
            ':hay_fever' => $data['hay_fever'],
            ':joint_disorder' => $data['joint_disorder'],
            ':liver_gall_bladder' => $data['liver_gall_bladder'],
            ':piles_haemorrhoids' => $data['piles_haemorrhoids'],
            ':rheumatic_fever' => $data['rheumatic_fever'],
            ':std' => $data['std'],
            ':tuberculosis' => $data['tuberculosis'],
            ':none_of_the_above' => $data['none_of_the_above'],
            ':comment_by_examine_doctor' => $data['comment_by_examine_doctor'],
            ':session_id' => $args['session_id']
        ]);

        // Update checkup_sessions
        $stmt = $pdo->prepare("UPDATE checkup_sessions SET 
            updated_by = :updated_by, 
            updated_at = NOW() 
            WHERE session_id = :session_id");
        $stmt->bindParam(':updated_by', $data['staff_email'], \PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $args['session_id'], \PDO::PARAM_STR);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Medical history record updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

};