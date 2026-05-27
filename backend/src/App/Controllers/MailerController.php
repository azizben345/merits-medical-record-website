<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;
use App\Controllers\AuthController;

// Accept the key as the 3rd argument
return function ($app, $jwtMiddleware, $jwtSecretKey) {

    // ------------------------------------------------------------------
    // ROUTE 1: LOGIN (Request OTP)
    // ------------------------------------------------------------------
    $app->post('/login-otp', function (Request $request, Response $response) use ($jwtSecretKey) {
        
        $database = new db();
        // Pass the key we received into the Controller
        $controller = new AuthController($database, $jwtSecretKey);

        try {
            $result = $controller->login($request->getParsedBody());
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $status = $e->getCode() ?: 500;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    });

    // ------------------------------------------------------------------
    // ROUTE 2: VERIFY OTP (Get Token)
    // ------------------------------------------------------------------
    $app->post('/verify-otp', function (Request $request, Response $response) use ($jwtSecretKey) {
        
        $database = new db();
        $controller = new AuthController($database, $jwtSecretKey);

        try {
            $result = $controller->verifyOtp($request->getParsedBody());
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $status = $e->getCode() ?: 500;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    });

};