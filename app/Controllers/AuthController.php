<?php

namespace App\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;

class AuthController
{
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
     * - data.user gom id, username, email
     *
     * Output error:
     * - HTTP 400 neu body khong phai JSON hop le
     * - HTTP 422 neu du lieu dau vao khong hop le
     * - HTTP 409 neu email da ton tai
     */

    // register(): Đọc json chuyển sang array -> validate -> xử lý dữ liệu (hashpass) -> tạo dữ liệu thông qua model
    public function register(): void
    {   
        // Doc JSON body tu request va chuyen thanh mang PHP.
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Neu body khong phai JSON hop le thi tra ve loi 400 Bad Request.
        if (!is_array($data)) {
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

        // Model User chiu trach nhiem thao tac voi bang users.
        $userModel = new User();

        // Kiem tra email da ton tai chua de tranh trung user.
        if ($userModel->findByEmail($email) !== null) {
            ResponseHelper::error('Email already exists', 409);
            return;
        }

        // Hash password truoc khi luu, khong bao gio luu password goc vao database.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Tao user moi thong qua model, controller khong viet SQL truc tiep.
        $user = $userModel->create($username, $email, $passwordHash);

        // Tra ve thong tin public cua user, khong tra password_hash.
        ResponseHelper::success([
            'user' => $user,
        ], 'User registered successfully', 201);
    }
}
