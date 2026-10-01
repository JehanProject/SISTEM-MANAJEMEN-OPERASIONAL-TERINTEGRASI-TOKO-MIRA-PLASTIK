<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LoginThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = service('throttler');
        $key = 'login:' . $request->getIPAddress();
        $authConfig = config('Auth');

        if (! $throttler->check($key, $authConfig->loginMaxAttempts, $authConfig->loginWindowSeconds)) {
            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $throttler->getTokenTime())
                ->setBody('Terlalu banyak percobaan masuk. Coba lagi sebentar.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}