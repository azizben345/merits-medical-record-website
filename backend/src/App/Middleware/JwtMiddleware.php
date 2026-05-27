<?php
// App/Middleware/JwtMiddleware.php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Throwable;

class JwtMiddleware implements MiddlewareInterface
{
    private string $secret;
    private string $algo;
    private ?string $aud;
    private ?string $iss;
    private int $leewaySec;

    public function __construct(
        string $secret,
        string $algo = 'HS256',
        ?string $aud = null,
        ?string $iss = null,
        int $leewaySec = 30 // allow small clock skew
    ) {
        $this->secret = $secret;
        $this->algo = $algo;
        $this->aud = $aud;
        $this->iss = $iss;
        $this->leewaySec = $leewaySec;
        JWT::$leeway = $this->leewaySec;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $auth = $request->getHeaderLine('Authorization');
            if (!$auth || stripos($auth, 'Bearer ') !== 0) {
                return $this->unauthorized('Missing Bearer token');
            }

            $jwt = trim(substr($auth, 7));
            if ($jwt === '') {
                return $this->unauthorized('Empty token');
            }

            // Decode & verify
            $decoded = JWT::decode($jwt, new Key($this->secret, $this->algo));

            // Optional audience/issuer checks
            if ($this->aud && (!isset($decoded->aud) || $decoded->aud !== $this->aud)) {
                return $this->unauthorized('Invalid audience');
            }
            if ($this->iss && (!isset($decoded->iss) || $decoded->iss !== $this->iss)) {
                return $this->unauthorized('Invalid issuer');
            }

            // Attach claims for downstream use
            $request = $request->withAttribute('jwt', $decoded);
            return $handler->handle($request);

        } catch (ExpiredException $e) {
            return $this->unauthorized('Token expired');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorized('Invalid token signature');
        } catch (Throwable $e) {
            // Any other token/format issue -> 401
            return $this->unauthorized('Invalid token');
        }
    }

    private function unauthorized(string $msg): ResponseInterface
    {
        $res = new Response(401);
        $res->getBody()->write(json_encode(['error' => $msg]));
        return $res->withHeader('Content-Type', 'application/json');
    }
}
