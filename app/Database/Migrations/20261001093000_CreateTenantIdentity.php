<?php

namespace App\Database\Migrations;

class CreateTenantIdentity extends TenantSchemaMigration
{
    public function up()
    {
        $this->createAppTable(
            'tenants',
            $this->idField() + [
                'name'       => ['type' => 'VARCHAR', 'constraint' => 160],
                'slug'       => ['type' => 'VARCHAR', 'constraint' => 80],
                'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            ] + $this->timestamps(),
            ['id'],
            [['slug']],
        );

        $this->createAppTable(
            'stores',
            $this->idField() + $this->tenantIdField() + [
                'code'     => ['type' => 'VARCHAR', 'constraint' => 40],
                'name'     => ['type' => 'VARCHAR', 'constraint' => 160],
                'timezone' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Asia/Jakarta'],
                'status'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'code']],
            [],
            [$this->tenantForeignKey('fk_stores_tenant')],
        );

        $this->createAppTable(
            'users',
            $this->idField() + [
                'email'         => ['type' => 'VARCHAR', 'constraint' => 254],
                'name'          => ['type' => 'VARCHAR', 'constraint' => 120],
                'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
                'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
                'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            ] + $this->timestamps(),
            ['id'],
            [['email']],
        );

        $this->createAppTable(
            'roles',
            $this->idField() + $this->tenantIdField() + [
                'code'  => ['type' => 'VARCHAR', 'constraint' => 64],
                'name'  => ['type' => 'VARCHAR', 'constraint' => 100],
                'scope' => ['type' => 'VARCHAR', 'constraint' => 10],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'id', 'scope'], ['tenant_id', 'scope', 'code']],
            [],
            [$this->tenantForeignKey('fk_roles_tenant')],
        );

        $this->createAppTable(
            'permissions',
            [
                'code'        => ['type' => 'VARCHAR', 'constraint' => 64],
                'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            ],
            ['code'],
        );

        $this->db->table('permissions')->insertBatch([
            ['code' => 'pos.sell', 'description' => 'Membuat transaksi penjualan'],
            ['code' => 'pos.void', 'description' => 'Membatalkan transaksi penjualan'],
            ['code' => 'product.manage', 'description' => 'Mengelola katalog produk'],
            ['code' => 'stock.view', 'description' => 'Melihat stok toko'],
            ['code' => 'stock.adjust', 'description' => 'Mencatat penyesuaian stok'],
            ['code' => 'purchase.receive', 'description' => 'Menerima barang pembelian'],
            ['code' => 'supplier.manage', 'description' => 'Mengelola pemasok'],
            ['code' => 'cash.manage', 'description' => 'Mencatat arus kas'],
            ['code' => 'report.archive', 'description' => 'Mengarsipkan laporan'],
            ['code' => 'finance.view', 'description' => 'Melihat data keuangan'],
            ['code' => 'finance.manage', 'description' => 'Mengelola utang, piutang, dan pembayaran'],
            ['code' => 'report.export', 'description' => 'Mengekspor laporan'],
            ['code' => 'user.manage', 'description' => 'Mengelola pengguna dan peran'],
        ]);

        $this->createAppTable(
            'role_permissions',
            $this->tenantIdField() + [
                'role_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'permission_code' => ['type' => 'VARCHAR', 'constraint' => 64],
            ],
            ['tenant_id', 'role_id', 'permission_code'],
            [],
            [],
            [
                [
                    'columns'    => ['tenant_id', 'role_id'],
                    'table'      => 'roles',
                    'references' => ['tenant_id', 'id'],
                    'name'       => 'fk_role_permissions_role',
                ],
                [
                    'columns'    => ['permission_code'],
                    'table'      => 'permissions',
                    'references' => ['code'],
                    'name'       => 'fk_role_permissions_permission',
                ],
            ],
        );

        $this->createAppTable(
            'memberships',
            $this->idField() + $this->tenantIdField() + [
                'user_id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'tenant_role_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'tenant_role_scope VARCHAR(10) NOT NULL DEFAULT \'tenant\' CHECK (tenant_role_scope = \'tenant\')',
                'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'user_id']],
            [],
            [
                $this->tenantForeignKey('fk_memberships_tenant'),
                [
                    'columns'    => ['user_id'],
                    'table'      => 'users',
                    'references' => ['id'],
                    'name'       => 'fk_memberships_user',
                ],
                [
                    'columns'    => ['tenant_id', 'tenant_role_id', 'tenant_role_scope'],
                    'table'      => 'roles',
                    'references' => ['tenant_id', 'id', 'scope'],
                    'name'       => 'fk_memberships_tenant_role',
                ],
            ],
        );

        $this->createAppTable(
            'store_memberships',
            $this->tenantIdField() + [
                'membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'store_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'role_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'role_scope VARCHAR(10) NOT NULL DEFAULT \'store\' CHECK (role_scope = \'store\')',
                'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            ] + $this->timestamps(),
            ['tenant_id', 'membership_id', 'store_id', 'role_id'],
            [],
            [['tenant_id', 'store_id'], ['tenant_id', 'membership_id']],
            [
                [
                    'columns'    => ['tenant_id', 'membership_id'],
                    'table'      => 'memberships',
                    'references' => ['tenant_id', 'id'],
                    'onDelete'   => 'CASCADE',
                    'name'       => 'fk_store_memberships_membership',
                ],
                [
                    'columns'    => ['tenant_id', 'store_id'],
                    'table'      => 'stores',
                    'references' => ['tenant_id', 'id'],
                    'onDelete'   => 'CASCADE',
                    'name'       => 'fk_store_memberships_store',
                ],
                [
                    'columns'    => ['tenant_id', 'role_id', 'role_scope'],
                    'table'      => 'roles',
                    'references' => ['tenant_id', 'id', 'scope'],
                    'name'       => 'fk_store_memberships_role',
                ],
            ],
        );
    }

    public function down()
    {
        $this->forge->dropTable('store_memberships', true);
        $this->forge->dropTable('memberships', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('stores', true);
        $this->forge->dropTable('tenants', true);
    }
}