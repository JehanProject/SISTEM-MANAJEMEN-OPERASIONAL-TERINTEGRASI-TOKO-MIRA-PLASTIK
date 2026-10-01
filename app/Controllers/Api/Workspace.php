<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\TenantWorkspaceRepository;
use App\Libraries\WorkspaceAccessException;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

class Workspace extends BaseController
{
    public function index(): ResponseInterface
    {
        try {
            return $this->response->setJSON($this->repository()->workspace($this->actor()));
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function createProduct(): ResponseInterface
    {
        try {
            $product = $this->repository()->createProduct($this->actor(), $this->jsonInput());

            return $this->response->setStatusCode(201)->setJSON(['product' => $product]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function adjustStock(): ResponseInterface
    {
        try {
            $product = $this->repository()->adjustStock($this->actor(), $this->jsonInput());

            return $this->response->setJSON(['product' => $product]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function checkout(): ResponseInterface
    {
        try {
            $transaction = $this->repository()->checkout($this->actor(), $this->jsonInput());

            return $this->response->setStatusCode(201)->setJSON(['transaction' => $transaction]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function createSupplier(): ResponseInterface
    {
        try {
            $supplier = $this->repository()->createSupplier($this->actor(), $this->jsonInput());

            return $this->response->setStatusCode(201)->setJSON(['supplier' => $supplier]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function createExpense(): ResponseInterface
    {
        try {
            $expense = $this->repository()->createExpense($this->actor(), $this->jsonInput());

            return $this->response->setStatusCode(201)->setJSON(['expense' => $expense]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function createDebt(): ResponseInterface
    {
        try {
            $debt = $this->repository()->createDebt($this->actor(), $this->jsonInput());

            return $this->response->setStatusCode(201)->setJSON(['debt' => $debt]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function payDebt(): ResponseInterface
    {
        try {
            $payment = $this->repository()->payDebt($this->actor(), $this->jsonInput());

            return $this->response->setJSON(['payment' => $payment]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function archiveReport(): ResponseInterface
    {
        try {
            $archive = $this->repository()->archiveReport($this->actor(), $this->jsonInput());

            return $this->response->setStatusCode(201)->setJSON(['archive' => $archive]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    private function repository(): TenantWorkspaceRepository
    {
        return new TenantWorkspaceRepository();
    }

    /** @return array<string, mixed> */
    private function actor(): array
    {
        $actor = session()->get('auth_user');
        if (! is_array($actor)) {
            throw new WorkspaceAccessException('Sesi login tidak valid.');
        }

        return $actor;
    }

    /** @return array<string, mixed> */
    private function jsonInput(): array
    {
        $input = $this->request->getJSON(true);
        if (! is_array($input)) {
            throw new DomainException('Isi request JSON tidak valid.');
        }

        return $input;
    }

    private function errorResponse(Throwable $exception): ResponseInterface
    {
        if ($exception instanceof WorkspaceAccessException) {
            return $this->response->setStatusCode(403)->setJSON(['error' => $exception->getMessage()]);
        }

        if ($exception instanceof DomainException) {
            return $this->response->setStatusCode(422)->setJSON(['error' => $exception->getMessage()]);
        }

        if ($exception instanceof DatabaseException) {
            log_message('error', 'Workspace database request failed: {message}', ['message' => $exception->getMessage()]);
            return $this->response->setStatusCode(503)->setJSON(['error' => 'Database belum siap. Periksa konfigurasi .env dan jalankan migration.']);
        }

        log_message('error', 'Workspace API request failed: {message}', ['message' => $exception->getMessage()]);

        return $this->response->setStatusCode(500)->setJSON(['error' => 'Permintaan tidak dapat diproses.']);
    }
}