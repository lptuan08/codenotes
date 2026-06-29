<?php

namespace App\Controllers;

use App\Models\User;
use App\Helpers\ResponseHelper;
use Core\JwtPayloadRegistry;

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function index(): void
    {
        $users = $this->userModel->findAllPublic();

        ResponseHelper::success([
            'request_user' => JwtPayloadRegistry::getUser(),
            'token_payload' => JwtPayloadRegistry::getPayload(),
            'users' => $users,
        ], 'Token valid');
    }

    public function store(): void
    {
        ResponseHelper::success([
            'request_user' => JwtPayloadRegistry::getUser(),
            'token_payload' => JwtPayloadRegistry::getPayload(),
            'message_for_example' => 'Day la cach dung JWT trong store() de bao ve API',
        ], 'Token valid');
    }

    public function show(string $id): void
    {
        ResponseHelper::success([
            'id' => $id,
            'request_user_id' => JwtPayloadRegistry::getUserId(),
            'message_for_example' => 'Day la endpoint mau cho show()'], 'User detail example');
    }
}
