<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // to get all occupational history
    $app->get('/occupational-history', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM occupational_history");
        $occupationalHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($occupationalHistory));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to get occupational history by email
    $app->get('/occupational-history/{email:.+}', function ($request, $response, $args) {

        // Get the staff email from query parameter
        // $staffEmail = $request->getQueryParam('staff_email');
        $staffEmail = str_replace('XYZ', '.', urldecode($args['email']));
        
        if (!$staffEmail) {
            return $response->withStatus(400)->withJson(['error' => 'staff_email is required']);
        }

        // Get data from the database
        try {
            $db = new db();
            $pdo = $db->getPDO();
            // Query to fetch occupational history for the staff email
            $stmt = $pdo->prepare("SELECT oh_id, year, company, location, job_title, nature_of_work 
                                FROM occupational_history 
                                WHERE staff_email = ?");
            $stmt->execute([$staffEmail]);
            $historyData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // If no records found
            if (empty($historyData)) {
                $response->getBody()->write(json_encode(['error' => 'No occupational history found for staff email: ' . $staffEmail]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Prepare response
            $result = [
                'staff_email' => $staffEmail,
                'occupational_history' => $historyData
            ];

            // Return the results as JSON
            $response->getBody()->write(json_encode($historyData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            // Handle database errors
            return $response->withStatus(500)->withJson(['error' => 'Database error', 'message' => $e->getMessage()]);
        }

    })->add($jwtMiddleware);

    // to add occupational history
    $app->post('/occupational-history/add', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $data = json_decode($request->getBody()->getContents(), true);

        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $pdo->prepare("INSERT INTO occupational_history (staff_email, year, company, location, job_title, nature_of_work) 
                                VALUES (:staff_email, :year, :company, :location, :job_title, :nature_of_work)");
        $stmt->bindParam(':staff_email', $data['staff_email'], \PDO::PARAM_STR);
        $stmt->bindParam(':year', $data['year'], \PDO::PARAM_INT);
        $stmt->bindParam(':company', $data['company'], \PDO::PARAM_STR);
        $stmt->bindParam(':location', $data['location'], \PDO::PARAM_STR);
        $stmt->bindParam(':job_title', $data['job_title'], \PDO::PARAM_STR);
        $stmt->bindParam(':nature_of_work', $data['nature_of_work'], \PDO::PARAM_STR);
        $stmt->execute();

        $response->getBody()->write(json_encode(['message' => 'Occupational history added successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    })->add($jwtMiddleware);

    // to update occupational history by oh_id
    $app->put('/occupational-history/edit/{id}', function (Request $request, Response $response, $args) {
        // $email = str_replace('XYZ', '.', urldecode($args['email']));
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        if ($data === null || $data === false) {
            $response->getBody()->write(json_encode(['error' => 'No data provided']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = new db();
        $pdo = $db->getPDO();

        // Check if occupational history record exists
        $stmt = $pdo->prepare("SELECT * FROM occupational_history WHERE oh_id = :id");
        $stmt->execute([':id' => $id]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'Occupational history record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Update occupational history record
        $stmt = $pdo->prepare("UPDATE occupational_history SET year = :year, company = :company, location = :location, 
                                job_title = :job_title, nature_of_work = :nature_of_work WHERE oh_id = :id");
        $stmt->execute([
            ':year' => $data['year'],
            ':company' => $data['company'],
            ':location' => $data['location'],
            ':job_title' => $data['job_title'],
            ':nature_of_work' => $data['nature_of_work'],
            ':id' => $id
        ]);

        $response->getBody()->write(json_encode(['message' => 'Occupational history updated successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
    })->add($jwtMiddleware);

    // to delete occcupational history based on oh_id
    $app->delete('/occupational-history/delete/{id}', function (Request $request, Response $response, $args) {
        $db = new db();
        $pdo = $db->getPDO();

        $id = (int)$args['id'];

        // Check if occupational history record exists
        $stmt = $pdo->prepare("SELECT * FROM occupational_history WHERE oh_id = :id");
        $stmt->execute([':id' => $id]);
        $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingRecord === false || $existingRecord === null)  {
            $response->getBody()->write(json_encode(['error' => 'Occupational history record not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Delete occupational history record
        $stmt = $pdo->prepare("DELETE FROM occupational_history WHERE oh_id = :id");
        $stmt->execute([':id' => $id]);

        $response->getBody()->write(json_encode(['message' => 'Occupational history deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    })->add($jwtMiddleware);

};