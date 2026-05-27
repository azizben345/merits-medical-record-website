<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // PHYSICAL_EXAMS

    // to fetch all physical_exams
    $app->get('/physical-exams', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM physical_exams");
        $physical_exams = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($physical_exams));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // // to fetch a specific physical_exams by staff_email
    // $app->get('/physical-exams/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $stmt = $pdo->prepare("SELECT * FROM physical_exams WHERE staff_email = :staff_email");
    //     $stmt->execute(['staff_email' => $staff_email]);
    //     $physical_exams = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     if (!$physical_exams) {
    //         $response->getBody()->write(json_encode(['error' => 'physical_exams not found.']));
    //         return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //     }

    //     $response->getBody()->write(json_encode($physical_exams));
    //     return $response->withHeader('Content-Type', 'application/json');
    // })->add($jwtMiddleware);

    // // to edit physical_exams by staff_email
    // $app->put('/physical-exams/edit/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $data = json_decode($request->getBody()->getContents(), true);

    //     if ($data === null || $data === false) {
    //         $response->getBody()->write(json_encode(['error' => 'No data provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     $stmt = $pdo->prepare("UPDATE physical_exams 
    //         SET weight_kg = :weight_kg, 
    //             height_m = :height_m, 
    //             bmi = :bmi, 
    //             bp_sys = :bp_sys, 
    //             bp_dia = :bp_dia, 
    //             pulse_bpm = :pulse_bpm, 
    //             blood_group = :blood_group, 
    //             dist_uncorr_r = :dist_uncorr_r, 
    //             dist_uncorr_l = :dist_uncorr_l, 
    //             dist_uncorr_b = :dist_uncorr_b, 
    //             dist_corr_r = :dist_corr_r, 
    //             dist_corr_l = :dist_corr_l, 
    //             dist_corr_b = :dist_corr_b, 
    //             near_uncorr_r = :near_uncorr_r, 
    //             near_uncorr_l = :near_uncorr_l, 
    //             near_uncorr_b = :near_uncorr_b, 
    //             near_corr_r = :near_corr_r, 
    //             near_corr_l = :near_corr_l, 
    //             near_corr_b = :near_corr_b, 
    //             colour_vision = :colour_vision 
    //         WHERE staff_email = :staff_email");
    //     $stmt->execute([
    //         'weight_kg' => $data['weight_kg'] ?? null,
    //         'height_m' => $data['height_m'] ?? null,
    //         'bmi' => $data['bmi'] ?? null,
    //         'bp_sys' => $data['bp_sys'] ?? null,
    //         'bp_dia' => $data['bp_dia'] ?? null,
    //         'pulse_bpm' => $data['pulse_bpm'] ?? null,
    //         'blood_group' => $data['blood_group'] ?? 'Unknown',
    //         'dist_uncorr_r' => $data['dist_uncorr_r'] ?? null,
    //         'dist_uncorr_l' => $data['dist_uncorr_l'] ?? null,
    //         'dist_uncorr_b' => $data['dist_uncorr_b'] ?? null,
    //         'dist_corr_r' => $data['dist_corr_r'] ?? null,
    //         'dist_corr_l' => $data['dist_corr_l'] ?? null,
    //         'dist_corr_b' => $data['dist_corr_b'] ?? null,
    //         'near_uncorr_r' => $data['near_uncorr_r'] ?? null,
    //         'near_uncorr_l' => $data['near_uncorr_l'] ?? null,
    //         'near_uncorr_b' => $data['near_uncorr_b'] ?? null,
    //         'near_corr_r' => $data['near_corr_r'] ?? null,
    //         'near_corr_l' => $data['near_corr_l'] ?? null,
    //         'near_corr_b' => $data['near_corr_b'] ?? null,
    //         'colour_vision' => $data['colour_vision'] ?? null,
    //         'staff_email' => $staff_email
    //     ]);

    //     $response->getBody()->write(json_encode(['message' => 'physical_exams updated successfully']));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    // })->add($jwtMiddleware);

    // PHYSICAL_EXAMS_2:

    // to fetch all physical_exams_2
    $app->get('/physical-exams-2', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM physical_exams_2");
        $physical_exams_2 = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($physical_exams_2));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // // to fetch a specific physical_exams_2 by staff_email
    // $app->get('/physical-exams-2/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $stmt = $pdo->prepare("SELECT * FROM physical_exams_2 WHERE staff_email = :staff_email");
    //     $stmt->execute(['staff_email' => $staff_email]);
    //     $physical_exams_2 = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     if (!$physical_exams_2) {
    //         $response->getBody()->write(json_encode(['error' => 'physical_exams_2 not found.']));
    //         return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //     }

    //     $response->getBody()->write(json_encode($physical_exams_2));
    //     return $response->withHeader('Content-Type', 'application/json');
    // })->add($jwtMiddleware);

    // // to edit physical_exams_2 by staff_email
    // $app->put('/physical-exams-2/edit/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $data = json_decode($request->getBody()->getContents(), true);

    //     if ($data === null || $data === false) {
    //         $response->getBody()->write(json_encode(['error' => 'No data provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     $stmt = $pdo->prepare("UPDATE physical_exams_2 SET 
    //         head = :head, 
    //         head_details_abnormality = :head_details_abnormality, 
    //         eyes = :eyes, 
    //         eyes_details_abnormality = :eyes_details_abnormality, 
    //         ears_and_drums = :ears_and_drums, 
    //         ears_and_drums_details_abnormality = :ears_and_drums_details_abnormality, 
    //         hearing = :hearing, 
    //         hearing_details_abnormality = :hearing_details_abnormality, 
    //         nose_and_sinuses = :nose_and_sinuses, 
    //         nose_and_sinuses_details_abnormality = :nose_and_sinuses_details_abnormality, 
    //         mouth_teeth_throat = :mouth_teeth_throat, 
    //         mouth_teeth_throat_details_abnormality = :mouth_teeth_throat_details_abnormality, 
    //         neck_and_thyroid = :neck_and_thyroid, 
    //         neck_and_thyroid_details_abnormality = :neck_and_thyroid_details_abnormality, 
    //         chest_and_lungs = :chest_and_lungs, 
    //         chest_and_lungs_details_abnormality = :chest_and_lungs_details_abnormality, 
    //         breasts = :breasts, 
    //         breasts_details_abnormality = :breasts_details_abnormality, 
    //         heart = :heart, 
    //         heart_details_abnormality = :heart_details_abnormality, 
    //         peripheral_arteries = :peripheral_arteries, 
    //         peripheral_arteries_details_abnormality = :peripheral_arteries_details_abnormality, 
    //         peripheral_veins = :peripheral_veins, 
    //         peripheral_veins_details_abnormality = :peripheral_veins_details_abnormality, 
    //         abdomen = :abdomen, 
    //         abdomen_details_abnormality = :abdomen_details_abnormality, 
    //         hernia_orifices = :hernia_orifices, 
    //         hernia_orifices_details_abnormality = :hernia_orifices_details_abnormality, 
    //         genitalia = :genitalia, 
    //         genitalia_details_abnormality = :genitalia_details_abnormality, 
    //         rectal_examination = :rectal_examination, 
    //         rectal_examination_details_abnormality = :rectal_examination_details_abnormality, 
    //         upper_limbs = :upper_limbs, 
    //         upper_limbs_details_abnormality = :upper_limbs_details_abnormality, 
    //         lower_limbs = :lower_limbs, 
    //         lower_limbs_details_abnormality = :lower_limbs_details_abnormality, 
    //         spine = :spine, 
    //         spine_details_abnormality = :spine_details_abnormality, 
    //         skin = :skin, 
    //         skin_details_abnormality = :skin_details_abnormality, 
    //         lymph_nodes = :lymph_nodes, 
    //         lymph_nodes_details_abnormality = :lymph_nodes_details_abnormality, 
    //         neurological = :neurological, 
    //         neurological_details_abnormality = :neurological_details_abnormality, 
    //         psychiatric = :psychiatric, 
    //         psychiatric_details_abnormality = :psychiatric_details_abnormality 
    //         WHERE staff_email = :staff_email");
    //     $stmt->execute([
    //         ':head' => $data['head'], 
    //         ':head_details_abnormality' => $data['head_details_abnormality'], 
    //         ':eyes' => $data['eyes'], 
    //         ':eyes_details_abnormality' => $data['eyes_details_abnormality'], 
    //         ':ears_and_drums' => $data['ears_and_drums'], 
    //         ':ears_and_drums_details_abnormality' => $data['ears_and_drums_details_abnormality'], 
    //         ':hearing' => $data['hearing'], 
    //         ':hearing_details_abnormality' => $data['hearing_details_abnormality'], 
    //         ':nose_and_sinuses' => $data['nose_and_sinuses'], 
    //         ':nose_and_sinuses_details_abnormality' => $data['nose_and_sinuses_details_abnormality'], 
    //         ':mouth_teeth_throat' => $data['mouth_teeth_throat'], 
    //         ':mouth_teeth_throat_details_abnormality' => $data['mouth_teeth_throat_details_abnormality'], 
    //         ':neck_and_thyroid' => $data['neck_and_thyroid'], 
    //         ':neck_and_thyroid_details_abnormality' => $data['neck_and_thyroid_details_abnormality'], 
    //         ':chest_and_lungs' => $data['chest_and_lungs'], 
    //         ':chest_and_lungs_details_abnormality' => $data['chest_and_lungs_details_abnormality'], 
    //         ':breasts' => $data['breasts'], 
    //         ':breasts_details_abnormality' => $data['breasts_details_abnormality'], 
    //         ':heart' => $data['heart'], 
    //         ':heart_details_abnormality' => $data['heart_details_abnormality'], 
    //         ':peripheral_arteries' => $data['peripheral_arteries'], 
    //         ':peripheral_arteries_details_abnormality' => $data['peripheral_arteries_details_abnormality'], 
    //         ':peripheral_veins' => $data['peripheral_veins'], 
    //         ':peripheral_veins_details_abnormality' => $data['peripheral_veins_details_abnormality'], 
    //         ':abdomen' => $data['abdomen'], 
    //         ':abdomen_details_abnormality' => $data['abdomen_details_abnormality'], 
    //         ':hernia_orifices' => $data['hernia_orifices'], 
    //         ':hernia_orifices_details_abnormality' => $data['hernia_orifices_details_abnormality'], 
    //         ':genitalia' => $data['genitalia'], 
    //         ':genitalia_details_abnormality' => $data['genitalia_details_abnormality'], 
    //         ':rectal_examination' => $data['rectal_examination'], 
    //         ':rectal_examination_details_abnormality' => $data['rectal_examination_details_abnormality'], 
    //         ':upper_limbs' => $data['upper_limbs'], 
    //         ':upper_limbs_details_abnormality' => $data['upper_limbs_details_abnormality'], 
    //         ':lower_limbs' => $data['lower_limbs'], 
    //         ':lower_limbs_details_abnormality' => $data['lower_limbs_details_abnormality'], 
    //         ':spine' => $data['spine'], 
    //         ':spine_details_abnormality' => $data['spine_details_abnormality'],
    //         ':skin' => $data['skin'], 
    //         ':skin_details_abnormality' => $data['skin_details_abnormality'], 
    //         ':lymph_nodes' => $data['lymph_nodes'], 
    //         ':lymph_nodes_details_abnormality' => $data['lymph_nodes_details_abnormality'], 
    //         ':neurological' => $data['neurological'], 
    //         ':neurological_details_abnormality' => $data['neurological_details_abnormality'], 
    //         ':psychiatric' => $data['psychiatric'], 
    //         ':psychiatric_details_abnormality' => $data['psychiatric_details_abnormality'], 
    //         ':staff_email' => $staff_email
    //     ]);

    //     $response->getBody()->write(json_encode(['message' => 'physical_exams_2 updated successfully']));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    // })->add($jwtMiddleware);

    // to get physical_exams by session_id
    $app->get('/physical-exams/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM physical_exams WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $physicalExams = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($physicalExams));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get physical_exams_2 by session_id
    $app->get('/physical-exams-2/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM physical_exams_2 WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $physicalExams = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($physicalExams));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to edit physical_exams by session_id
    $app->put('/physical-exams/edit/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode($request->getBody()->getContents(), true);
        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if physical_exams record exists
        $stmt = $pdo->prepare("SELECT * FROM physical_exams WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'physical_exams record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update physical_exams record
        $stmt = $pdo->prepare("UPDATE physical_exams 
            SET weight_kg = :weight_kg, 
                height_m = :height_m, 
                bmi = :bmi, 
                bp_sys = :bp_sys, 
                bp_dia = :bp_dia, 
                pulse_bpm = :pulse_bpm, 
                blood_group = :blood_group, 
                dist_uncorr_r = :dist_uncorr_r, 
                dist_uncorr_l = :dist_uncorr_l, 
                dist_uncorr_b = :dist_uncorr_b, 
                dist_corr_r = :dist_corr_r, 
                dist_corr_l = :dist_corr_l, 
                dist_corr_b = :dist_corr_b, 
                near_uncorr_r = :near_uncorr_r, 
                near_uncorr_l = :near_uncorr_l, 
                near_uncorr_b = :near_uncorr_b, 
                near_corr_r = :near_corr_r, 
                near_corr_l = :near_corr_l, 
                near_corr_b = :near_corr_b, 
                colour_vision = :colour_vision 
            WHERE session_id = :session_id");
        $stmt->execute([
            'weight_kg' => $data['weight_kg'] ?? null,
            'height_m' => $data['height_m'] ?? null,
            'bmi' => $data['bmi'] ?? null,
            'bp_sys' => $data['bp_sys'] ?? null,
            'bp_dia' => $data['bp_dia'] ?? null,
            'pulse_bpm' => $data['pulse_bpm'] ?? null,
            'blood_group' => $data['blood_group'] ?? 'Unknown',
            'dist_uncorr_r' => $data['dist_uncorr_r'] ?? null,
            'dist_uncorr_l' => $data['dist_uncorr_l'] ?? null,
            'dist_uncorr_b' => $data['dist_uncorr_b'] ?? null,
            'dist_corr_r' => $data['dist_corr_r'] ?? null,
            'dist_corr_l' => $data['dist_corr_l'] ?? null,
            'dist_corr_b' => $data['dist_corr_b'] ?? null,
            'near_uncorr_r' => $data['near_uncorr_r'] ?? null,
            'near_uncorr_l' => $data['near_uncorr_l'] ?? null,
            'near_uncorr_b' => $data['near_uncorr_b'] ?? null,
            'near_corr_r' => $data['near_corr_r'] ?? null,
            'near_corr_l' => $data['near_corr_l'] ?? null,
            'near_corr_b' => $data['near_corr_b'] ?? null,
            'colour_vision' => $data['colour_vision'] ?? null,
            'session_id' => $args['session_id']
        ]);

        // Update checkup_sessions
        $stmt = $pdo->prepare("UPDATE checkup_sessions SET 
            updated_by = :updated_by, 
            updated_at = NOW() 
            WHERE session_id = :session_id");
        $stmt->bindParam(':updated_by', $data['updated_by'], \PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $args['session_id'], \PDO::PARAM_STR);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Physical exams record updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    })->add($jwtMiddleware);

    // to edit physical_exams_2 by session_id
    $app->put('/physical-exams-2/edit/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode($request->getBody()->getContents(), true);
        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if physical_exams_2 record exists
        $stmt = $pdo->prepare("SELECT * FROM physical_exams_2 WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'physical_exams_2 record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update physical_exams_2 record
        $stmt = $pdo->prepare("UPDATE physical_exams_2 SET 
            head = :head, 
            head_details_abnormality = :head_details_abnormality, 
            eyes = :eyes, 
            eyes_details_abnormality = :eyes_details_abnormality, 
            ears_and_drums = :ears_and_drums, 
            ears_and_drums_details_abnormality = :ears_and_drums_details_abnormality, 
            hearing = :hearing, 
            hearing_details_abnormality = :hearing_details_abnormality, 
            nose_and_sinuses = :nose_and_sinuses, 
            nose_and_sinuses_details_abnormality = :nose_and_sinuses_details_abnormality, 
            mouth_teeth_throat = :mouth_teeth_throat, 
            mouth_teeth_throat_details_abnormality = :mouth_teeth_throat_details_abnormality, 
            neck_and_thyroid = :neck_and_thyroid, 
            neck_and_thyroid_details_abnormality = :neck_and_thyroid_details_abnormality, 
            chest_and_lungs = :chest_and_lungs, 
            chest_and_lungs_details_abnormality = :chest_and_lungs_details_abnormality, 
            breasts = :breasts, 
            breasts_details_abnormality = :breasts_details_abnormality, 
            heart = :heart, 
            heart_details_abnormality = :heart_details_abnormality, 
            peripheral_arteries = :peripheral_arteries, 
            peripheral_arteries_details_abnormality = :peripheral_arteries_details_abnormality, 
            peripheral_veins = :peripheral_veins, 
            peripheral_veins_details_abnormality = :peripheral_veins_details_abnormality, 
            abdomen = :abdomen, 
            abdomen_details_abnormality = :abdomen_details_abnormality, 
            hernia_orifices = :hernia_orifices, 
            hernia_orifices_details_abnormality = :hernia_orifices_details_abnormality, 
            genitalia = :genitalia, 
            genitalia_details_abnormality = :genitalia_details_abnormality, 
            rectal_examination = :rectal_examination, 
            rectal_examination_details_abnormality = :rectal_examination_details_abnormality, 
            upper_limbs = :upper_limbs, 
            upper_limbs_details_abnormality = :upper_limbs_details_abnormality, 
            lower_limbs = :lower_limbs, 
            lower_limbs_details_abnormality = :lower_limbs_details_abnormality, 
            spine = :spine, 
            spine_details_abnormality = :spine_details_abnormality, 
            skin = :skin, 
            skin_details_abnormality = :skin_details_abnormality, 
            lymph_nodes = :lymph_nodes, 
            lymph_nodes_details_abnormality = :lymph_nodes_details_abnormality, 
            neurological = :neurological, 
            neurological_details_abnormality = :neurological_details_abnormality, 
            psychiatric = :psychiatric, 
            psychiatric_details_abnormality = :psychiatric_details_abnormality 
            WHERE session_id = :session_id");
        $stmt->execute([
            ':head' => $data['head'], 
            ':head_details_abnormality' => $data['head_details_abnormality'], 
            ':eyes' => $data['eyes'], 
            ':eyes_details_abnormality' => $data['eyes_details_abnormality'], 
            ':ears_and_drums' => $data['ears_and_drums'], 
            ':ears_and_drums_details_abnormality' => $data['ears_and_drums_details_abnormality'], 
            ':hearing' => $data['hearing'], 
            ':hearing_details_abnormality' => $data['hearing_details_abnormality'], 
            ':nose_and_sinuses' => $data['nose_and_sinuses'], 
            ':nose_and_sinuses_details_abnormality' => $data['nose_and_sinuses_details_abnormality'], 
            ':mouth_teeth_throat' => $data['mouth_teeth_throat'], 
            ':mouth_teeth_throat_details_abnormality' => $data['mouth_teeth_throat_details_abnormality'], 
            ':neck_and_thyroid' => $data['neck_and_thyroid'], 
            ':neck_and_thyroid_details_abnormality' => $data['neck_and_thyroid_details_abnormality'], 
            ':chest_and_lungs' => $data['chest_and_lungs'], 
            ':chest_and_lungs_details_abnormality' => $data['chest_and_lungs_details_abnormality'], 
            ':breasts' => $data['breasts'], 
            ':breasts_details_abnormality' => $data['breasts_details_abnormality'], 
            ':heart' => $data['heart'], 
            ':heart_details_abnormality' => $data['heart_details_abnormality'], 
            ':peripheral_arteries' => $data['peripheral_arteries'], 
            ':peripheral_arteries_details_abnormality' => $data['peripheral_arteries_details_abnormality'], 
            ':peripheral_veins' => $data['peripheral_veins'], 
            ':peripheral_veins_details_abnormality' => $data['peripheral_veins_details_abnormality'], 
            ':abdomen' => $data['abdomen'], 
            ':abdomen_details_abnormality' => $data['abdomen_details_abnormality'], 
            ':hernia_orifices' => $data['hernia_orifices'], 
            ':hernia_orifices_details_abnormality' => $data['hernia_orifices_details_abnormality'], 
            ':genitalia' => $data['genitalia'], 
            ':genitalia_details_abnormality' => $data['genitalia_details_abnormality'], 
            ':rectal_examination' => $data['rectal_examination'], 
            ':rectal_examination_details_abnormality' => $data['rectal_examination_details_abnormality'], 
            ':upper_limbs' => $data['upper_limbs'], 
            ':upper_limbs_details_abnormality' => $data['upper_limbs_details_abnormality'], 
            ':lower_limbs' => $data['lower_limbs'], 
            ':lower_limbs_details_abnormality' => $data['lower_limbs_details_abnormality'], 
            ':spine' => $data['spine'], 
            ':spine_details_abnormality' => $data['spine_details_abnormality'],
            ':skin' => $data['skin'], 
            ':skin_details_abnormality' => $data['skin_details_abnormality'], 
            ':lymph_nodes' => $data['lymph_nodes'], 
            ':lymph_nodes_details_abnormality' => $data['lymph_nodes_details_abnormality'], 
            ':neurological' => $data['neurological'], 
            ':neurological_details_abnormality' => $data['neurological_details_abnormality'], 
            ':psychiatric' => $data['psychiatric'], 
            ':psychiatric_details_abnormality' => $data['psychiatric_details_abnormality'], 
            ':session_id' => $args['session_id'],
        ]);

        // Update checkup_sessions
        $stmt = $pdo->prepare("UPDATE checkup_sessions SET 
            updated_by = :updated_by, 
            updated_at = NOW() 
            WHERE session_id = :session_id");
        $stmt->bindParam(':updated_by', $data['updated_by'], \PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $args['session_id'], \PDO::PARAM_STR);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'physical_exams_2 record updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

};