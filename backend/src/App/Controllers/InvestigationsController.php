<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to fetch all investigations
    $app->get('/investigations', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM investigations");
        $investigations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($investigations));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // // to fetch a specific investigation by staff_email
    // $app->get('/investigations/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $stmt = $pdo->prepare("SELECT * FROM investigations WHERE staff_email = :staff_email");
    //     $stmt->execute(['staff_email' => $staff_email]);
    //     $investigations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     if (!$investigations) {
    //         $response->getBody()->write(json_encode(['error' => 'investigations not found.']));
    //         return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //     }

    //     $response->getBody()->write(json_encode($investigations));
    //     return $response->withHeader('Content-Type', 'application/json');
    // })->add($jwtMiddleware);

    // // to edit investigations by staff_email
    // $app->put('/investigations/edit/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $data = json_decode($request->getBody()->getContents(), true);

    //     if ($data === null || $data === false) {
    //         $response->getBody()->write(json_encode(['error' => 'No data provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     // Check if staff record exists
    //     $stmt = $pdo->prepare("SELECT * FROM investigations WHERE staff_email = :staff_email");
    //     $stmt->execute(['staff_email' => $staff_email]);
    //     $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

    //     if ($existingRecord === false || $existingRecord === null)  {
    //         $response->getBody()->write(json_encode(['error' => 'investigations record not found']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    //     }

    //     // Update staff record
    //     $stmt = $pdo->prepare("UPDATE investigations SET 
    //         spirometry_status = :spirometry_status, 
    //         spirometry_details = :spirometry_details, 
    //         audiometry_status = :audiometry_status, 
    //         audiometry_details = :audiometry_details, 
    //         chest_xray_status = :chest_xray_status, 
    //         chest_xray_details = :chest_xray_details, 
    //         electrocardiograph_status = :electrocardiograph_status, 
    //         electrocardiograph_details = :electrocardiograph_details, 
    //         opiates_result = :opiates_result, 
    //         opiates_remark = :opiates_remark, 
    //         cannabinoids_result = :cannabinoids_result, 
    //         cannabinoids_remark = :cannabinoids_remark, 
    //         amphetamine_result = :amphetamine_result, 
    //         amphetamine_remark = :amphetamine_remark, 
    //         mdma_result = :mdma_result, 
    //         mdma_remark = :mdma_remark, 
    //         benzodiazepine_result = :benzodiazepine_result, 
    //         benzodiazepine_remark = :benzodiazepine_remark, 
    //         remarks_ohd = :remarks_ohd 
    //         WHERE staff_email = :staff_email");
    //     $stmt->bindParam(':spirometry_status', $data['spirometry_status'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':spirometry_details', $data['spirometry_details'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':audiometry_status', $data['audiometry_status'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':audiometry_details', $data['audiometry_details'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':chest_xray_status', $data['chest_xray_status'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':chest_xray_details', $data['chest_xray_details'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':electrocardiograph_status', $data['electrocardiograph_status'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':electrocardiograph_details', $data['electrocardiograph_details'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':opiates_result', $data['opiates_result'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':opiates_remark', $data['opiates_remark'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':cannabinoids_result', $data['cannabinoids_result'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':cannabinoids_remark', $data['cannabinoids_remark'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':amphetamine_result', $data['amphetamine_result'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':amphetamine_remark', $data['amphetamine_remark'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':mdma_result', $data['mdma_result'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':mdma_remark', $data['mdma_remark'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':benzodiazepine_result', $data['benzodiazepine_result'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':benzodiazepine_remark', $data['benzodiazepine_remark'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':remarks_ohd', $data['remarks_ohd'], \PDO::PARAM_STR);
    //     $stmt->bindParam(':staff_email', $staff_email, \PDO::PARAM_STR);
    //     $stmt->execute();

    //     $response->getBody()->write(json_encode(['message' => 'investigations record updated successfully']));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    // })->add($jwtMiddleware);

    // // to fetch all investigations_lab
    // $app->get('/investigations-lab', function (Request $request, Response $response) {
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $stmt = $pdo->query("SELECT * FROM investigations_lab");
    //     $investigations_lab = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     $response->getBody()->write(json_encode($investigations_lab));
    //     return $response->withHeader('Content-Type', 'application/json');
    // })->add($jwtMiddleware);

    // // to fetch a specific investigations_lab by staff_email
    // $app->get('/investigations-lab/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db = new db();
    //     $pdo = $db->getPDO();

    //     $stmt = $pdo->prepare("SELECT * FROM investigations_lab WHERE staff_email = :staff_email");
    //     $stmt->execute(['staff_email' => $staff_email]);
    //     $investigations_lab = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     if (!$investigations_lab) {
    //         $response->getBody()->write(json_encode(['error' => 'investigations_lab not found.']));
    //         return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //     }

    //     $response->getBody()->write(json_encode($investigations_lab));
    //     return $response->withHeader('Content-Type', 'application/json');
    // });//->add($jwtMiddleware);

    // // to edit investigations_lab by staff_email
    // $app->put('/investigations-lab/edit/{staff_email}', function (Request $request, Response $response, $args) {
    //     $staff_email = str_replace('XYZ', '.', urldecode($args['staff_email']));
    //     $db  = new db();
    //     $pdo = $db->getPDO();

    //     $data = json_decode($request->getBody()->getContents(), true);
    //     if ($data === null || $data === false) {
    //         $response->getBody()->write(json_encode(['error' => 'No data provided']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    //     }

    //     // Whitelist of all lab analytes (prefix before _result/_remark)
    //     $fields = [
    //         // Haematology
    //         'hb','rbc','pcv','mcv','mch','mchc','rdw','wbc','neut','lym','mon','eon','bas','plet','esr','fbp',
    //         // Glucose
    //         'fbs','rbs',
    //         // Lipids
    //         'tchol','tg','hdl','ldl',
    //         // Electrolytes
    //         'na','k','cl',
    //         // Renal
    //         'bu','creat','ua','ca','cca','po4',
    //         // Liver
    //         'tprot','alb','glo','agr','alkp','tbil','ggt','ast','alt',
    //         // Urinalysis chemistry
    //         'uprot','uph','uglu','uket','usg','ubld',
    //         // Urinalysis microscopy
    //         'uleu','uery','uecell','ucc',
    //         // Serology
    //         'vdrl','hbsag','hbsab','hcs',
    //     ];

    //     // Allowed enums for *_result
    //     $allowedResults = ['Normal','Abnormal','Not done'];

    //     // Load existing row (for partial updates)
    //     $stmt = $pdo->prepare("SELECT * FROM investigations_lab WHERE staff_email = :staff_email");
    //     $stmt->execute(['staff_email' => $staff_email]);
    //     $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

    //     if (!$existing) {
    //         $response->getBody()->write(json_encode(['error' => 'investigations_lab record not found']));
    //         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    //     }

    //     // build SET clause + params from whitelist
    //     $setParts = [];
    //     $params   = ['staff_email' => $staff_email];

    //     foreach ($fields as $f) {
    //         $resKey = "{$f}_result";
    //         $remKey = "{$f}_remark";

    //         // decide final values (use input if provided, else keep existing)
    //         $resVal = array_key_exists($resKey, $data) ? $data[$resKey] : $existing[$resKey];
    //         $remVal = array_key_exists($remKey, $data) ? $data[$remKey] : $existing[$remKey];

    //         // normalize *_result to allowed enum (or default to 'Not done' if null/invalid)
    //         if ($resVal === null || $resVal === '') {
    //             $resVal = $existing[$resKey] ?? 'Not done';
    //         } else {
    //             // be forgiving to case; trim spaces
    //             $resVal = trim((string)$resVal);
    //             $title  = ucfirst(strtolower($resVal)); // "normal"->"Normal"
    //             if (!in_array($resVal, $allowedResults, true) && !in_array($title, $allowedResults, true)) {
    //                 $resVal = $existing[$resKey] ?? 'Not done';
    //             } else {
    //                 $resVal = in_array($resVal, $allowedResults, true) ? $resVal : $title;
    //             }
    //         }

    //         $setParts[] = "$resKey = :$resKey";
    //         $setParts[] = "$remKey = :$remKey";
    //         $params[$resKey] = $resVal;
    //         $params[$remKey] = ($remVal === '' ? null : $remVal);
    //     }

    //     $sql = "UPDATE investigations_lab SET " . implode(', ', $setParts) . " WHERE staff_email = :staff_email";
    //     $stmt = $pdo->prepare($sql);
    //     $stmt->execute($params);

    //     $response->getBody()->write(json_encode(['message' => 'investigations_lab record updated successfully']));
    //     return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    // })->add($jwtMiddleware);

    // to get investigation by session_id
    $app->get('/investigations/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM investigations WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $investigations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($investigations));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get investigations_lab by session_id
    $app->get('/investigations-lab/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM investigations_lab WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $investigations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($investigations));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to edit investigations_lab by session_id
    $app->put('/investigations-lab/edit/{session_id}', function (Request $request, Response $response, $args) {
        $db  = new db();
        $pdo = $db->getPDO();

        $data = json_decode($request->getBody()->getContents(), true);
        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Whitelist of all lab analytes (prefix before _result/_remark)
        $fields = [
            // haematology
            'hb','rbc','pcv','mcv','mch','mchc','rdw','wbc','neut','lym','mon','eon','bas','plet','esr','fbp',
            // Glucose
            'fbs','rbs',
            // Lipids
            'tchol','tg','hdl','ldl',
            // Electrolytes
            'na','k','cl',
            // Renal
            'bu','creat','ua','ca','cca','po4',
            // Liver
            'tprot','alb','glo','agr','alkp','tbil','ggt','ast','alt',
            // Urinalysis chemistry
            'uprot','uph','uglu','uket','usg','ubld',
            // Urinalysis microscopy
            'uleu','uery','uecell','ucc',
            // Serology
            'vdrl','hbsag','hbsab','hcs',
        ];

        // Allowed enums for *_result
        $allowedResults = ['Normal','Abnormal','Not done'];

        // Load existing row (for partial updates)
        $stmt = $pdo->prepare("SELECT * FROM investigations_lab WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            $response->getBody()->write(json_encode(['error' => 'investigations_lab record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // build SET clause + params from whitelist
        $setParts = [];
        $params   = ['session_id' => $args['session_id']];

        foreach ($fields as $f) {
            $resKey = "{$f}_result";
            $remKey = "{$f}_remark";
            $valKey = "{$f}_value";

            // 1. Handle RESULT (Always exists)
            $resVal = array_key_exists($resKey, $data) ? $data[$resKey] : $existing[$resKey];
            
            // Normalize Enum Logic...
            if ($resVal === null || $resVal === '') {
                $resVal = $existing[$resKey] ?? 'Not done';
            } else {
                $resVal = trim((string)$resVal);
                $title  = ucfirst(strtolower($resVal)); 
                if (!in_array($resVal, $allowedResults, true) && !in_array($title, $allowedResults, true)) {
                    $resVal = $existing[$resKey] ?? 'Not done';
                } else {
                    $resVal = in_array($resVal, $allowedResults, true) ? $resVal : $title;
                }
            }
            
            $setParts[] = "$resKey = :$resKey";
            $params[$resKey] = $resVal;

            // 2. Handle REMARK (Always exists)
            $remVal = array_key_exists($remKey, $data) ? $data[$remKey] : $existing[$remKey];
            $setParts[] = "$remKey = :$remKey";
            $params[$remKey] = ($remVal === '' ? null : $remVal);

            // 3. Handle VALUE (Dynamic Check!)
            // Only add this to the UPDATE query if the column actually exists in the DB
            if (array_key_exists($valKey, $existing)) {
                $valVal = array_key_exists($valKey, $data) ? $data[$valKey] : $existing[$valKey];
                
                $setParts[] = "$valKey = :$valKey";
                $params[$valKey] = ($valVal === '' ? null : $valVal);
            }
        }

        $sql = "UPDATE investigations_lab SET " . implode(', ', $setParts) . " WHERE session_id = :session_id";
        // update chekup_sessions
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $stmt = $pdo->prepare("UPDATE checkup_sessions SET updated_by = :updated_by, updated_at = NOW() WHERE session_id = :session_id");
            $stmt->execute([':updated_by' => $data['updated_by'], ':session_id' => $args['session_id']]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $response->getBody()->write(json_encode(['message' => 'investigations_lab record updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

    // to edit investigations by session_id
    $app->put('/investigations/edit/{session_id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode($request->getBody()->getContents(), true);

        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if investigations record exists
        $stmt = $pdo->prepare("SELECT * FROM investigations WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $args['session_id']]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'investigations record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update investigations record
        $stmt = $pdo->prepare("UPDATE investigations SET 
            spirometry_status = :spirometry_status, 
            spirometry_details = :spirometry_details, 
            audiometry_status = :audiometry_status, 
            audiometry_details = :audiometry_details, 
            chest_xray_status = :chest_xray_status, 
            chest_xray_details = :chest_xray_details, 
            electrocardiograph_status = :electrocardiograph_status, 
            electrocardiograph_details = :electrocardiograph_details, 
            opiates_result = :opiates_result, 
            opiates_remark = :opiates_remark, 
            cannabinoids_result = :cannabinoids_result, 
            cannabinoids_remark = :cannabinoids_remark, 
            amphetamine_result = :amphetamine_result, 
            amphetamine_remark = :amphetamine_remark, 
            mdma_result = :mdma_result, 
            mdma_remark = :mdma_remark, 
            benzodiazepine_result = :benzodiazepine_result, 
            benzodiazepine_remark = :benzodiazepine_remark, 
            remarks_ohd = :remarks_ohd 
            WHERE session_id = :session_id");
        $stmt->bindParam(':spirometry_status', $data['spirometry_status'], \PDO::PARAM_STR);
        $stmt->bindParam(':spirometry_details', $data['spirometry_details'], \PDO::PARAM_STR);
        $stmt->bindParam(':audiometry_status', $data['audiometry_status'], \PDO::PARAM_STR);
        $stmt->bindParam(':audiometry_details', $data['audiometry_details'], \PDO::PARAM_STR);
        $stmt->bindParam(':chest_xray_status', $data['chest_xray_status'], \PDO::PARAM_STR);
        $stmt->bindParam(':chest_xray_details', $data['chest_xray_details'], \PDO::PARAM_STR);
        $stmt->bindParam(':electrocardiograph_status', $data['electrocardiograph_status'], \PDO::PARAM_STR);
        $stmt->bindParam(':electrocardiograph_details', $data['electrocardiograph_details'], \PDO::PARAM_STR);
        $stmt->bindParam(':opiates_result', $data['opiates_result'], \PDO::PARAM_STR);
        $stmt->bindParam(':opiates_remark', $data['opiates_remark'], \PDO::PARAM_STR);
        $stmt->bindParam(':cannabinoids_result', $data['cannabinoids_result'], \PDO::PARAM_STR);
        $stmt->bindParam(':cannabinoids_remark', $data['cannabinoids_remark'], \PDO::PARAM_STR);
        $stmt->bindParam(':amphetamine_result', $data['amphetamine_result'], \PDO::PARAM_STR);
        $stmt->bindParam(':amphetamine_remark', $data['amphetamine_remark'], \PDO::PARAM_STR);
        $stmt->bindParam(':mdma_result', $data['mdma_result'], \PDO::PARAM_STR);
        $stmt->bindParam(':mdma_remark', $data['mdma_remark'], \PDO::PARAM_STR);
        $stmt->bindParam(':benzodiazepine_result', $data['benzodiazepine_result'], \PDO::PARAM_STR);
        $stmt->bindParam(':benzodiazepine_remark', $data['benzodiazepine_remark'], \PDO::PARAM_STR);
        $stmt->bindParam(':remarks_ohd', $data['remarks_ohd'], \PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $args['session_id'], \PDO::PARAM_STR);
        $stmt->execute();

        // Update checkup_sessions
        $stmt = $pdo->prepare("UPDATE checkup_sessions SET updated_by = :updated_by, updated_at = NOW() WHERE session_id = :session_id");
        $stmt->bindParam(':updated_by', $data['updated_by'], \PDO::PARAM_STR);
        $stmt->bindParam(':session_id', $args['session_id'], \PDO::PARAM_STR);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'investigations record updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

};