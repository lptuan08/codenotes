<?php

namespace Core;

use RuntimeException;

class Jwt
{
    private const ALGORITHM = 'HS256';

    /**
     * Tao JWT token bang PHP thuan, khong dung thu vien ngoai.
     *
     * Input:
     * - $user: array user public data, nen gom id, username, email, email_verified_at
     *
     * Output:
     * - string JWT dang: header.payload.signature
     *
     * Ghi chu:
     * - JWT khong ma hoa payload, chi ky payload de phat hien token bi sua.
     * - Khong dua password hoac password_hash vao token.
     */
    public static function create(array $user): string
    {
        $now = time();
        $expiresIn = (int) ($_ENV['JWT_EXPIRES_IN'] ?? 3600);

        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];

        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'codenotes-api',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $expiresIn,
            'sub' => (string) ($user['id'] ?? ''),
            'user' => [
                'id' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'email' => $user['email'] ?? null,
                'email_verified_at' => $user['email_verified_at'] ?? null,
            ],
        ];

        $encodedHeader = self::base64UrlEncode(json_encode($header));
        $encodedPayload = self::base64UrlEncode(json_encode($payload));
        $signature = self::sign($encodedHeader . '.' . $encodedPayload);

        return $encodedHeader . '.' . $encodedPayload . '.' . $signature;
    }

    /**
     * Kiem tra JWT token co hop le khong.
     *
     * Input:
     * - $token: chuoi JWT client gui len, khong gom chu "Bearer "
     *
     * Output:
     * - array payload neu token hop le
     * - null neu token sai format, sai chu ky, chua den thoi diem dung, hoac het han
     */
    public static function verify(string $token): ?array
    {
        $parts = explode('.', trim($token));

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $signature] = $parts;

        $expectedSignature = self::sign($encodedHeader . '.' . $encodedPayload);

        // hash_equals giup so sanh chu ky an toan hon so voi ===.
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payloadJson = self::base64UrlDecode($encodedPayload);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        $now = time();

        // nbf = not before, token chua duoc dung truoc thoi diem nay.
        if (isset($payload['nbf']) && $now < (int) $payload['nbf']) {
            return null;
        }

        // exp = expires at, token het han sau thoi diem nay.
        if (isset($payload['exp']) && $now > (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    /**
     * Lay token tu header Authorization.
     *
     * Input:
     * - $authorizationHeader: vi du "Bearer eyJ..."
     *
     * Output:
     * - string token neu header dung format Bearer
     * - null neu thieu header hoac sai format
     */
    public static function getBearerToken(?string $authorizationHeader): ?string
    {
        if ($authorizationHeader === null) {
            return null;
        }

        $prefix = 'Bearer ';

        if (!str_starts_with($authorizationHeader, $prefix)) {
            return null;
        }

        return trim(substr($authorizationHeader, strlen($prefix)));
    }

    /**
     * Tao chu ky cho JWT bang HMAC SHA256.
     *
     * Input:
     * - $data: chuoi "header.payload"
     *
     * Output:
     * - signature da duoc base64url encode
     */
    private static function sign(string $data): string
    {
        $signature = hash_hmac('sha256', $data, self::secret(), true);

        return self::base64UrlEncode($signature);
    }

    /**
     * Lay JWT secret tu .env.
     *
     * Input:
     * - khong co
     *
     * Output:
     * - string secret neu da cau hinh
     *
     * Exception:
     * - RuntimeException neu thieu JWT_SECRET
     */
    private static function secret(): string
    {
        $secret = (string) ($_ENV['JWT_SECRET'] ?? '');

        if ($secret === '') {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }

        return $secret;
    }

    /**
     * Encode du lieu theo base64url dung chuan JWT.
     *
     * Input:
     * - $data: chuoi can encode
     *
     * Output:
     * - chuoi base64url khong co dau "=" o cuoi
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode base64url ve chuoi goc.
     *
     * Input:
     * - $data: chuoi base64url trong JWT
     *
     * Output:
     * - chuoi sau khi decode
     */
    private static function base64UrlDecode(string $data): string
    {
        $base64 = strtr($data, '-_', '+/');
        $padding = strlen($base64) % 4;

        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($base64) ?: '';
    }
}
