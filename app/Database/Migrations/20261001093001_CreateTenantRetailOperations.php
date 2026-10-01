<?php

namespace App\Database\Migrations;

class CreateTenantRetailOperations extends TenantSchemaMigration
{
    public function up()
    {
        $this->createAppTable(
            'product_categories',
            $this->idField() + $this->tenantIdField() + [
                'parent_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'name'      => ['type' => 'VARCHAR', 'constraint' => 120],
                'slug'      => ['type' => 'VARCHAR', 'constraint' => 120],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'slug']],
            [],
            [
                $this->tenantForeignKey('fk_categories_tenant'),
                $this->foreignKey('fk_categories_parent', ['tenant_id', 'parent_id'], 'product_categories', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'products',
            $this->idField() + $this->tenantIdField() + [
                'category_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'sku'               => ['type' => 'VARCHAR', 'constraint' => 64],
                'name'              => ['type' => 'VARCHAR', 'constraint' => 180],
                'unit'              => ['type' => 'VARCHAR', 'constraint' => 30],
                'minimum_stock'     => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
                'cost_rupiah'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
                'retail_rupiah'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
                'wholesale_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
                'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'sku']],
            [['tenant_id', 'category_id']],
            [
                $this->tenantForeignKey('fk_products_tenant'),
                $this->foreignKey('fk_products_category', ['tenant_id', 'category_id'], 'product_categories', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'customers',
            $this->idField() + $this->tenantIdField() + [
                'name'   => ['type' => 'VARCHAR', 'constraint' => 160],
                'phone'  => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'name']],
            [$this->tenantForeignKey('fk_customers_tenant')],
        );

        $this->createAppTable(
            'suppliers',
            $this->idField() + $this->tenantIdField() + [
                'name'           => ['type' => 'VARCHAR', 'constraint' => 160],
                'contact_name'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'phone'          => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'payment_terms'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'name']],
            [$this->tenantForeignKey('fk_suppliers_tenant')],
        );

        $this->createAppTable(
            'store_inventory',
            $this->tenantIdField() + [
                'store_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'product_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'quantity'  => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 0],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ],
            ['tenant_id', 'store_id', 'product_id'],
            [],
            [],
            [
                $this->foreignKey('fk_inventory_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id'], 'CASCADE'),
                $this->foreignKey('fk_inventory_product', ['tenant_id', 'product_id'], 'products', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'stock_movements',
            $this->idField() + $this->tenantIdField() + [
                'store_id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'product_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'movement_type'      => ['type' => 'VARCHAR', 'constraint' => 30],
                'quantity_delta'     => ['type' => 'DECIMAL', 'constraint' => '14,3'],
                'unit_cost_rupiah'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'source_type'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'source_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'actor_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'created_at'         => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'product_id', 'created_at'], ['tenant_id', 'source_type', 'source_id']],
            [
                $this->foreignKey('fk_movements_inventory', ['tenant_id', 'store_id', 'product_id'], 'store_inventory', ['tenant_id', 'store_id', 'product_id']),
                $this->foreignKey('fk_movements_actor', ['tenant_id', 'actor_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'cash_shifts',
            $this->idField() + $this->tenantIdField() + [
                'store_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'opened_by_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
                'opening_cash_rupiah' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
                'expected_cash_rupiah' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'counted_cash_rupiah' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'cash_difference_rupiah' => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
                'opened_at'           => ['type' => 'DATETIME'],
                'closed_at'           => ['type' => 'DATETIME', 'null' => true],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'store_id', 'id']],
            [['tenant_id', 'store_id', 'status']],
            [
                $this->foreignKey('fk_shifts_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id']),
                $this->foreignKey('fk_shifts_member', ['tenant_id', 'opened_by_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'receipt_sequences',
            $this->tenantIdField() + [
                'store_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'business_date'  => ['type' => 'DATE'],
                'next_number'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 1],
            ],
            ['tenant_id', 'store_id', 'business_date'],
            [],
            [],
            [$this->foreignKey('fk_receipt_sequences_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id'], 'CASCADE')],
        );

        $this->createAppTable(
            'transactions',
            $this->idField() + $this->tenantIdField() + [
                'store_id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'receipt_no'             => ['type' => 'VARCHAR', 'constraint' => 40],
                'cashier_membership_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'customer_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'cash_shift_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'status'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'completed'],
                'subtotal_rupiah'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'discount_rupiah'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
                'total_rupiah'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'created_at'             => ['type' => 'DATETIME', 'null' => true],
                'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'store_id', 'id'], ['tenant_id', 'store_id', 'receipt_no']],
            [['tenant_id', 'store_id', 'created_at']],
            [
                $this->foreignKey('fk_transactions_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id']),
                $this->foreignKey('fk_transactions_cashier', ['tenant_id', 'cashier_membership_id'], 'memberships', ['tenant_id', 'id']),
                $this->foreignKey('fk_transactions_customer', ['tenant_id', 'customer_id'], 'customers', ['tenant_id', 'id']),
                $this->foreignKey('fk_transactions_shift', ['tenant_id', 'store_id', 'cash_shift_id'], 'cash_shifts', ['tenant_id', 'store_id', 'id']),
            ],
        );

        $this->createAppTable(
            'transaction_items',
            $this->idField() + $this->tenantIdField() + [
                'store_id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'transaction_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'product_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'quantity'           => ['type' => 'DECIMAL', 'constraint' => '14,3'],
                'unit_price_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'unit_cost_rupiah'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'discount_rupiah'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
                'line_total_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'store_id', 'transaction_id', 'id']],
            [['tenant_id', 'product_id']],
            [
                $this->foreignKey('fk_transaction_items_transaction', ['tenant_id', 'store_id', 'transaction_id'], 'transactions', ['tenant_id', 'store_id', 'id'], 'CASCADE'),
                $this->foreignKey('fk_transaction_items_product', ['tenant_id', 'product_id'], 'products', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'transaction_payments',
            $this->idField() + $this->tenantIdField() + [
                'store_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'transaction_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'method'         => ['type' => 'VARCHAR', 'constraint' => 30],
                'amount_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'reference'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'transaction_id']],
            [$this->foreignKey('fk_transaction_payments_transaction', ['tenant_id', 'store_id', 'transaction_id'], 'transactions', ['tenant_id', 'store_id', 'id'], 'CASCADE')],
        );

        $this->createAppTable(
            'sales_returns',
            $this->idField() + $this->tenantIdField() + [
                'store_id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'transaction_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'return_no'             => ['type' => 'VARCHAR', 'constraint' => 40],
                'reason'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'approved'],
                'approved_by_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'created_at'            => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'store_id', 'id'], ['tenant_id', 'store_id', 'return_no'], ['tenant_id', 'store_id', 'id', 'transaction_id']],
            [],
            [
                $this->foreignKey('fk_returns_transaction', ['tenant_id', 'store_id', 'transaction_id'], 'transactions', ['tenant_id', 'store_id', 'id']),
                $this->foreignKey('fk_returns_approver', ['tenant_id', 'approved_by_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'sales_return_items',
            $this->idField() + $this->tenantIdField() + [
                'store_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'sales_return_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'transaction_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'transaction_item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'quantity'            => ['type' => 'DECIMAL', 'constraint' => '14,3'],
                'amount_rupiah'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'sales_return_id']],
            [
                $this->foreignKey('fk_return_items_return', ['tenant_id', 'store_id', 'sales_return_id', 'transaction_id'], 'sales_returns', ['tenant_id', 'store_id', 'id', 'transaction_id'], 'CASCADE'),
                $this->foreignKey('fk_return_items_sale_item', ['tenant_id', 'store_id', 'transaction_id', 'transaction_item_id'], 'transaction_items', ['tenant_id', 'store_id', 'transaction_id', 'id']),
            ],
        );

        $this->createAppTable(
            'refund_payments',
            $this->idField() + $this->tenantIdField() + [
                'store_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'sales_return_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'method'          => ['type' => 'VARCHAR', 'constraint' => 30],
                'amount_rupiah'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'reference'       => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'sales_return_id']],
            [$this->foreignKey('fk_refund_payments_return', ['tenant_id', 'store_id', 'sales_return_id'], 'sales_returns', ['tenant_id', 'store_id', 'id'], 'CASCADE')],
        );
    }

    public function down()
    {
        $this->forge->dropTable('refund_payments', true);
        $this->forge->dropTable('sales_return_items', true);
        $this->forge->dropTable('sales_returns', true);
        $this->forge->dropTable('transaction_payments', true);
        $this->forge->dropTable('transaction_items', true);
        $this->forge->dropTable('transactions', true);
        $this->forge->dropTable('receipt_sequences', true);
        $this->forge->dropTable('cash_shifts', true);
        $this->forge->dropTable('stock_movements', true);
        $this->forge->dropTable('store_inventory', true);
        $this->forge->dropTable('suppliers', true);
        $this->forge->dropTable('customers', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('product_categories', true);
    }
}