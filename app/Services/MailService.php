<?php

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    /**
     * Gui email chua ma xac thuc.
     *
     * Input:
     * - $toEmail: email nguoi nhan
     * - $toName: ten nguoi nhan
     * - $verificationCode: ma xac thuc 6 so
     * - $expiresAt: thoi gian het han cua ma, dinh dang Y-m-d H:i:s
     *
     * Output:
     * - true neu gui thanh cong
     * - false neu co loi
     */
    public static function sendVerificationEmail(string $toEmail, string $toName, string $verificationCode, string $expiresAt): bool
    {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            // Gmail app password thuong hien thi theo nhom co khoang trang, SMTP can chuoi lien nhau.
            $mail->Password = str_replace(' ', '', $_ENV['MAIL_PASSWORD'] ?? '');
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);

            $fromEmail = $_ENV['MAIL_FROM'] ?? ($_ENV['MAIL_USERNAME'] ?? '');
            $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'CodeNotes';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Ma xac thuc email tai khoan';
            $mail->Body = self::buildVerificationBody($toName, $verificationCode, $expiresAt);
            $mail->AltBody = "Xin chao {$toName}, ma xac thuc email cua ban la: {$verificationCode}. Ma het han luc {$expiresAt}.";

            return $mail->send();
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Tao noi dung HTML cho email xac thuc.
     *
     * Input:
     * - $toName: ten nguoi nhan
     * - $verificationCode: ma xac thuc 6 so
     * - $expiresAt: thoi gian het han cua ma
     *
     * Output:
     * - string HTML dung lam noi dung email
     */
    private static function buildVerificationBody(string $toName, string $verificationCode, string $expiresAt): string
    {
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($verificationCode, ENT_QUOTES, 'UTF-8');
        $safeExpiresAt = htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Xac thuc email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #222;">
    <p>Xin chao {$safeName},</p>
    <p>Ma xac thuc email tai khoan cua ban la:</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">{$safeCode}</p>
    <p>Ma nay se het han luc {$safeExpiresAt}.</p>
    <p>Neu ban khong yeu cau dang ky, co the bo qua email nay.</p>
</body>
</html>
HTML;
    }
}
