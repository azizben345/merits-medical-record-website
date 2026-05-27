<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use App\db;
use PDO;

class SessionTimeoutMiddleware {
    public function __invoke(Request $request, RequestHandler $handler): Response {
        // 1. Get User ID from the JWT (which ran in previous middleware)
        $user = $request->getAttribute('jwt_data'); 
        
        // If no user (public route), just pass through
        if (!$user) { 
            return $handler->handle($request); 
        }

        $userId = $user['user_id'];
        $db = new db();
        $pdo = $db->getPDO();

        // 2. Check Last Activity
        $stmt = $pdo->prepare("SELECT last_activity_at FROM users WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['last_activity_at']) {
            $lastActive = strtotime($row['last_activity_at']);
            $timeout = 15 * 60; // 15 minutes

            if ((time() - $lastActive) > $timeout) {
                // EXPIRED! Kill the request.
                $response = new Response();
                $response->getBody()->write(json_encode(['error' => 'Session expired due to inactivity. Please login again.']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        }

        // 3. Update Activity (Keep session alive)
        $pdo->prepare("UPDATE users SET last_activity_at = NOW() WHERE user_id = :id")->execute(['id' => $userId]);

        return $handler->handle($request);
    }
}