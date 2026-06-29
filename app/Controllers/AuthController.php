<?php

namespace App\Controllers;

use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Services\MailService;
use Core\Database;
use Core\Jwt;
use Throwable;

class AuthController
{
    private User $userModel;
    private $emailVerificationModel;

    public function __construct()
    {
        $this->userModel = new User();
        $emailVerificationClass = 'App\\Models\\EmailVerification';
        $this->emailVerificationModel = new $emailVerificationClass();
    }

    /**
     * Dang ky user moi.
     *
     * Input JSON:
     * - username: string
     * - email: string
     * - password: string
     *
     * Output success:
     * - HTTP 201
     * - data.user: id, username, email, email_verified_at
     * - data.verification_expires_at: thoi gian het han cua ma xac thuc
     * - data.mail_sent: true neu gui email thanh cong
     * - data.verification_code_for_dev: ma xac thuc, chi tra ve khi APP_ENV=dev va SMTP gui that bai
     * - message thong bao da tao tai khoan va gui ma xac thuc email
     *
     * Output error:
     * - HTTP 400 neu body khong phai JSON hop le
     * - HTTP 422 neu du lieu dau vao khong hop le
     * - HTTP 409 neu email da ton tai
     * - HTTP 500 neu khong tao duoc tai khoan hoac khong gui duoc email
     */
    public function register(): void
    {
        // Doc JSON body tu request va chuyen thanh mang PHP.
        $data = RequestHelper::getJsonBody();

        // Neu body khong phai JSON hop le thi tra ve loi 400 Bad Request.
        if ($data === null) {
            ResponseHelper::error('Invalid JSON body', 400);
            return;
        }

        // Lay du lieu can thiet, trim de loai bo khoang trang thua.
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // Validate cac field bat buoc.
        if ($username === '' || $email === '' || $password === '') {
            ResponseHelper::error('Username, email and password are required', 422);
            return;
        }

        // Kiem tra email dung dinh dang.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error('Invalid email', 422);
            return;
        }

        // Mat khau qua ngan thi tu choi dang ky.
        if (strlen($password) < 6) {
            ResponseHelper::error('Password must be at least 6 characters', 422);
            return;
        }

        // Kiem tra email da ton tai chua de tranh trung user.
        if ($this->userModel->findByEmail($email) !== null) {
            ResponseHelper::error('Email already exists', 409);
            return;
        }

        // Hash password truoc khi luu, khong bao gio luu password goc vao database.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Tao ma xac thuc 6 so. str_pad giu ca truong hop ma bat dau bang so 0.
        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = password_hash($verificationCode, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 15 * 60);

        $db = Database::connection();
        $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'dev';
        $mailSent = false;

        try {
            $db->beginTransaction();

            // Tao user moi thong qua model, controller khong viet SQL truc tiep cho bang users.
            $user = $this->userModel->create($username, $email, $passwordHash);

            $now = date('Y-m-d H:i:s');
            // Luu hash cua ma xac thuc thong qua model rieng cho bang email_verifications.
            $this->emailVerificationModel->create(
                (int) $user['id'],
                $email,
                $codeHash,
                $expiresAt,
                $now,
                $now
            );

            // Gui ma xac thuc qua email.
            // Production: neu gui that bai thi rollback de khong tao tai khoan dang do.
            // Dev: neu SMTP bi chan, van tao tai khoan va tra ma trong response de test API.
            $mailSent = MailService::sendVerificationEmail($email, $username, $verificationCode, $expiresAt);

            if (!$mailSent && !$isDev) {
                $db->rollBack();
                ResponseHelper::error('Cannot send verification email', 500);
                return;
            }

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            ResponseHelper::error('Cannot create account', 500);
            return;
        }

        // Dang ky thanh cong nhung chua cap JWT, user can xac thuc email truoc khi dang nhap.
        $responseData = [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'email_verified_at' => null,
            ],
            'verification_expires_at' => $expiresAt,
            'mail_sent' => $mailSent,
        ];

        if (!$mailSent && $isDev) {
            $responseData['verification_code_for_dev'] = $verificationCode;
        }

        $message = $mailSent
            ? 'User registered successfully. Please check your email for the verification code.'
            : 'User registered successfully. SMTP is not available in dev, use verification_code_for_dev to verify email.';

        ResponseHelper::success($responseData, $message, 201);
    }

    /**
     * Xac thuc email bang ma da gui luc dang ky.
     *
     * Input JSON:
     * - email: string
     * - code: string gom 6 so
     *
     * Output success:
     * - HTTP 200
     * - data.user: id, username, email, email_verified_at
     *
     * Output error:
     * - HTTP 400 neu body khong phai JSON hop le
     * - HTTP 422 neu thieu email/code, email sai dinh dang, code sai hoac het han
     * - HTTP 404 neu khong tim thay yeu cau xac thuc
     * - HTTP 429 neu nhap sai qua so lan cho phep
     */
    public function verifyEmail(): void
    {
        // Doc JSON body tu request va chuyen thanh mang PHP.
        $data = RequestHelper::getJsonBody();

        // Neu body khong phai JSON hop le thi tra ve loi 400 Bad Request.
        if ($data === null) {
            ResponseHelper::error('Invalid JSON body', 400);
            return;
        }

        // Lay email va ma xac thuc tu request.
        $email = trim($data['email'] ?? '');
        $code = trim($data['code'] ?? '');

        // Validate cac field bat buoc.
        if ($email === '' || $code === '') {
            ResponseHelper::error('Email and verification code are required', 422);
            return;
        }

        // Kiem tra email dung dinh dang.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error('Invalid email', 422);
            return;
        }

        // Ma xac thuc can dung 6 chu so.
        if (!preg_match('/^\d{6}$/', $code)) {
            ResponseHelper::error('Verification code must be 6 digits', 422);
            return;
        }

        $db = Database::connection();
        $maxAttempts = 5;

        // Lay ma xac thuc moi nhat theo email, kem thong tin user de update khi thanh cong.
        $verification = $this->emailVerificationModel->findByEmailWithUser($email);

        if (!$verification) {
            ResponseHelper::error('Verification request not found', 404);
            return;
        }

        if ($verification['email_verified_at'] !== null || $verification['verified_at'] !== null) {
            ResponseHelper::success([
                'user' => [
                    'id' => $verification['user_id'],
                    'username' => $verification['username'],
                    'email' => $verification['email'],
                    'email_verified_at' => $verification['email_verified_at'],
                ],
            ], 'Email already verified');
            return;
        }

        if (strtotime($verification['expires_at']) < time()) {
            ResponseHelper::error('Verification code expired', 422);
            return;
        }

        if ((int) $verification['attempt_count'] >= $maxAttempts) {
            ResponseHelper::error('Too many wrong attempts. Please request a new verification code.', 429);
            return;
        }

        if (!password_verify($code, $verification['code_hash'])) {
            $newAttemptCount = (int) $verification['attempt_count'] + 1;

            $this->emailVerificationModel->updateAttemptCount(
                (int) $verification['verification_id'],
                $newAttemptCount,
                date('Y-m-d H:i:s')
            );

            ResponseHelper::error('Invalid verification code', 422, [
                'remaining_attempts' => max(0, $maxAttempts - $newAttemptCount),
            ]);
            return;
        }

        $verifiedAt = date('Y-m-d H:i:s');

        try {
            $db->beginTransaction();

            // Danh dau user da xac thuc email.
            $this->userModel->markEmailVerified((int) $verification['user_id'], $verifiedAt);

            // Danh dau ban ghi ma xac thuc da duoc su dung thanh cong.
            $this->emailVerificationModel->markVerified(
                (int) $verification['verification_id'],
                $verifiedAt,
                $verifiedAt
            );

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            ResponseHelper::error('Cannot verify email', 500);
            return;
        }

        ResponseHelper::success([
            'user' => [
                'id' => $verification['user_id'],
                'username' => $verification['username'],
                'email' => $verification['email'],
                'email_verified_at' => $verifiedAt,
            ],
        ], 'Email verified successfully');
    }

    /**
     * Dang nhap user.
     *
     * Input JSON:
     * - email: string
     * - password: string
     *
     * Output success:
     * - HTTP 200
     * - data.token: JWT token dung cho cac request can dang nhap
     * - data.token_type: Bearer
     * - data.expires_in: thoi gian song cua token tinh bang giay
     * - data.user: id, username, email, email_verified_at
     *
     * Output error:
     * - HTTP 400 neu body khong phai JSON hop le
     * - HTTP 422 neu thieu email/password hoac email sai dinh dang
     * - HTTP 401 neu email hoac password khong dung
     * - HTTP 403 neu email chua duoc xac thuc
     */
    public function login(): void
    {
        // Doc JSON body tu request va chuyen thanh mang PHP.
        $data = RequestHelper::getJsonBody();

        // Neu body khong phai JSON hop le thi tra ve loi 400 Bad Request.
        if ($data === null) {
            ResponseHelper::error('Invalid JSON body', 400);
            return;
        }

        // Lay email va password tu request.
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // Validate cac field bat buoc.
        if ($email === '' || $password === '') {
            ResponseHelper::error('Email and password are required', 422);
            return;
        }

        // Kiem tra email dung dinh dang.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error('Invalid email', 422);
            return;
        }

        // Lay user theo email de kiem tra thong tin dang nhap.
        $user = $this->userModel->findByEmail($email);

        // Khong noi ro email sai hay password sai de tranh lo thong tin user ton tai.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            ResponseHelper::error('Invalid credentials', 401);
            return;
        }

        // Chi cho dang nhap sau khi email da duoc xac thuc thanh cong.
        if ($user['email_verified_at'] === null) {
            ResponseHelper::error('Please verify your email before login', 403);
            return;
        }

        // Dang nhap thanh cong thi tao JWT cho client gui kem o cac request sau.
        $this->respondWithToken($user, 'Login successfully');
    }

    /**
     * Tra response authentication kem JWT token.
     *
     * Input:
     * - $user: array user lay tu database/model, co the gom password_hash
     * - $message: thong bao thanh cong
     * - $statusCode: HTTP status code muon tra ve
     *
     * Output JSON:
     * - success: true
     * - data.token: JWT token da ky bang JWT_SECRET
     * - data.token_type: Bearer
     * - data.expires_in: thoi gian token het han tinh bang giay
     * - data.user: thong tin public cua user, khong co password_hash
     */
    private function respondWithToken(array $user, string $message, int $statusCode = 200): void
    {
        // Chi dua thong tin public vao response va payload token.
        $publicUser = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'email_verified_at' => $user['email_verified_at'],
        ];

        // Jwt::create() se lay JWT_SECRET va JWT_EXPIRES_IN tu .env.
        $token = Jwt::create($publicUser);

        ResponseHelper::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) ($_ENV['JWT_EXPIRES_IN'] ?? 3600),
            'user' => $publicUser,
        ], $message, $statusCode);
    }
}
