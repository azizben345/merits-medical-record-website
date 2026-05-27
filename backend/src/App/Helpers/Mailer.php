<?php
namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private static $SMTP_EMAIL = 'merits.system.notify@gmail.com'; 
    private static $SMTP_PASS  = 'knhg ugis qjif eayv'; // Keep this safe!

    // Internal helper to avoid code duplication
    private static function getMailer() {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = self::$SMTP_EMAIL;
        $mail->Password   = self::$SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom(self::$SMTP_EMAIL, 'MERITS System');
        $mail->isHTML(true);
        return $mail;
    }

    public static function sendOTP($toEmail, $otpCode) {
        try {
            $mail = self::getMailer();
            $mail->addAddress($toEmail);
            $mail->Subject = 'Login Code: ' . $otpCode;
            $mail->Body    = "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #ddd;'>
                    <h2 style='color: #2b6cb0;'>Login Verification</h2>
                    <h1 style='letter-spacing: 5px; background: #eee; padding: 10px;'>$otpCode</h1>
                    <p>Please login within 5 minutes.</p>
                </div>";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer OTP Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    // Function for Password Reset
    public static function sendPasswordReset($toEmail, $token) {
        // insert url
        $link = 
            // "https://darkgray-mole-938146.hostingersite.com/#/reset-forgot-password?token="
            "http://localhost:8080/#/reset-forgot-password?token="
            . $token;

        try {
            $mail = self::getMailer();
            $mail->addAddress($toEmail);
            $mail->Subject = 'Reset Your Password';
            $mail->Body    = "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #ddd;'>
                    <h2 style='color: #e53e3e;'>Password Reset Request</h2>
                    <p>Click the link below to set a new password. Valid for 1 hour.</p>
                    <p><a href='$link' style='background:#2b6cb0; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>Reset Password</a></p>
                    <p style='font-size:12px; color:#777;'>If you did not request this, please ignore this email.</p>
                </div>";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Reset Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}

// namespace App\Helpers;

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// class Mailer {
//     // ---------------------------------------------------------
//     private static $SMTP_EMAIL = 'merits.system.notify@gmail.com'; 
//     private static $SMTP_PASS  = 'knhg ugis qjif eayv';
//     // ---------------------------------------------------------

//     public static function sendOTP($toEmail, $otpCode) {
//         $mail = new PHPMailer(true);

//         try {
//             // 1. Connect to Google's Server
//             $mail->isSMTP();
//             $mail->Host       = 'smtp.gmail.com';
//             $mail->SMTPAuth   = true;
//             $mail->Username   = self::$SMTP_EMAIL;
//             $mail->Password   = self::$SMTP_PASS;
//             $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // The standard encryption for Gmail
//             $mail->Port       = 587;

//             // 2. Setup the Email
//             $mail->setFrom(self::$SMTP_EMAIL, 'MERITS System Notification'); // Sender
//             $mail->addAddress($toEmail); // Recipient

//             // 3. Content
//             $mail->isHTML(true);
//             $mail->Subject = 'MERITS Login Code: ' . $otpCode;
//             $mail->Body    = "
//                 <div style='font-family: sans-serif; padding: 20px; border: 1px solid #ddd;'>
//                     <h2 style='color: #2b6cb0;'>Login Verification</h2>
//                     <p>Your One-Time Password is:</p>
//                     <h1 style='letter-spacing: 5px; background: #eee; display: inline-block; padding: 10px;'>$otpCode</h1>
//                     <p>Valid for your shift (12 hours).</p>
//                 </div>
//             ";

//             $mail->send();
//             return true;

//         } catch (Exception $e) {
//             // If it fails, print the error to your server logs so you can see why
//             error_log("Mailer Error: " . $mail->ErrorInfo);
//             return false;
//         }
//     }
// }