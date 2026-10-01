<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $user = session()->get('auth_user') ?? [];

        return view('welcome_message', [
            'user'         => $user,
            'databaseMode' => isset($user['tenant_id'], $user['store_id']),
        ]);
    }
}
