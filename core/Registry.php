<?php

namespace Core;

class Registry
{
    private static array $items = [];

    /**
     * Luu mot gia tri vao Registry theo key.
     *
     * Input:
     * - $key: ten khoa can luu
     * - $value: gia tri muon luu, co the la bat ky kieu du lieu nao
     *
     * Output:
     * - Khong tra ve gia tri
     */
    public static function set(string $key, $value): void
    {
        self::$items[$key] = $value;
    }

    /**
     * Lay gia tri theo key tu Registry.
     *
     * Input:
     * - $key: ten khoa can lay
     * - $default: gia tri tra ve neu key khong ton tai
     *
     * Output:
     * - Gia tri da luu neu co
     * - $default neu key khong ton tai
     */
    public static function get(string $key, $default = null)
    {
        return self::$items[$key] ?? $default;
    }

    /**
     * Kiem tra Registry co chua mot key hay khong.
     *
     * Input:
     * - $key: ten khoa can kiem tra
     *
     * Output:
     * - true neu key ton tai
     * - false neu key khong ton tai
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$items);
    }

    /**
     * Xoa mot key khoi Registry.
     *
     * Input:
     * - $key: ten khoa can xoa
     *
     * Output:
     * - Khong tra ve gia tri
     */
    public static function remove(string $key): void
    {
        unset(self::$items[$key]);
    }

    /**
     * Xoa toan bo du lieu trong Registry.
     *
     * Input:
     * - Khong co
     *
     * Output:
     * - Khong tra ve gia tri
     */
    public static function clear(): void
    {
        self::$items = [];
    }
}
