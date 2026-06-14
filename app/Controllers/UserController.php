<?php

namespace App\Controllers;
use Core\Database;
class UserController
{
    private $database;
    public function __construct()
    {
        // $this->database = new Database();
    }
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
        $a = Database::connection();
        var_dump($a);

    }
}
