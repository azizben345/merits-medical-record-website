<?php

namespace App\Controllers;

use App\db;
use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;
use Firebase\JWT\JWT;

// Note: Removed 'use App\Helpers\Mailer;' as it is no longer needed

class AuthController {
    private string $jwtSecretKey;

    public function __construct(
        private db $database,  
        string $jwtSecretKey   
    ) {
        $this->jwtSecretKey = $jwtSecretKey;
    }

    // SINGLE STEP: Verify Credentials & Return Token
    public function login(array $credentials): array
    {
        $identifier = $credentials['email'] ?? $credentials['username'] ?? '';
        $password   = $credentials['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            throw new InvalidArgumentException("Username/Email and password are required.");
        }

        try {
            $pdo = $this->database->getPDO();

            // 1. Search User (Added 'is_temp_password' to selection)
            $sql = "SELECT user_id, email, username, fullname, password, role, is_temp_password 
                    FROM users 
                    WHERE (email = :id OR username = :id) 
                    AND deleted_at IS NULL";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verify Password
            if (!$user || !password_verify($password, $user['password'])) {
                throw new RuntimeException("Invalid email/username or password.", 401);
            }

            // 3. Update Activity (Log the login)
            $pdo->prepare("UPDATE users SET last_activity_at = NOW() WHERE user_id = :id")
                ->execute([':id' => $user['user_id']]);

            // 4. Check Force Reset Status
            $forceReset = ($user['is_temp_password'] == 1);

            // 5. Generate Token (Directly)
            $issuedAt = time();
            $expirationTime = $issuedAt + 43200; // 12 Hours

            $payload = [
                'iat'  => $issuedAt,             
                'exp'  => $expirationTime,       
                'iss'  => 'merits-system', 
                'data' => [ 
                    'user_id' => $user['user_id'],
                    'email' => $user['email'],
                    'fullname' => $user['fullname'] ?? 'Sir/Mdm',
                    'role' => $user['role'],
                    'force_reset' => $forceReset
                ]
            ];

            $token = JWT::encode($payload, $this->jwtSecretKey, 'HS256');

            // Return Success immediately (No '2fa_required' status)
            return [
                'token' => $token,
                'user' => [
                    'user_id' => $user['user_id'],
                    'email' => $user['email'],
                    'fullname' => $user['fullname'] ?? 'Sir/Mdm',
                    'role' => $user['role'],
                    'force_reset' => $forceReset
                ]
            ];

        } catch (PDOException $e) {
            throw new RuntimeException("Database error during login: " . $e->getMessage(), 500, $e);
        }
    }

    public function logout(): array
    {
        return ['message' => 'Logged out successfully.'];
    }
    
    // NOTE: 'verifyOtp' method is deleted - no longer used.
}