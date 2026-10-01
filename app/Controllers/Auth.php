<?php

namespace App\Controllers;

use App\Libraries\LocalUserStore;
use App\Libraries\TenantAuthRepository;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->has('auth_user')) {
            return redirect()->to('/');
        }

        if (! $this->users()->hasOwner()) {
            return redirect()->to('/setup');
        }

        return view('auth/login', [
            'error'  => session()->getFlashdata('error'),
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function attempt()
    {
        if (! $this->validate([
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|max_length[200]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = $this->users()->verify(
            (string) $this->request->getPost('email'),
            (string) $this->request->getPost('password'),
        );

        if ($user === null) {
            return redirect()->to('/login')->withInput()->with('error', 'Email atau kata sandi tidak sesuai.');
        }

        session()->regenerate(true);
        session()->set('auth_user', $user);

        return redirect()->to('/');
    }

    public function setup()
    {
        if ($this->users()->hasOwner()) {
            return redirect()->to('/login');
        }

        $viewData = [
            'errors' => session()->getFlashdata('errors') ?? [],
            'error'  => session()->getFlashdata('error'),
        ];

        if (strlen(config('Auth')->setupToken) < 32) {
            return $this->response->setStatusCode(503)->setBody(view('auth/setup', $viewData + ['setupLocked' => true]));
        }

        return view('auth/setup', $viewData + ['setupLocked' => false]);
    }

    public function createOwner()
    {
        $setupToken = config('Auth')->setupToken;
        $providedToken = (string) $this->request->getPost('setup_token');
        $setupLocked = strlen($setupToken) < 32;

        if ($setupLocked || ! hash_equals($setupToken, $providedToken)) {
            return $this->response->setStatusCode($setupLocked ? 503 : 403)->setBody(view('auth/setup', [
                'error'       => $setupLocked ? 'Setup dikunci. Atur auth.setupToken minimal 32 karakter di file .env.' : 'Token setup tidak valid.',
                'errors'      => [],
                'setupLocked' => $setupLocked,
            ]));
        }

        if (! $this->validate([
            'name'             => 'required|min_length[2]|max_length[80]',
            'email'            => 'required|valid_email|max_length[254]',
            'password'         => 'required|min_length[8]|max_length[200]',
            'password_confirm' => 'required|matches[password]',
            'setup_token'      => 'required|max_length[256]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name = (string) $this->request->getPost('name');
        $email = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        $createdUser = $this->users()->createOwner($name, $email, $password);
        if (! $createdUser) {
            return redirect()->to('/login')->with('error', 'Akun pemilik sudah dibuat. Silakan masuk.');
        }

        session()->regenerate(true);
        session()->set('auth_user', is_array($createdUser) ? $createdUser : [
            'name'  => trim($name),
            'email' => strtolower(trim($email)),
            'role'  => 'Owner',
        ]);

        return redirect()->to('/');
    }

    public function logout()
    {
        session()->remove('auth_user');
        session()->regenerate(true);

        return redirect()->to('/login');
    }

    private function users(): LocalUserStore|TenantAuthRepository
    {
        $database = config('Database')->default;

        return trim((string) ($database['database'] ?? '')) === ''
            ? new LocalUserStore()
            : new TenantAuthRepository();
    }
}