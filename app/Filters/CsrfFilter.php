<?php

namespace App\Filters;

use CodeIgniter\Filters\CSRF;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CsrfFilter extends CSRF
{
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if ($request instanceof IncomingRequest && str_starts_with($request->getUri()->getPath(), '/api/')) {
            $token = service('security')->getHash();
            if ($token !== null) {
                $response->setHeader(config('Security')->headerName, $token);
            }
        }

        return parent::after($request, $response, $arguments);
    }
}