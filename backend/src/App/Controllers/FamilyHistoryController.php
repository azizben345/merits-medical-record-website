<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to get all family history
    // to get family history by email (and inline-fix age_now)
    $app->get('/family-history/{staff_email}', function (Request $request, Response $response, $args) {
        $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
        $pdo = (new db())->getPDO();

        // 1) Fetch family history rows
        $stmt = $pdo->prepare("SELECT * FROM family_history WHERE staff_email = :staff_email");
        $stmt->execute([':staff_email' => $staffEmail]);
        $familyHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2) Best-effort inline refresh of age_now based on year_of_born
        //    - only if still alive (age_at_death is NULL)
        //    - simple year-based calc since schema stores only a year
        $nowYear = (int)date('Y');
        $upd = $pdo->prepare("UPDATE family_history SET age_now = :age WHERE fh_id = :id");

        foreach ($familyHistory as &$row) {
            $fhId = $row['fh_id'] ?? null; // adjust pk name if different
            $yob  = isset($row['year_of_born']) ? (int)$row['year_of_born'] : null;
            $isDeceased = !empty($row['age_at_death']) || $row['age_at_death'] === 0; // treat any non-null as deceased

            if ($fhId && $yob && !$isDeceased) {
                $calcAge = max(0, $nowYear - $yob);
                $stored  = isset($row['age_now']) ? (int)$row['age_now'] : null;

                if ($stored !== $calcAge) {
                    // best-effort patch; don't fail the whole request if this write fails
                    try {
                        $upd->execute([':age' => $calcAge, ':id' => $fhId]);
                        $row['age_now'] = $calcAge; // reflect in response immediately
                    } catch (\Throwable $e) {
                        // swallow — return whatever we have
                    }
                } else {
                    // keep as-is
                    $row['age_now'] = $stored;
                }
            }
            // if deceased or missing year_of_born, we leave age_now as-is
        }
        unset($row);

        // 3) Fetch family history disease (one-to-one)
        $stmt = $pdo->prepare("SELECT * FROM family_history_disease WHERE staff_email = :staff_email");
        $stmt->execute([':staff_email' => $staffEmail]);
        $diseaseHistory = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        // 4) Respond
        $payload = [
            'family_history' => $familyHistory,
            'family_history_disease' => $diseaseHistory,
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);


    // to create family history
    $app->post('/family-history/add', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        // $data = $request->getParsedBody();
        $data = json_decode($request->getBody()->getContents(), true);

        $stmt = $pdo->prepare("INSERT INTO family_history 
            (staff_email, relationship, relative_name, sex, year_of_born, 
            age_now, age_at_death, state_health_death_cause) 
            VALUES 
            (:staff_email, :relationship, :relative_name, :sex, :year_of_born, 
            :age_now, :age_at_death, :state_health_death_cause)"
        );
        $stmt->bindParam(':staff_email', $data['staff_email']);
        $stmt->bindParam(':relationship', $data['relationship']);
        $stmt->bindParam(':relative_name', $data['relative_name']);
        $stmt->bindParam(':sex', $data['sex']);
        $stmt->bindParam(':year_of_born', $data['year_of_born']);
        $stmt->bindParam(':age_now', $data['age_now']);
        $stmt->bindParam(':age_at_death', $data['age_at_death']);
        $stmt->bindParam(':state_health_death_cause', $data['state_health_death_cause']);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Family History created successfully']));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);

    // to edit family history
    $app->put('/family-history/edit/{id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $id = $args['id'];
        // $data = $request->getParsedBody();
        $data = json_decode($request->getBody()->getContents(), true);


        $stmt = $pdo->prepare("UPDATE family_history 
        SET
            relationship = :relationship,
            relative_name = :relative_name,
            sex = :sex,
            year_of_born = :year_of_born,
            age_now = :age_now,
            age_at_death = :age_at_death,
            state_health_death_cause = :state_health_death_cause
        WHERE fh_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':relationship', $data['relationship']);
        $stmt->bindParam(':relative_name', $data['relative_name']);
        $stmt->bindParam(':sex', $data['sex']);
        $stmt->bindParam(':year_of_born', $data['year_of_born']);
        $stmt->bindParam(':age_now', $data['age_now']);
        $stmt->bindParam(':age_at_death', $data['age_at_death']);
        $stmt->bindParam(':state_health_death_cause', $data['state_health_death_cause']);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Family History updated successfully']));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);

    // to delete family history
    $app->delete('/family-history/delete/{id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $id = (int)$args['id'];

        // Check if family history record exists
        $stmt = $pdo->prepare("SELECT * FROM family_history WHERE fh_id = :id");
        $stmt->execute([':id' => $id]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'Family history record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Delete family history record
        $stmt = $pdo->prepare("DELETE FROM family_history WHERE fh_id = :id");
        $stmt->execute([':id' => $id]);

        $response->getBody()->write(json_encode(['message' => 'Family history deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

    // to get family history disease by email
    $app->get('/family-history-disease/{staff_email}', function (Request $request, Response $response, $args) {
        $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM family_history_disease WHERE staff_email = :staff_email");
        $stmt->execute([':staff_email' => $staffEmail]);
        $familyHistoryDisease = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($familyHistoryDisease));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);

    // to edit family history disease
    $app->put('/family-history-disease/edit/{staff_email}', function (Request $request, Response $response, $args) {
        $staffEmail = str_replace('XYZ', '.', urldecode($args['staff_email']));
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode($request->getBody()->getContents(), true);

        $stmt = $pdo->prepare("UPDATE family_history_disease 
        SET
            heart_disease = :heart_disease,
            high_blood_pressure = :high_blood_pressure,
            stroke = :stroke,
            cancer = :cancer,
            diabetes = :diabetes,
            kidney_disease = :kidney_disease,
            allergy = :allergy,
            asthma = :asthma,
            eczema = :eczema,
            tuberculosis = :tuberculosis,
            epilepsy = :epilepsy,
            mental_disorder = :mental_disorder,
            alcohol_dependence = :alcohol_dependence,
            drug_abuse = :drug_abuse,
            birth_abnormality = :birth_abnormality,
            none_above = :none_above,
            details = :details
        WHERE staff_email = :staff_email");
        $stmt->bindParam(':staff_email', $staffEmail);
        $stmt->bindParam(':heart_disease', $data['heart_disease']);
        $stmt->bindParam(':high_blood_pressure', $data['high_blood_pressure']);
        $stmt->bindParam(':stroke', $data['stroke']);
        $stmt->bindParam(':cancer', $data['cancer']);
        $stmt->bindParam(':diabetes', $data['diabetes']);
        $stmt->bindParam(':kidney_disease', $data['kidney_disease']);
        $stmt->bindParam(':allergy', $data['allergy']);
        $stmt->bindParam(':asthma', $data['asthma']);
        $stmt->bindParam(':eczema', $data['eczema']);
        $stmt->bindParam(':tuberculosis', $data['tuberculosis']);
        $stmt->bindParam(':epilepsy', $data['epilepsy']);
        $stmt->bindParam(':mental_disorder', $data['mental_disorder']);
        $stmt->bindParam(':alcohol_dependence', $data['alcohol_dependence']);
        $stmt->bindParam(':drug_abuse', $data['drug_abuse']);
        $stmt->bindParam(':birth_abnormality', $data['birth_abnormality']);
        $stmt->bindParam(':none_above', $data['none_above']);
        $stmt->bindParam(':details', $data['details']);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Family History Disease updated successfully']));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);


};