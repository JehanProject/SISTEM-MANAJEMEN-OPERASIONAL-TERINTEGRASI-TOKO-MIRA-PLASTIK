<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $actor = session()->get('auth_user');
        if (! is_array($actor) || ! isset($actor['user_id'], $actor['membership_id'], $actor['tenant_id'], $actor['store_id'])) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Sesi tenant tidak valid.']);
        }

        if ($arguments === null || $arguments === []) {
            return service('response')->setStatusCode(403)->setJSON(['error' => 'Izin route belum dikonfigurasi.']);
        }

        $db = db_connect();
        $membership = $db->table('memberships')
            ->where('id', $actor['membership_id'])
            ->where('tenant_id', $actor['tenant_id'])
            ->where('user_id', $actor['user_id'])
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if ($membership === null) {
            return service('response')->setStatusCode(403)->setJSON(['error' => 'Membership tidak aktif.']);
        }

        $roleIds = [];
        if ($membership['tenant_role_id'] !== null) {
            $roleIds[] = (int) $membership['tenant_role_id'];
        }
        foreach ($db->table('store_memberships')
            ->select('role_id')
            ->where('tenant_id', $actor['tenant_id'])
            ->where('membership_id', $actor['membership_id'])
            ->where('store_id', $actor['store_id'])
            ->where('status', 'active')
            ->get()->getResultArray() as $assignment) {
            $roleIds[] = (int) $assignment['role_id'];
        }

        if ($roleIds === []) {
            return service('response')->setStatusCode(403)->setJSON(['error' => 'Role aktif tidak ditemukan.']);
        }

        $permitted = $db->table('role_permissions')
            ->where('tenant_id', $actor['tenant_id'])
            ->whereIn('role_id', array_unique($roleIds))
            ->whereIn('permission_code', $arguments)
            ->countAllResults() > 0;

        if (! $permitted) {
            return service('response')->setStatusCode(403)->setJSON(['error' => 'Anda tidak memiliki izin untuk endpoint ini.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}