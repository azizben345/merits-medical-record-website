<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\db;
use App\Helpers\Mailer;

// require_once __DIR__ . '/../utils/db.php';

return function ($app, $jwtMiddleware) {

    // ------------------------------------------------------------------
    // VERIFY SETUP LINK (Check if valid on page load)
    // ------------------------------------------------------------------
    $app->post('/auth/verify-setup-link', function (Request $request, Response $response) {
        $db = new \App\db();
        $pdo = $db->getPDO();
        $data = json_decode($request->getBody()->getContents(), true);

        $email = $data['email'] ?? '';
        $code = $data['code'] ?? '';

        // 1. Basic lookup
        $stmt = $pdo->prepare("SELECT password, is_temp_password FROM users WHERE email = :email AND deleted_at IS NULL");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        // 2. Strict Checks
        $isValid = false;
        if ($user) {
            // Must have flag = 1 AND password must match
            if ($user['is_temp_password'] == 1 && password_verify($code, $user['password'])) {
                $isValid = true;
            }
        }

        if ($isValid) {
            $response->getBody()->write(json_encode(['valid' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            // Return 400 so frontend knows to block the page
            $response->getBody()->write(json_encode(['valid' => false, 'message' => 'Link expired or invalid']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    });

    // ------------------------------------------------------------------
    // ACCOUNT SETUP (First Time Login / Activation)
    // ------------------------------------------------------------------
    $app->post('/auth/setup-account', function (Request $request, Response $response) {
        $db = new \App\db();
        $pdo = $db->getPDO();
        $data = json_decode($request->getBody()->getContents(), true);

        $email = $data['email'] ?? '';
        $tempPass = $data['temp_password'] ?? '';
        $newPass = $data['new_password'] ?? '';
        $fullname = $data['fullname'] ?? '';

        // 1. Validation
        if (empty($email) || empty($tempPass) || empty($newPass)) {
            throw new \Exception("Missing required fields", 400);
        }

        // 2. Find User
        // We check if the email exists AND usually we check if 'is_temp_password' is 1
        // to prevent people from using this route to reset regular accounts.
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND deleted_at IS NULL");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception("User not found", 404);
        }

        // 3. Verify Temporary Password
        // Note: If you stored temp_password as HASHED in DB, use password_verify.
        // If you stored it as PLAIN TEXT (common for temp codes), use direct comparison.
        // Assuming standard HASHED for security:
        if (!password_verify($tempPass, $user['password'])) {
            throw new \Exception("Invalid temporary code", 401);
        }

        // 4. Update User Credentials
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        
        // Update password, set temp flag to 0, and update fullname if provided
        $updateSql = "UPDATE users SET password = :pass, is_temp_password = 0";
        $params = ['pass' => $newHash, 'id' => $user['user_id']];

        if (!empty($fullname)) {
            $updateSql .= ", fullname = :name";
            $params['name'] = $fullname;
        }

        $updateSql .= " WHERE user_id = :id";
        
        $pdo->prepare($updateSql)->execute($params);

        // 5. Auto-Login (Generate Token) so they don't have to login again
        // Reuse your existing Token Generation logic here
        $payload = [
            'iss' => "vtti-merits",
            'iat' => time(),
            'exp' => time() + (12 * 60 * 60), // 12 hours
            'user_id' => $user['user_id'],
            'role' => $user['role'],
            'email' => $user['email']
        ];
        // IMPORTANT: Make sure you have JWT_SECRET defined or imported
        $jwt = \Firebase\JWT\JWT::encode($payload, $_ENV['JWT_SECRET'] ?? 'secret_key', 'HS256');

        // Return the Token + User Info (same format as your Login API)
        $response->getBody()->write(json_encode([
            'message' => 'Account activated successfully',
            'token' => $jwt,
            'user' => [
                'id' => $user['user_id'],
                'email' => $user['email'],
                'fullname' => $fullname ?: $user['fullname'], // Return the new name
                'role' => $user['role']
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    });

    // Forgot Password Request (No Login Required)
    $app->post('/user/forgot-password', function (Request $request, Response $response) {
        // 1. Setup Database Connection manually here
        $database = new db();
        $pdo = $database->getPDO();
        // 2. Parse Input
        $data = json_decode($request->getBody()->getContents(), true);
        $email = $data['email'] ?? '';
        // 3. Validate Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Valid email required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // 4. Logic: Check User & Create Token
        // Check if user exists (Silent fail if not found for security)
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND deleted_at IS NULL");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(32)); 
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Clean old tokens
            $pdo->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $email]);
            
            // Insert new token
            $insert = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
            $insert->execute(['email' => $email, 'token' => $token, 'expires' => $expires]);

            // Send Email
            Mailer::sendPasswordReset($email, $token);
        }

        // 5. Return Success (Always return 200 OK so we don't leak which emails exist)
        $response->getBody()->write(json_encode(['message' => 'If the email exists, a reset link has been sent.']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ------------------------------------------------------------------
    // CHANGE PASSWORD (LOGGED IN)
    // ------------------------------------------------------------------
    $app->post('/user/change-password', function (Request $request, Response $response) {
        // 1. Get User ID from Token (Middleware put it there)
        $jwt = $request->getAttribute('jwt_data');
        $userId = $jwt['user_id'];

        $data = json_decode($request->getBody()->getContents(), true);
        $currentPass = $data['current_password'] ?? '';
        $newPass = $data['new_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            throw new \Exception("Missing password fields", 400);
        }

        $db = new db();
        $pdo = $db->getPDO();

        // 2. Verify Current Password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPass, $user['password'])) {
            throw new \Exception("Incorrect current password", 401);
        }

        // 3. Update to New Password
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        
        // Also clear the 'is_temp_password' flag if they change it manually
        $pdo->prepare("UPDATE users SET password = ?, is_temp_password = 0 WHERE user_id = ?")
            ->execute([$newHash, $userId]);

        $response->getBody()->write(json_encode(['message' => 'Password changed successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Reset Password Action (No Login Required)
    $app->post('/user/reset-password', function (Request $request, Response $response) {
        // 1. Setup Database
        $database = new db();
        $pdo = $database->getPDO();
        // 2. Parse Input
        $data = json_decode($request->getBody()->getContents(), true);
        $token = $data['token'] ?? '';
        $newPass = $data['new_password'] ?? '';
        // 3. Validation
        if (empty($token) || empty($newPass)) {
            $response->getBody()->write(json_encode(['error' => 'Missing token or password']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // 4. Verify Token
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch(\PDO::FETCH_ASSOC);
        // Check if token exists AND is not expired
        if (!$reset || strtotime($reset['expires_at']) < time()) {
            $response->getBody()->write(json_encode(['error' => 'Invalid or expired token']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // 5. Update Password
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        // Update user password & clear 'is_temp_password' flag
        $pdo->prepare("UPDATE users SET password = ?, is_temp_password = 0 WHERE email = ?")
            ->execute([$hash, $reset['email']]);
        // 6. Delete Token (Prevent reuse)
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
        // 7. Success Response
        $response->getBody()->write(json_encode(['message' => 'Password reset successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // to logout user
    $app->post('/user/logout', function (Request $request, Response $response) {
        $response->getBody()->write(json_encode(['message' => 'Logout successful.']));
        return $response->withHeader('Content-Type', 'application/json');
    });//->add($jwtMiddleware);

    // $app->group('', function ($group) {
    //     // move existing protected routes inside here
    //     // $group->get('/dashboard', [UserController::class, 'dashboard']);
    //     // $group->get('/user/profile', [UserController::class, 'profile']);
    //     // ... etc ...
    // })
    // // LIFO ORDER (Last Added = First Executed)
    // ->add(new \App\Middleware\SessionTimeoutMiddleware()) // 2
    // ->add($jwtMiddleware); // 1

    // to fetch all users
    $app->get('/admin/users', function (Request $request, Response $response) {
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->query("SELECT * FROM users");
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to fetch a specific user by user_id
    $app->get('/admin/user/{user_id}', function (Request $request, Response $response, $args) {
        $user_id = (int)$args['user_id'];
        $db = new db();
        $pdo = $db->getPDO();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($user));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($jwtMiddleware);

    // to fetch a specific user info from different role tables by email - also for profile
    $app->get('/user/role-based/{email:.+}', function (Request $request, Response $response, $args) {
        // $email = str_replace('XYZ', '.', urldecode($args['email']));
        $email = str_replace(['XYZ', 'UVW'], ['.', '+'], urldecode($args['email']));
        // $email = $args['email'];
        $db = new db();
        $pdo = $db->getPDO();
        // first get user role from users table
        $stmt = $pdo->prepare("SELECT role FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $role = $user['role'];
        $userInfo = null;
        if ($role === 'doctor') {
            $stmt = $pdo->prepare("SELECT * FROM doctor WHERE doctor_email = :email");
            $stmt->execute(['email' => $email]);
            $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
        } else if ($role === 'staff') {
            $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_email = :email");
            $stmt->execute(['email' => $email]);
            $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
        } else if ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_email = :email");
            $stmt->execute(['email' => $email]);
            $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            $response->getBody()->write(json_encode(['error' => 'Invalid user role.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if (!$userInfo) {
            $response->getBody()->write(json_encode(['error' => ucfirst($role) . ' info not found.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        // merge user and userInfo
        $result = array_merge($user, $userInfo);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');

    })->add($jwtMiddleware);

    // to register a new user
    // session-linked tables you want to initialize/duplicate
    $SESSION_TABLES = [
        'medical_history',
        'lifestyle',
        'physical_exams',
        'physical_exams_2',
        'investigations',
        'investigations_lab',
    ];

    // find the most recent session (optionally before a date)
    $findLatestSessionId = function (\PDO $pdo, string $staffEmail, ?string $beforeDate = null): ?int {
        $sql = "SELECT session_id
                FROM checkup_sessions
                WHERE staff_email = :email" .
                ($beforeDate ? " AND session_date < :beforeDate" : "") .
            " ORDER BY session_date DESC
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $params = [':email' => strtolower(trim($staffEmail))];
        if ($beforeDate) $params[':beforeDate'] = $beforeDate;
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['session_id'] : null;
    };

    // create an empty row for a given session-linked table
    $initEmptyRow = function (\PDO $pdo, string $table, string $email, int $sessionId): void {
        $stmt = $pdo->prepare("INSERT INTO {$table} (staff_email, session_id) VALUES (:email, :sid)");
        $stmt->execute([':email' => strtolower(trim($email)), ':sid' => $sessionId]);
    };

    // duplicate the last session’s row for a given table → new session
    $duplicateRowForTable = function (\PDO $pdo, string $table, string $email, int $fromSessionId, int $toSessionId): void {
        $sel = $pdo->prepare("SELECT * FROM {$table} WHERE staff_email = :email AND session_id = :sid LIMIT 1");
        $sel->execute([':email' => strtolower(trim($email)), ':sid' => $fromSessionId]);
        $src = $sel->fetch(\PDO::FETCH_ASSOC);

        if (!$src) {
            // If no prior row, just init empty
            $stmt = $pdo->prepare("INSERT INTO {$table} (staff_email, session_id) VALUES (:email, :sid)");
            $stmt->execute([':email' => strtolower(trim($email)), ':sid' => $toSessionId]);
            return;
        }

        // Remove PK column (first *_id or 'id' if present) + timestamps
        foreach (array_keys($src) as $k) {
            if (preg_match('/_id$/', $k) || $k === 'id') { unset($src[$k]); break; }
        }
        unset($src['created_at'], $src['updated_at']);

        // Force new linkage
        $src['staff_email'] = strtolower(trim($email));
        $src['session_id']  = $toSessionId;

        $cols   = array_keys($src);
        $params = array_map(fn($c) => ':' . $c, $cols);

        $ins = $pdo->prepare(
            "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (" . implode(',', $params) . ")"
        );
        $bind = [];
        foreach ($cols as $i => $c) $bind[$params[$i]] = $src[$c];
        $ins->execute($bind);
    };

    // ------------------------------
    // POST /admin/register  (extended)
    // ------------------------------
    $app->post('/admin/register', function (Request $request, Response $response) use (
        $SESSION_TABLES, $findLatestSessionId, $initEmptyRow, $duplicateRowForTable
    ) {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        // Required fields
        if (empty($data['email']) || empty($data['password']) || empty($data['role']) || empty($data['fullname'])) {
            $response->getBody()->write(json_encode(['error' => 'Email, password, fullname, and role are required.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $email    = strtolower(trim($data['email']));
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $role     = strtolower(trim($data['role']));
        $fullname = trim($data['fullname']);

        // if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        //     $response->getBody()->write(json_encode(['error' => 'Invalid email format.']));
        //     return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        // }
        // admin can have non-email input
        if ($role !== 'admin') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response->getBody()->write(json_encode(['error' => 'Invalid email format.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }
        if (!in_array($role, ['admin', 'doctor', 'staff'], true)) {
            $response->getBody()->write(json_encode(['error' => 'Role must be admin, doctor, or staff.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // optional session init flags from frontend
        $initSession     = !empty($data['init_session']); // bool
        $duplicateRecent = !empty($data['duplicate_recent']); // bool
        $sessionDate     = !empty($data['session_date'])  ? trim($data['session_date'])  : date('Y-m-d');
        $sessionType     = !empty($data['session_type'])  ? trim($data['session_type'])  : 'annual';
        $sessionStatus   = !empty($data['session_status'])? trim($data['session_status']): 'draft';
        $createdBy       = !empty($data['created_by']) ? strtolower(trim($data['created_by'])) : null; // admin email preferably

        try {
            $pdo = (new db())->getPDO();
            $pdo->beginTransaction();

            // Uniqueness check
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                $response->getBody()->write(json_encode(['error' => 'Email already exists.']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role, fullname) VALUES (:email, :password, :role, :fullname)");
            $stmt->execute([
                ':email'    => $email,
                ':password' => $password,
                ':role'     => $role,
                ':fullname' => $fullname
            ]);

            // Role-specific info tables
            if ($role === 'staff') {
                // staff baseline rows (your previous behavior)
                $stmt = $pdo->prepare("INSERT INTO staff (staff_email, staff_name) VALUES (:email, :name)");
                $stmt->execute([':email' => $email, ':name' => $fullname]);

                $stmt = $pdo->prepare("INSERT INTO family_history (staff_email, relationship) VALUES (:email,'father'), (:email,'mother')");
                $stmt->execute([':email' => $email]);

                $stmt = $pdo->prepare("INSERT INTO family_history_disease (staff_email) VALUES (:email)");
                $stmt->execute([':email' => $email]);

                // Optionally create a first session (+ tables)
                if ($initSession) {
                    // Create the session (with created_by if provided)
                    $ins = $pdo->prepare("INSERT INTO checkup_sessions
                        (staff_email, session_date, session_type, status, created_by)
                        VALUES (:email, :dt, :typ, :st, :by)");
                    $ins->execute([
                        ':email' => $email,
                        ':dt'    => $sessionDate,
                        ':typ'   => $sessionType,
                        ':st'    => $sessionStatus,
                        ':by'    => $createdBy
                    ]);
                    $newSessionId = (int)$pdo->lastInsertId();

                    if ($duplicateRecent) {
                        $prevId = $findLatestSessionId($pdo, $email, $sessionDate);
                        if ($prevId) {
                            foreach ($SESSION_TABLES as $tbl) {
                                $duplicateRowForTable($pdo, $tbl, $email, $prevId, $newSessionId);
                            }
                        } else {
                            foreach ($SESSION_TABLES as $tbl) {
                                $initEmptyRow($pdo, $tbl, $email, $newSessionId);
                            }
                        }
                    } else {
                        foreach ($SESSION_TABLES as $tbl) {
                            $initEmptyRow($pdo, $tbl, $email, $newSessionId);
                        }
                    }
                }
            } elseif ($role === 'doctor') {
                $stmt = $pdo->prepare("INSERT INTO doctor (doctor_email, doctor_name) VALUES (:email, :name)");
                $stmt->execute([':email' => $email, ':name' => $fullname]);
            } else { // admin
                $stmt = $pdo->prepare("INSERT INTO admin (admin_email, admin_name) VALUES (:email, :name)");
                $stmt->execute([':email' => $email, ':name' => $fullname]);
            }

            $pdo->commit();
            $response->getBody()->write(json_encode(['message' => 'User registered successfully.']));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (Throwable $e) {
            if (!empty($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // to update a user's details by user_id
    $app->put('/admin/user/{user_id}', function (Request $request, Response $response, array $args) {
        $user_id = (int)$args['user_id'];
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['email']) || empty($data['role']) || empty($data['fullname'])) {
            $response->getBody()->write(json_encode(['error' => 'Email, username, fullname, and role are required.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $email = strtolower(trim($data['email']));
        $username = trim($data['username']);
        $role = strtolower(trim($data['role']));
        $fullname = trim($data['fullname']);

        // admin can have non-email input
        if ($role !== 'admin') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response->getBody()->write(json_encode(['error' => 'Invalid email format.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        if (!in_array($role, ['admin', 'doctor', 'staff'])) {
            $response->getBody()->write(json_encode(['error' => 'Role must be admin, doctor, or staff.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $db = new db();
            $pdo = $db->getPDO();

            // check if user exists
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'User not found.']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // check for email uniqueness
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :email AND user_id != :user_id");
            $stmt->execute(['email' => $email, 'user_id' => $user_id]);
            if ($stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'Email already in use by another user.']));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            // update user details
            $stmt = $pdo->prepare("UPDATE users SET email = :email, username = :username, role = :role, fullname = :fullname WHERE user_id = :user_id");
            $stmt->execute([
                'email' => $email,
                'username' => $username,
                'role' => $role,
                'fullname' => $fullname,
                'user_id' => $user_id
            ]);
            $response->getBody()->write(json_encode(['message' => 'User updated successfully.']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // to edit a user profile by email (role-based)
    $app->put('/user/edit/role-based/{email:.+}', function (Request $request, Response $response, array $args) {
        $email = str_replace('XYZ','.',$args['email']);
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['fullname'])) {
            $response->getBody()->write(json_encode(['error' => 'Fullname is required.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // $fullname = trim($data['fullname']);

        try {
            $db = new db();
            $pdo = $db->getPDO();

            // check if user exists
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'User not found.']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // update user details
            $stmt = $pdo->prepare("UPDATE users SET 
                fullname = :fullname
                WHERE email = :email");
            $stmt->execute([
                'fullname' => $data['fullname'],
                'email' => $email
            ]);
            // update role-specific tables
            if ($data['role'] == 'admin') {
                $stmt = $pdo->prepare("UPDATE admin SET 
                    admin_name = :fullname,
                    phone_no = :phone_no,
                    department = :department
                    WHERE admin_email = :email");
                $stmt->execute([
                    'fullname' => $data['fullname'],
                    'phone_no' => $data['phone_no'],
                    'department' => $data['department'],
                    'email' => $email
                ]);
            } elseif ($data['role'] == 'doctor') {
                $stmt = $pdo->prepare("UPDATE doctor SET 
                    doctor_name = :fullname,
                    phone_no = :phone_no 
                    WHERE doctor_email = :email");
                $stmt->execute([
                    'fullname' => $data['fullname'],
                    'phone_no' => $data['phone_no'],
                    'email' => $email
                ]);
            } elseif ($data['role'] == 'staff') {
                $stmt = $pdo->prepare("UPDATE staff SET 
                    staff_name = :fullname,
                    phone_no = :phone_no,
                    job_title = :job_title
                    WHERE staff_email = :email");
                $stmt->execute([
                    'fullname' => $data['fullname'],
                    'phone_no' => $data['phone_no'],
                    'job_title' => $data['job_title'],
                    'email' => $email
                ]);
            } else {
                $response->getBody()->write(json_encode(['error' => 'Invalid role.']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['message' => 'User updated successfully.']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // to change a user's password by user_id - unused
    // $app->post('/admin/user/reset-password/{user_id}', function (Request $request, Response $response, array $args) {
    //     $user_id = (int)$args['user_id'];
    //     $data = json_decode($request->getBody()->getContents(), true);

    //     if (empty($data['new_password'])) {
    //         $response->getBody()->write(json_encode(['error' => 'New password is required.']));
    //         return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    //     }

    //     $new_password = password_hash($data['new_password'], PASSWORD_BCRYPT);

    //     try {
    //         $db = new db();
    //         $pdo = $db->getPDO();

    //         // check if user exists
    //         $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = :user_id");
    //         $stmt->execute(['user_id' => $user_id]);
    //         if (!$stmt->fetch()) {
    //             $response->getBody()->write(json_encode(['error' => 'User not found.']));
    //             return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //         }

    //         // update password
    //         $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
    //         $stmt->execute([
    //             'password' => $new_password,
    //             'user_id' => $user_id
    //         ]);
    //         $response->getBody()->write(json_encode(['message' => 'Password updated successfully.']));
    //         return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    //     } catch (\Throwable $e) {
    //         $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
    //         return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    //     }
    // })->add($jwtMiddleware);

    // to delete a user by user_id // not in use
    $app->delete('/admin/user/{user_id}', function (Request $request, Response $response, array $args) {
        $user_id = (int)$args['user_id'];

        try {
            $db = new db();
            $pdo = $db->getPDO();

            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'User not found.']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);

            $response->getBody()->write(json_encode(['message' => 'User deleted successfully.']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // to set a user to inactive by user_id (soft DELETE)
    $app->put('/admin/user/soft-delete/{user_id}', function (Request $request, Response $response, array $args) {
        $user_id = (int)$args['user_id'];

        try {
            $db = new db();
            $pdo = $db->getPDO();

            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'User not found.']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);

            $response->getBody()->write(json_encode(['message' => 'User set to inactive successfully.']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

    // to set a user to active by user_id (soft RESTORE)
    $app->put('/admin/user/restore/{user_id}', function (Request $request, Response $response, array $args) {
        $user_id = (int)$args['user_id'];

        try {
            $db = new db();
            $pdo = $db->getPDO();

            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            if (!$stmt->fetch()) {
                $response->getBody()->write(json_encode(['error' => 'User not found.']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $pdo->prepare("UPDATE users SET deleted_at = NULL WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);

            $response->getBody()->write(json_encode(['message' => 'User set to active successfully.']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    })->add($jwtMiddleware);

};