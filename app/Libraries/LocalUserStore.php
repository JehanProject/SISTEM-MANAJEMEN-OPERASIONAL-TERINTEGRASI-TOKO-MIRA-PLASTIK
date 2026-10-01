<?php

namespace App\Libraries;

use RuntimeException;

class LocalUserStore
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? WRITEPATH . 'auth' . DIRECTORY_SEPARATOR . 'users.json';
    }

    public function hasOwner(): bool
    {
        return $this->withFile(LOCK_SH, static fn (array $users): bool => $users !== []);
    }

    public function createOwner(string $name, string $email, string $password): bool
    {
        return $this->withFile(LOCK_EX, function (array $users, $handle) use ($name, $email, $password): bool {
            if ($users !== []) {
                return false;
            }

            $users[] = [
                'name'          => trim($name),
                'email'         => strtolower(trim($email)),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => 'Owner',
            ];

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($users, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            fflush($handle);
            chmod($this->path, 0600);

            return true;
        });
    }

    /** @return array{name: string, email: string, role: string}|null */
    public function verify(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));

        return $this->withFile(LOCK_SH, static function (array $users) use ($email, $password): ?array {
            foreach ($users as $user) {
                if (isset($user['email'], $user['password_hash'])
                    && hash_equals($user['email'], $email)
                    && password_verify($password, $user['password_hash'])) {
                    return [
                        'name'  => $user['name'],
                        'email' => $user['email'],
                        'role'  => $user['role'],
                    ];
                }
            }

            return null;
        });
    }

    private function withFile(int $lock, callable $callback): mixed
    {
        $directory = dirname($this->path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the authentication storage directory.');
        }

        $handle = fopen($this->path, 'c+');
        if ($handle === false || ! flock($handle, $lock)) {
            throw new RuntimeException('Unable to access the authentication store.');
        }

        try {
            rewind($handle);
            $contents = stream_get_contents($handle);
            $users = $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($users)) {
                throw new RuntimeException('The authentication store is invalid.');
            }

            return $callback($users, $handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}