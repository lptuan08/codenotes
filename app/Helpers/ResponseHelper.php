<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Tra ve response thanh cong cho API.
     *
     * Input:
     * - $data: du lieu muon tra ve cho client
     * - $message: thong bao ngan gon
     * - $statusCode: HTTP status code, mac dinh 200
     *
     * Output JSON:
     * - success: true
     * - message: string
     * - data: array
     */
    public static function success(array $data = [], string $message = 'Success', int $statusCode = 200): void
    {
        http_response_code($statusCode);

        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Tra ve response that bai cho API.
     *
     * Input:
     * - $message: thong bao loi ngan gon
     * - $statusCode: HTTP status code, vi du 400, 404, 422, 500
     * - $errors: chi tiet loi neu co, thuong dung cho validate
     *
     * Output JSON:
     * - success: false
     * - message: string
     * - errors: array
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        http_response_code($statusCode);

        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE);
    }
}
