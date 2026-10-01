<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class TenantAuthRepository
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function hasOwner(): bool
    {
        return $this->db->table('tenants')->countAllResults() > 0;
    }

    /** @return array<string, int|string>|null */
    public function createOwner(string $name, string $email, string $password): ?array
    {
        $this->db->transBegin();

        try {
            if ($this->hasOwner()) {
                $this->db->transRollback();

                return null;
            }

            $now = date('Y-m-d H:i:s');
            $this->db->table('tenants')->insert([
                'name'       => 'Toko Mira Plastik',
                'slug'       => 'mira-plastik',
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $tenantId = (int) $this->db->insertID();

            $this->db->table('stores')->insert([
                'tenant_id'  => $tenantId,
                'code'       => 'MAIN',
                'name'       => 'Cabang utama',
                'timezone'   => 'Asia/Jakarta',
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $storeId = (int) $this->db->insertID();

            $normalizedEmail = strtolower(trim($email));
            $this->db->table('users')->insert([
                'email'         => $normalizedEmail,
                'name'          => trim($name),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $userId = (int) $this->db->insertID();

            $this->db->table('roles')->insert([
                'tenant_id'  => $tenantId,
                'code'       => 'owner',
                'name'       => 'Owner',
                'scope'      => 'tenant',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleId = (int) $this->db->insertID();

            $this->db->table('memberships')->insert([
                'tenant_id'        => $tenantId,
                'user_id'          => $userId,
                'tenant_role_id'   => $roleId,
                'tenant_role_scope' => 'tenant',
                'status'           => 'active',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $membershipId = (int) $this->db->insertID();

            $permissionCodes = $this->db->table('permissions')->select('code')->get()->getResultArray();
            if ($permissionCodes !== []) {
                $this->db->table('role_permissions')->insertBatch(array_map(
                    static fn (array $permission): array => [
                        'tenant_id'      => $tenantId,
                        'role_id'        => $roleId,
                        'permission_code' => $permission['code'],
                    ],
                    $permissionCodes,
                ));
            }

            if (! $this->db->transStatus()) {
                throw new RuntimeException('Unable to create the owner account.');
            }

            $this->db->transCommit();

            return [
                'user_id'       => $userId,
                'membership_id' => $membershipId,
                'tenant_id'     => $tenantId,
                'tenant_name'   => 'Toko Mira Plastik',
                'store_id'      => $storeId,
                'store_name'    => 'Cabang utama',
                'name'          => trim($name),
                'email'         => $normalizedEmail,
                'role'          => 'Owner',
            ];
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    /** @return array<string, int|string>|null */
    public function verify(string $email, string $password): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        $user = $this->db->table('users')
            ->where('email', $normalizedEmail)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return null;
        }

        $memberships = $this->db->table('memberships')
            ->where('user_id', $user['id'])
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($memberships as $membership) {
            $context = $this->membershipContext($user, $membership);
            if ($context !== null) {
                $this->db->table('users')->where('id', $user['id'])->update(['last_login_at' => date('Y-m-d H:i:s')]);

                return $context;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $user @param array<string, mixed> $membership
     *  @return array<string, int|string>|null
     */
    private function membershipContext(array $user, array $membership): ?array
    {
        $role = null;
        $store = null;

        if ($membership['tenant_role_id'] !== null) {
            $role = $this->db->table('roles')
                ->where('tenant_id', $membership['tenant_id'])
                ->where('id', $membership['tenant_role_id'])
                ->where('scope', 'tenant')
                ->get()
                ->getRowArray();

            $store = $this->db->table('stores')
                ->where('tenant_id', $membership['tenant_id'])
                ->where('status', 'active')
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        } else {
            $assignment = $this->db->table('store_memberships')
                ->select('roles.name AS role_name, stores.id AS store_id, stores.name AS store_name')
                ->join('roles', 'roles.tenant_id = store_memberships.tenant_id AND roles.id = store_memberships.role_id AND roles.scope = store_memberships.role_scope')
                ->join('stores', 'stores.tenant_id = store_memberships.tenant_id AND stores.id = store_memberships.store_id')
                ->where('store_memberships.tenant_id', $membership['tenant_id'])
                ->where('store_memberships.membership_id', $membership['id'])
                ->where('store_memberships.status', 'active')
                ->where('stores.status', 'active')
                ->orderBy('stores.id', 'ASC')
                ->get()
                ->getRowArray();

            if ($assignment === null) {
                return null;
            }

            $role = ['name' => $assignment['role_name']];
            $store = ['id' => $assignment['store_id'], 'name' => $assignment['store_name']];
        }

        if ($role === null || $store === null) {
            return null;
        }

        $tenant = $this->db->table('tenants')->where('id', $membership['tenant_id'])->where('status', 'active')->get()->getRowArray();
        if ($tenant === null) {
            return null;
        }

        return [
            'user_id'       => (int) $user['id'],
            'membership_id' => (int) $membership['id'],
            'tenant_id'     => (int) $membership['tenant_id'],
            'tenant_name'   => $tenant['name'],
            'store_id'      => (int) $store['id'],
            'store_name'    => $store['name'],
            'name'          => $user['name'],
            'email'         => $user['email'],
            'role'          => $role['name'],
        ];
    }
}