<?php

namespace Core;

class JwtPayloadRegistry
{
    private const PAYLOAD_KEY = 'jwt.payload';
    private const USER_KEY = 'jwt.user';

    /**
     * Luu JWT payload va user da chuan hoa vao Registry.
     *
     * Input:
     * - $payload: mang payload JWT da duoc verify
     *
     * Output:
     * - Khong tra ve gia tri
     * - Luu payload vao key jwt.payload
     * - Luu user vao key jwt.user
     */
    public static function register(array $payload): void
    {
        Registry::set(self::PAYLOAD_KEY, $payload);
        Registry::set(self::USER_KEY, self::normalizeUser($payload['user'] ?? []));
    }

    /**
     * Lay toan bo JWT payload da duoc dang ky truoc do.
     *
     * Input:
     * - Khong co
     *
     * Output:
     * - array payload neu da register
     * - [] neu chua co payload trong Registry
     */
    public static function getPayload(): array
    {
        return Registry::get(self::PAYLOAD_KEY, []);
    }

    /**
     * Lay thong tin user da duoc chuan hoa tu JWT payload.
     *
     * Input:
     * - Khong co
     *
     * Output:
     * - array user neu da register va payload co user
     * - [] neu chua co user trong Registry
     */
    public static function getUser(): array
    {
        return Registry::get(self::USER_KEY, []);
    }

    /**
     * Lay mot claim cu the tu JWT payload.
     *
     * Input:
     * - $name: ten claim can lay
     * - $default: gia tri tra ve neu claim khong ton tai
     *
     * Output:
     * - Gia tri claim neu co
     * - $default neu khong co claim tuong ung
     */
    public static function getClaim(string $name, $default = null)
    {
        $payload = self::getPayload();

        return array_key_exists($name, $payload) ? $payload[$name] : $default;
    }

    /**
     * Lay user id tu payload hoac tu claim sub.
     *
     * Input:
     * - Khong co
     *
     * Output:
     * - int user id neu xac dinh duoc
     * - null neu khong co id hop le
     */
    public static function getUserId(): ?int
    {
        $user = self::getUser();
        $id = $user['id'] ?? self::getClaim('sub');

        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id;
    }

    /**
     * Xoa payload va user da luu trong Registry.
     *
     * Input:
     * - Khong co
     *
     * Output:
     * - Khong tra ve gia tri
     */
    public static function clear(): void
    {
        Registry::remove(self::PAYLOAD_KEY);
        Registry::remove(self::USER_KEY);
    }

    /**
     * Dam bao du lieu user luon la mang hop le.
     *
     * Input:
     * - $user: du lieu user co the la array hoac kieu khac
     *
     * Output:
     * - array neu input la array
     * - [] neu input khong hop le
     */
    private static function normalizeUser($user): array
    {
        return is_array($user) ? $user : [];
    }
}
