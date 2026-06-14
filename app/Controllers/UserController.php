<?php

namespace App\Controllers;

class UserController
{
    public function index(): void
    {
        echo 'API index';
    }

    public function store(): void
    {
        echo 'API store';
    }

    public function show(string $id): void
    {
        echo 'show duoc goi ' . $id;
    }
}
