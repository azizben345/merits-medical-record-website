<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Controllers\AuthController;
// SMTP ctrler
use App\Controllers\SMTPController;

use App\Middleware\JwtMiddleware;
use Tuupola\Middleware\CorsMiddleware;
// SMTP test
use App\Helpers\Mailer;

$app = AppFactory::create();
$app->setBasePath('/api');
// added for smtp
$app->addBodyParsingMiddleware();

$secretKey = "my-secret-key";
$jwtMiddleware = new JwtMiddleware($secretKey);

// // test controllers without $jwtMiddleware
// $noop = function ($request, $handler) {  // Slim 4 accepts callables as middleware
//   return $handler->handle($request);
// };

// Show errors
$errorMw = $app->addErrorMiddleware(true, true, true);
$errorMw->setDefaultErrorHandler(function ($request, Throwable $e) {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'error' => $e->getMessage(),
        'type'  => get_class($e),
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
});

// Minimal health route to test API connection
$app->get('/health', function ($req, $res) {
    $res->getBody()->write(json_encode(['ok' => true, 'ts' => time()]));
    return $res->withHeader('Content-Type', 'application/json');
});

// LOGIN route - currently changed to using OTP
$app->post('/login', function (Request $request, Response $response) use ($secretKey) {
    $body = (string) $request->getBody();
    $credentials = json_decode($body, true);

    if (!is_array($credentials)) {
        $response->getBody()->write(json_encode(['error' => 'Invalid JSON']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    if (empty($credentials['email']) || empty($credentials['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Email and password are required']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // DB + Auth
    $database = new \App\db();
    $auth = new AuthController($database, $secretKey);
    $result = $auth->login($credentials);

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

// SMTP test
$app->get('/test-email', function ($request, $response, $args) {
    
    // Hardcode your personal email here just for this test
    $result = Mailer::sendOTP('azizmabeni@gmail.com', '987654');

    if ($result) {
        $response->getBody()->write("Success! Email sent.");
    } else {
        $response->getBody()->write("Failed. Check PHP error logs.");
    }
    return $response;
});

require_once __DIR__ . '/../src/App/Middleware/JwtMiddleware.php';
require_once __DIR__ . '/../src/App/Controllers/AuthController.php';
(require __DIR__ . '/../src/App/Controllers/UserController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/AdminController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/StaffController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/DoctorController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/OccupationalHistoryController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/FamilyHistoryController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/MedicalHistoryController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/LifestyleController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/PhysicalExamsController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/InvestigationsController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/StatisticsController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/SessionController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/ReportController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/CertificateController.php')($app, $jwtMiddleware);
(require __DIR__ . '/../src/App/Controllers/MailerController.php')($app, $jwtMiddleware, $secretKey);
// (require __DIR__ . '/../src/App/Controllers/SMTPController.php')($app, $jwtMiddleware);

// enable CORS for all routes
$app->add(new CorsMiddleware([
    "origin" => "*",  // Allow all origins (for development purposes)
    "methods" => ["GET", "POST", "PUT", "DELETE", "OPTIONS"],  // Allow all necessary methods
    "headers.allow" => ["Content-Type", "Authorization"],  // Allow necessary headers
    "headers.expose" => ["Content-Type", "Authorization"],  // Expose necessary headers
    "credentials" => true 
]));

$app->run();
