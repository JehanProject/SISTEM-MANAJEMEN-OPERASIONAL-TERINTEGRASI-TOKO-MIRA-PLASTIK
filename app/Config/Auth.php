<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Auth extends BaseConfig
{
    public string $setupToken = '';

    public int $loginMaxAttempts = 5;

    public int $loginWindowSeconds = 60;
}