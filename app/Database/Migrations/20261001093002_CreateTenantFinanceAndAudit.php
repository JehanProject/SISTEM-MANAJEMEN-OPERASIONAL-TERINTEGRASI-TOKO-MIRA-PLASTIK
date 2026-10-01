<?php

namespace App\Database\Migrations;

class CreateTenantFinanceAndAudit extends TenantSchemaMigration
{
    public function up()
    {
        $this->createAppTable(
            'purchase_orders',
            $this->idField() + $this->tenantIdField() + [
                'store_id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'supplier_id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'created_by_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'order_no'              => ['type' => 'VARCHAR', 'constraint' => 40],
                'status'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
                'expected_on'           => ['type' => 'DATE', 'null' => true],
                'total_rupiah'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'store_id', 'id'], ['tenant_id', 'store_id', 'order_no']],
            [['tenant_id', 'store_id', 'status']],
            [
                $this->foreignKey('fk_purchase_orders_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id']),
                $this->foreignKey('fk_purchase_orders_supplier', ['tenant_id', 'supplier_id'], 'suppliers', ['tenant_id', 'id']),
                $this->foreignKey('fk_purchase_orders_member', ['tenant_id', 'created_by_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'purchase_order_items',
            $this->idField() + $this->tenantIdField() + [
                'store_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'purchase_order_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'product_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'ordered_quantity'  => ['type' => 'DECIMAL', 'constraint' => '14,3'],
                'unit_cost_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'line_total_rupiah' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'purchase_order_id']],
            [
                $this->foreignKey('fk_po_items_order', ['tenant_id', 'store_id', 'purchase_order_id'], 'purchase_orders', ['tenant_id', 'store_id', 'id'], 'CASCADE'),
                $this->foreignKey('fk_po_items_product', ['tenant_id', 'product_id'], 'products', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'goods_receipts',
            $this->idField() + $this->tenantIdField() + [
                'store_id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'purchase_order_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'received_by_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'receipt_no'             => ['type' => 'VARCHAR', 'constraint' => 40],
                'status'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
                'received_at'            => ['type' => 'DATETIME', 'null' => true],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'store_id', 'id'], ['tenant_id', 'store_id', 'receipt_no']],
            [],
            [
                $this->foreignKey('fk_receipts_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id']),
                $this->foreignKey('fk_receipts_order', ['tenant_id', 'store_id', 'purchase_order_id'], 'purchase_orders', ['tenant_id', 'store_id', 'id']),
                $this->foreignKey('fk_receipts_member', ['tenant_id', 'received_by_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'goods_receipt_items',
            $this->idField() + $this->tenantIdField() + [
                'store_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'goods_receipt_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'product_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'received_quantity' => ['type' => 'DECIMAL', 'constraint' => '14,3'],
                'unit_cost_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'line_total_rupiah' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'goods_receipt_id']],
            [
                $this->foreignKey('fk_receipt_items_receipt', ['tenant_id', 'store_id', 'goods_receipt_id'], 'goods_receipts', ['tenant_id', 'store_id', 'id'], 'CASCADE'),
                $this->foreignKey('fk_receipt_items_product', ['tenant_id', 'product_id'], 'products', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'receivables',
            $this->idField() + $this->tenantIdField() + [
                'store_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'transaction_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'customer_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'amount_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'due_on'         => ['type' => 'DATE', 'null' => true],
                'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'store_id', 'id'], ['tenant_id', 'store_id', 'transaction_id']],
            [['tenant_id', 'store_id', 'status', 'due_on']],
            [
                $this->foreignKey('fk_receivables_transaction', ['tenant_id', 'store_id', 'transaction_id'], 'transactions', ['tenant_id', 'store_id', 'id']),
                $this->foreignKey('fk_receivables_customer', ['tenant_id', 'customer_id'], 'customers', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'receivable_payments',
            $this->idField() + $this->tenantIdField() + [
                'store_id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'receivable_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'received_by_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'method'                 => ['type' => 'VARCHAR', 'constraint' => 30],
                'amount_rupiah'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'reference'              => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_at'             => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'receivable_id']],
            [
                $this->foreignKey('fk_receivable_payments_debt', ['tenant_id', 'store_id', 'receivable_id'], 'receivables', ['tenant_id', 'store_id', 'id'], 'CASCADE'),
                $this->foreignKey('fk_receivable_payments_member', ['tenant_id', 'received_by_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'payables',
            $this->idField() + $this->tenantIdField() + [
                'store_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'goods_receipt_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'supplier_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'supplier_invoice_no' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'amount_rupiah'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'due_on'              => ['type' => 'DATE', 'null' => true],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            ] + $this->timestamps(),
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'store_id', 'id']],
            [['tenant_id', 'store_id', 'status', 'due_on'], ['tenant_id', 'store_id', 'supplier_invoice_no']],
            [
                $this->foreignKey('fk_payables_receipt', ['tenant_id', 'store_id', 'goods_receipt_id'], 'goods_receipts', ['tenant_id', 'store_id', 'id']),
                $this->foreignKey('fk_payables_supplier', ['tenant_id', 'supplier_id'], 'suppliers', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'payable_payments',
            $this->idField() + $this->tenantIdField() + [
                'store_id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'payable_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'paid_by_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'method'                => ['type' => 'VARCHAR', 'constraint' => 30],
                'amount_rupiah'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'reference'             => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_at'            => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'payable_id']],
            [
                $this->foreignKey('fk_payable_payments_debt', ['tenant_id', 'store_id', 'payable_id'], 'payables', ['tenant_id', 'store_id', 'id'], 'CASCADE'),
                $this->foreignKey('fk_payable_payments_member', ['tenant_id', 'paid_by_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'cash_movements',
            $this->idField() + $this->tenantIdField() + [
                'store_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'cash_shift_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'actor_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'direction'      => ['type' => 'VARCHAR', 'constraint' => 10],
                'category'       => ['type' => 'VARCHAR', 'constraint' => 40],
                'method'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'amount_rupiah'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'reference_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'reference_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'created_at']],
            [
                $this->foreignKey('fk_cash_movements_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id']),
                $this->foreignKey('fk_cash_movements_shift', ['tenant_id', 'store_id', 'cash_shift_id'], 'cash_shifts', ['tenant_id', 'store_id', 'id']),
                $this->foreignKey('fk_cash_movements_member', ['tenant_id', 'actor_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'audit_logs',
            $this->idField() + $this->tenantIdField() + [
                'store_id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'actor_membership_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'action'               => ['type' => 'VARCHAR', 'constraint' => 80],
                'entity_type'          => ['type' => 'VARCHAR', 'constraint' => 80],
                'entity_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'details'              => ['type' => 'TEXT', 'null' => true],
                'created_at'           => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id']],
            [['tenant_id', 'store_id', 'created_at'], ['tenant_id', 'entity_type', 'entity_id']],
            [
                $this->tenantForeignKey('fk_audit_logs_tenant'),
                $this->foreignKey('fk_audit_logs_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id']),
                $this->foreignKey('fk_audit_logs_actor', ['tenant_id', 'actor_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );

        $this->createAppTable(
            'report_archives',
            $this->idField() + $this->tenantIdField() + [
                'store_id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'generated_by_membership_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
                'period'                => ['type' => 'VARCHAR', 'constraint' => 7],
                'snapshot'              => ['type' => 'LONGTEXT'],
                'created_at'            => ['type' => 'DATETIME', 'null' => true],
            ],
            ['id'],
            [['tenant_id', 'id'], ['tenant_id', 'store_id', 'period']],
            [],
            [
                $this->foreignKey('fk_report_archives_store', ['tenant_id', 'store_id'], 'stores', ['tenant_id', 'id']),
                $this->foreignKey('fk_report_archives_member', ['tenant_id', 'generated_by_membership_id'], 'memberships', ['tenant_id', 'id']),
            ],
        );
    }

    public function down()
    {
        $this->forge->dropTable('report_archives', true);
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('cash_movements', true);
        $this->forge->dropTable('payable_payments', true);
        $this->forge->dropTable('payables', true);
        $this->forge->dropTable('receivable_payments', true);
        $this->forge->dropTable('receivables', true);
        $this->forge->dropTable('goods_receipt_items', true);
        $this->forge->dropTable('goods_receipts', true);
        $this->forge->dropTable('purchase_order_items', true);
        $this->forge->dropTable('purchase_orders', true);
    }
}