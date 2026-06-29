<?php

namespace App\Helpers;

class RequestHelper
{
    /**
     * Doc JSON body tu request va tra ve mang PHP.
     *
     * Output:
     * - array neu body hop le va decode thanh cong
     * - null neu body khong phai JSON hop le hoac body rong
     */
    public static function getJsonBody(): ?array
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            return null;
        }

        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Lay header Authorization tu request bang nhieu cach de ho tro Apache/WAMP.
     *
     * Output:
     * - string header neu tim thay
     * - null neu khong co
     */
    public static function getAuthorizationHeader(): ?string
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (!empty($headers['Authorization'])) {
                return $headers['Authorization'];
            }

            if (!empty($headers['authorization'])) {
                return $headers['authorization'];
            }
        }

        return null;
    }
}