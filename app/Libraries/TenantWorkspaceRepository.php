<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use Throwable;

trait TenantFinanceOperations
{
    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function createSupplier(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'supplier.manage');
        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            throw new DomainException('Nama pemasok wajib diisi.');
        }
        if ($this->db->table('suppliers')->where('tenant_id', $context['tenant_id'])->where('name', $name)->countAllResults() > 0) {
            throw new DomainException('Pemasok sudah terdaftar.');
        }

        $this->db->transBegin();
        try {
            $now = $this->now();
            $this->db->table('suppliers')->insert([
                'tenant_id'     => $context['tenant_id'],
                'name'          => $name,
                'contact_name'  => trim((string) ($input['contact_name'] ?? '')) ?: null,
                'phone'         => trim((string) ($input['phone'] ?? '')) ?: null,
                'payment_terms' => trim((string) ($input['payment_terms'] ?? 'Tunai')),
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $supplierId = (int) $this->db->insertID();
            $this->recordAudit($context, 'supplier.created', 'suppliers', $supplierId, ['name' => $name]);
            $this->completeTransaction();

            return [
                'id'        => (string) $supplierId,
                'name'      => $name,
                'contact'   => (string) ($input['phone'] ?? ''),
                'lastOrder' => date('Y-m-d'),
                'total'     => 0,
                'status'    => (string) ($input['payment_terms'] ?? 'Tunai'),
            ];
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function createExpense(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'cash.manage');
        $label = trim((string) ($input['label'] ?? ''));
        $category = trim((string) ($input['category'] ?? 'Lainnya'));
        $amount = $this->money($input['amount'] ?? 0, 'Jumlah pengeluaran');
        $method = trim((string) ($input['method'] ?? 'Tunai'));

        if ($label === '' || $amount < 1 || ! in_array($method, ['Tunai', 'Transfer', 'QRIS'], true)) {
            throw new DomainException('Data pengeluaran tidak valid.');
        }

        $this->db->transBegin();
        try {
            $this->lockStore($context);
            $now = $this->now();
            $this->db->table('cash_movements')->insert([
                'tenant_id'           => $context['tenant_id'],
                'store_id'            => $context['store_id'],
                'actor_membership_id' => $context['membership_id'],
                'direction'           => 'out',
                'category'            => $category,
                'method'              => $method,
                'amount_rupiah'       => $amount,
                'reference_type'      => 'expense',
                'created_at'          => $now,
            ]);
            $movementId = (int) $this->db->insertID();
            $this->recordAudit($context, 'cash.expense_created', 'cash_movements', $movementId, ['label' => $label, 'amount_rupiah' => $amount]);
            $this->completeTransaction();

            return ['id' => (string) $movementId, 'date' => substr($now, 0, 10), 'label' => $label, 'category' => $category, 'amount' => $amount, 'method' => $method];
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function createDebt(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'finance.manage');
        $type = (string) ($input['type'] ?? '');
        $party = trim((string) ($input['party'] ?? ''));
        $amount = $this->money($input['amount'] ?? 0, 'Nilai tagihan');
        $due = (string) ($input['due'] ?? '');

        if (! in_array($type, ['receivable', 'payable'], true) || $party === '' || $amount < 1 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
            throw new DomainException('Data utang/piutang tidak valid.');
        }

        $this->db->transBegin();
        try {
            $now = $this->now();
            if ($type === 'receivable') {
                $customerId = $this->customerId($context, $party, true);
                if (! empty($input['note'])) {
                    $this->db->table('customers')->where('tenant_id', $context['tenant_id'])->where('id', $customerId)->update(['phone' => trim((string) $input['note'])]);
                }
                $this->db->table('receivables')->insert([
                    'tenant_id'      => $context['tenant_id'],
                    'store_id'       => $context['store_id'],
                    'customer_id'    => $customerId,
                    'amount_rupiah'  => $amount,
                    'due_on'         => $due,
                    'status'         => 'open',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
                $id = (int) $this->db->insertID();
                $this->recordAudit($context, 'receivable.created', 'receivables', $id, ['amount_rupiah' => $amount]);
                $debt = ['id' => (string) $id, 'customer' => $party, 'phone' => (string) ($input['note'] ?? ''), 'original' => $amount, 'paid' => 0, 'due' => $due, 'note' => (string) ($input['note'] ?? '')];
            } else {
                $supplier = $this->db->table('suppliers')->where('tenant_id', $context['tenant_id'])->where('name', $party)->get()->getRowArray();
                if ($supplier === null) {
                    $this->db->table('suppliers')->insert(['tenant_id' => $context['tenant_id'], 'name' => $party, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
                    $supplierId = (int) $this->db->insertID();
                } else {
                    $supplierId = (int) $supplier['id'];
                }
                $note = trim((string) ($input['note'] ?? ''));
                $this->db->table('payables')->insert([
                    'tenant_id'            => $context['tenant_id'],
                    'store_id'             => $context['store_id'],
                    'supplier_id'          => $supplierId,
                    'supplier_invoice_no'  => $note ?: null,
                    'amount_rupiah'        => $amount,
                    'due_on'               => $due,
                    'status'               => 'open',
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);
                $id = (int) $this->db->insertID();
                $this->recordAudit($context, 'payable.created', 'payables', $id, ['amount_rupiah' => $amount]);
                $debt = ['id' => (string) $id, 'supplier' => $party, 'original' => $amount, 'paid' => 0, 'due' => $due, 'note' => $note];
            }

            $this->completeTransaction();

            return $debt;
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function payDebt(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'finance.manage');
        $type = (string) ($input['type'] ?? '');
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $amount = $this->money($input['amount'] ?? 0, 'Jumlah pembayaran');
        $method = (string) ($input['method'] ?? '');

        if (! in_array($type, ['receivable', 'payable'], true) || $id === false || $amount < 1 || ! in_array($method, ['Tunai', 'Transfer', 'QRIS'], true)) {
            throw new DomainException('Data pembayaran tidak valid.');
        }

        $this->db->transBegin();
        try {
            $this->lockStore($context);
            $table = $type === 'receivable' ? 'receivables' : 'payables';
            $paymentTable = $type === 'receivable' ? 'receivable_payments' : 'payable_payments';
            $parentKey = $type === 'receivable' ? 'receivable_id' : 'payable_id';
            $debt = $this->db->table($table)->where('tenant_id', $context['tenant_id'])->where('store_id', $context['store_id'])->where('id', $id)->get()->getRowArray();
            if ($debt === null) {
                throw new DomainException('Tagihan tidak ditemukan.');
            }

            $paymentQuery = $this->db->table($paymentTable)->selectSum('amount_rupiah', 'total')->where('tenant_id', $context['tenant_id'])->where('store_id', $context['store_id'])->where($parentKey, $id)->get()->getRow();
            $paid = (int) ($paymentQuery->total ?? 0);
            $remaining = (int) $debt['amount_rupiah'] - $paid;
            if ($amount > $remaining) {
                throw new DomainException('Pembayaran melebihi sisa saldo.');
            }

            $now = $this->now();
            $membershipColumn = $type === 'receivable' ? 'received_by_membership_id' : 'paid_by_membership_id';
            $this->db->table($paymentTable)->insert([
                'tenant_id'       => $context['tenant_id'],
                'store_id'        => $context['store_id'],
                $parentKey         => (int) $id,
                $membershipColumn => $context['membership_id'],
                'method'          => $method,
                'amount_rupiah'   => $amount,
                'created_at'      => $now,
            ]);
            $remaining -= $amount;
            $this->db->table($table)->where('tenant_id', $context['tenant_id'])->where('store_id', $context['store_id'])->where('id', $id)->update([
                'status'     => $remaining === 0 ? 'paid' : 'open',
                'updated_at' => $now,
            ]);
            $this->db->table('cash_movements')->insert([
                'tenant_id'           => $context['tenant_id'],
                'store_id'            => $context['store_id'],
                'actor_membership_id' => $context['membership_id'],
                'direction'           => $type === 'receivable' ? 'in' : 'out',
                'category'            => $type === 'receivable' ? 'Pembayaran piutang' : 'Pembayaran utang',
                'method'              => $method,
                'amount_rupiah'       => $amount,
                'reference_type'      => $type,
                'reference_id'        => (int) $id,
                'created_at'          => $now,
            ]);
            $this->recordAudit($context, $type . '.payment_recorded', $table, (int) $id, ['amount_rupiah' => $amount]);
            $this->completeTransaction();

            return ['id' => (string) $id, 'paid' => $paid + $amount, 'remaining' => $remaining];
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function archiveReport(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'report.archive');
        $period = (string) ($input['period'] ?? '');
        $snapshot = $input['snapshot'] ?? null;
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) || ! is_array($snapshot)) {
            throw new DomainException('Periode atau ringkasan arsip tidak valid.');
        }
        $this->db->transBegin();
        try {
            $this->lockStore($context);
            if ($this->db->table('report_archives')->where('tenant_id', $context['tenant_id'])->where('store_id', $context['store_id'])->where('period', $period)->countAllResults() > 0) {
                throw new DomainException('Periode ini sudah diarsipkan.');
            }

            $now = $this->now();
            $this->db->table('report_archives')->insert([
                'tenant_id'                 => $context['tenant_id'],
                'store_id'                  => $context['store_id'],
                'generated_by_membership_id' => $context['membership_id'],
                'period'                    => $period,
                'snapshot'                  => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'created_at'                => $now,
            ]);
            $archiveId = (int) $this->db->insertID();
            $this->recordAudit($context, 'report.archived', 'report_archives', $archiveId, ['period' => $period]);
            $this->completeTransaction();

            return array_merge($snapshot, ['id' => (string) $archiveId, 'period' => $period, 'archivedAt' => substr($now, 0, 10)]);
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

}

class TenantWorkspaceRepository
{
    use TenantFinanceOperations;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** @param array<string, mixed> $actor
     *  @return array<string, array<int, array<string, mixed>>>
     */
    public function workspace(array $actor): array
    {
        $context = $this->context($actor);
        if (! $this->hasPermission($context, 'stock.view') && ! $this->hasPermission($context, 'pos.sell')) {
            throw new DomainException('Akses workspace ditolak.');
        }

        $canViewFinance = $this->hasPermission($context, 'finance.view');
        $canManageUsers = $this->hasPermission($context, 'user.manage');

        return [
            'products'     => $this->products($context),
            'transactions' => $this->transactions($context),
            'receivables'  => $canViewFinance ? $this->debts($context, 'receivables') : [],
            'payables'     => $canViewFinance ? $this->debts($context, 'payables') : [],
            'suppliers'    => $canViewFinance ? $this->suppliers($context) : [],
            'expenses'     => $canViewFinance ? $this->expenses($context) : [],
            'employees'    => $canManageUsers ? $this->employees($context) : [],
            'activity'     => $canViewFinance || $canManageUsers ? $this->activity($context) : [],
            'archives'     => $canViewFinance ? $this->archives($context) : [],
        ];
    }

    /** @param array<string, mixed> $context @return list<array<string, mixed>> */
    private function transactions(array $context): array
    {
        $rows = $this->db->table('transactions t')
            ->select('t.id, t.receipt_no, t.total_rupiah, t.created_at, c.name AS customer_name, u.name AS cashier_name')
            ->join('memberships m', 'm.tenant_id = t.tenant_id AND m.id = t.cashier_membership_id')
            ->join('users u', 'u.id = m.user_id')
            ->join('customers c', 'c.tenant_id = t.tenant_id AND c.id = t.customer_id', 'left')
            ->where('t.tenant_id', $context['tenant_id'])
            ->where('t.store_id', $context['store_id'])
            ->orderBy('t.id', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [];
        }

        $transactionIds = array_column($rows, 'id');
        $itemTotals = [];
        foreach ($this->db->table('transaction_items')
            ->select('transaction_id, quantity, unit_cost_rupiah')
            ->where('tenant_id', $context['tenant_id'])
            ->where('store_id', $context['store_id'])
            ->whereIn('transaction_id', $transactionIds)
            ->get()->getResultArray() as $item) {
            $id = (int) $item['transaction_id'];
            $itemTotals[$id]['quantity'] = ($itemTotals[$id]['quantity'] ?? 0) + (float) $item['quantity'];
            $itemTotals[$id]['cost'] = ($itemTotals[$id]['cost'] ?? 0) + (int) round((float) $item['quantity'] * (int) $item['unit_cost_rupiah']);
        }

        $methods = [];
        foreach ($this->db->table('transaction_payments')
            ->select('transaction_id, method')
            ->where('tenant_id', $context['tenant_id'])
            ->where('store_id', $context['store_id'])
            ->whereIn('transaction_id', $transactionIds)
            ->get()->getResultArray() as $payment) {
            $methods[(int) $payment['transaction_id']][] = $payment['method'];
        }

        $bonTransactions = array_fill_keys(array_map('intval', array_column(
            $this->db->table('receivables')->select('transaction_id')->where('tenant_id', $context['tenant_id'])->where('store_id', $context['store_id'])->whereIn('transaction_id', $transactionIds)->get()->getResultArray(),
            'transaction_id',
        )), true);

        return array_map(static function (array $row) use ($itemTotals, $methods, $bonTransactions): array {
            $id = (int) $row['id'];
            $payment = $methods[$id] ?? (isset($bonTransactions[$id]) ? ['Bon'] : ['Tunai']);

            return [
                'id'       => $row['receipt_no'],
                'date'     => substr((string) $row['created_at'], 0, 10),
                'customer' => $row['customer_name'] ?: 'Pelanggan umum',
                'payment'  => count(array_unique($payment)) > 1 ? 'Campuran' : $payment[0],
                'total'    => (int) $row['total_rupiah'],
                'cost'     => (int) ($itemTotals[$id]['cost'] ?? 0),
                'status'   => isset($bonTransactions[$id]) ? 'Belum lunas' : 'Lunas',
                'items'    => $itemTotals[$id]['quantity'] ?? 0,
                'cashier'  => $row['cashier_name'],
            ];
        }, $rows);
    }

    /** @param array<string, mixed> $context @return list<array<string, mixed>> */
    private function debts(array $context, string $table): array
    {
        $isReceivable = $table === 'receivables';
        $paymentTable = $isReceivable ? 'receivable_payments' : 'payable_payments';
        $parentId = $isReceivable ? 'receivable_id' : 'payable_id';
        $partyTable = $isReceivable ? 'customers' : 'suppliers';
        $partyId = $isReceivable ? 'customer_id' : 'supplier_id';
        $partyName = $isReceivable ? 'customer' : 'supplier';
        $query = $this->db->table($table . ' d')
            ->select('d.id, d.amount_rupiah, d.due_on, d.status, ' . ($isReceivable ? 'd.transaction_id' : 'd.supplier_invoice_no') . ' AS note, p.name AS party_name' . ($isReceivable ? ', p.phone AS phone' : ''))
            ->join($partyTable . ' p', 'p.tenant_id = d.tenant_id AND p.id = d.' . $partyId, 'left')
            ->where('d.tenant_id', $context['tenant_id'])
            ->where('d.store_id', $context['store_id'])
            ->orderBy('d.due_on', 'ASC');
        $rows = $query->get()->getResultArray();

        if ($rows === []) {
            return [];
        }

        $ids = array_column($rows, 'id');
        $paid = [];
        foreach ($this->db->table($paymentTable)
            ->select($parentId . ', amount_rupiah')
            ->where('tenant_id', $context['tenant_id'])
            ->where('store_id', $context['store_id'])
            ->whereIn($parentId, $ids)
            ->get()->getResultArray() as $payment) {
            $key = (int) $payment[$parentId];
            $paid[$key] = ($paid[$key] ?? 0) + (int) $payment['amount_rupiah'];
        }

        return array_map(static function (array $row) use ($paid, $partyName, $isReceivable): array {
            $id = (int) $row['id'];
            return [
                'id'       => (string) $id,
                $partyName => $row['party_name'] ?? 'Pelanggan umum',
                'phone'    => $row['phone'] ?? '',
                'original' => (int) $row['amount_rupiah'],
                'paid'     => (int) ($paid[$id] ?? 0),
                'due'      => $row['due_on'] ?? date('Y-m-d'),
                'note'     => (string) ($row['note'] ?? ''),
            ];
        }, $rows);
    }

    /** @param array<string, mixed> $context @return list<array<string, mixed>> */
    private function suppliers(array $context): array
    {
        $rows = $this->db->table('suppliers')
            ->where('tenant_id', $context['tenant_id'])
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn (array $row): array => [
            'id'        => (string) $row['id'],
            'name'      => $row['name'],
            'contact'   => $row['phone'] ?? '',
            'lastOrder' => date('Y-m-d'),
            'total'     => 0,
            'status'    => $row['payment_terms'] ?: 'Tunai',
        ], $rows);
    }

    /** @param array<string, mixed> $context @return list<array<string, mixed>> */
    private function expenses(array $context): array
    {
        $rows = $this->db->table('cash_movements')
            ->where('tenant_id', $context['tenant_id'])
            ->where('store_id', $context['store_id'])
            ->where('direction', 'out')
            ->orderBy('id', 'DESC')
            ->limit(200)
            ->get()->getResultArray();

        return array_map(static fn (array $row): array => [
            'id'       => (string) $row['id'],
            'date'     => substr((string) $row['created_at'], 0, 10),
            'label'    => $row['category'],
            'category' => $row['category'],
            'amount'   => (int) $row['amount_rupiah'],
            'method'   => 'Kas',
        ], $rows);
    }

    /** @param array<string, mixed> $context @return list<array<string, mixed>> */
    private function employees(array $context): array
    {
        $rows = $this->db->table('memberships m')
            ->select('m.id, m.status, u.name, r.name AS role_name')
            ->join('users u', 'u.id = m.user_id')
            ->join('roles r', 'r.tenant_id = m.tenant_id AND r.id = m.tenant_role_id AND r.scope = m.tenant_role_scope', 'left')
            ->where('m.tenant_id', $context['tenant_id'])
            ->orderBy('u.name', 'ASC')
            ->get()->getResultArray();

        return array_map(static function (array $row): array {
            $name = (string) $row['name'];
            $initials = implode('', array_map(static fn (string $part): string => mb_substr($part, 0, 1), array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2)));
            return [
                'id'       => 'MEM-' . $row['id'],
                'name'     => $name,
                'role'     => $row['role_name'] ?: 'Anggota toko',
                'initials' => strtoupper($initials),
                'status'   => $row['status'] === 'active' ? 'Aktif' : 'Nonaktif',
            ];
        }, $rows);
    }

    /** @param array<string, mixed> $context @return list<array<string, mixed>> */
    private function activity(array $context): array
    {
        $rows = $this->db->table('audit_logs a')
            ->select('a.action, a.created_at, a.entity_type, u.name AS user_name')
            ->join('memberships m', 'm.tenant_id = a.tenant_id AND m.id = a.actor_membership_id', 'left')
            ->join('users u', 'u.id = m.user_id', 'left')
            ->where('a.tenant_id', $context['tenant_id'])
            ->groupStart()
                ->where('a.store_id', $context['store_id'])
                ->orWhere('a.store_id', null)
            ->groupEnd()
            ->orderBy('a.id', 'DESC')
            ->limit(100)
            ->get()->getResultArray();

        return array_map(static function (array $row): array {
            $type = str_contains($row['action'], 'transaction') ? 'sale' : (str_contains($row['action'], 'finance') ? 'finance' : 'stock');
            return [
                'action' => str_replace('.', ' ', $row['action']),
                'user'   => $row['user_name'] ?? 'Sistem',
                'time'   => date('H.i', strtotime((string) $row['created_at'])),
                'type'   => $type,
            ];
        }, $rows);
    }

    /** @param array<string, mixed> $context @return list<array<string, mixed>> */
    private function archives(array $context): array
    {
        $rows = $this->db->table('report_archives')
            ->where('tenant_id', $context['tenant_id'])
            ->where('store_id', $context['store_id'])
            ->orderBy('period', 'DESC')
            ->get()->getResultArray();

        return array_map(static function (array $row): array {
            $snapshot = json_decode($row['snapshot'], true) ?: [];
            $label = date('F Y', strtotime($row['period'] . '-01'));
            return array_merge($snapshot, [
                'period'     => $row['period'],
                'label'      => $snapshot['label'] ?? $label,
                'archivedAt' => substr((string) $row['created_at'], 0, 10),
            ]);
        }, $rows);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function product(array $context, int $productId): array
    {
        $row = $this->db->table('products p')
            ->select('p.id, p.sku, p.name, p.unit, p.minimum_stock, p.cost_rupiah, p.retail_rupiah, p.wholesale_rupiah, c.name AS category, si.quantity AS stock')
            ->join($this->db->prefixTable('store_inventory') . ' si', 'si.tenant_id = p.tenant_id AND si.store_id = ' . $context['store_id'] . ' AND si.product_id = p.id', 'left', false)
            ->join($this->db->prefixTable('product_categories') . ' c', 'c.tenant_id = p.tenant_id AND c.id = p.category_id', 'left', false)
            ->where('p.tenant_id', $context['tenant_id'])
            ->where('p.id', $productId)
            ->get()->getRowArray();

        if ($row === null) {
            throw new DomainException('Barang tidak ditemukan.');
        }

        return $this->productDto($row);
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function createProduct(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'product.manage');

        $sku = strtoupper(trim((string) ($input['sku'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $categoryName = trim((string) ($input['category'] ?? ''));
        $unit = trim((string) ($input['unit'] ?? ''));
        $minimumStock = $this->number($input['minimum_stock'] ?? 0, 'Batas minimum stok');
        $openingStock = $this->number($input['opening_stock'] ?? 0, 'Stok awal');
        $cost = $this->money($input['cost_rupiah'] ?? 0, 'Harga modal');
        $retail = $this->money($input['retail_rupiah'] ?? 0, 'Harga eceran');
        $wholesale = $this->money($input['wholesale_rupiah'] ?? 0, 'Harga grosir');

        if ($sku === '' || $name === '' || $unit === '' || $categoryName === '') {
            throw new DomainException('Kode, nama, kategori, dan satuan barang wajib diisi.');
        }

        if ($this->db->table('products')->where('tenant_id', $context['tenant_id'])->where('sku', $sku)->countAllResults() > 0) {
            throw new DomainException('SKU tersebut sudah digunakan di toko ini.');
        }

        $this->db->transBegin();

        try {
            $this->lockStore($context);
            $categorySlug = $this->slug($categoryName);
            $category = $this->db->table('product_categories')
                ->where('tenant_id', $context['tenant_id'])
                ->where('slug', $categorySlug)
                ->get()
                ->getRowArray();

            if ($category === null) {
                $this->db->table('product_categories')->insert([
                    'tenant_id'  => $context['tenant_id'],
                    'name'       => $categoryName,
                    'slug'       => $categorySlug,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
                $categoryId = (int) $this->db->insertID();
            } else {
                $categoryId = (int) $category['id'];
            }

            $this->db->table('products')->insert([
                'tenant_id'       => $context['tenant_id'],
                'category_id'     => $categoryId,
                'sku'             => $sku,
                'name'            => $name,
                'unit'            => $unit,
                'minimum_stock'   => $minimumStock,
                'cost_rupiah'     => $cost,
                'retail_rupiah'   => $retail,
                'wholesale_rupiah' => $wholesale,
                'status'          => 'active',
                'created_at'      => $this->now(),
                'updated_at'      => $this->now(),
            ]);
            $productId = (int) $this->db->insertID();

            $this->db->table('store_inventory')->insert([
                'tenant_id'  => $context['tenant_id'],
                'store_id'   => $context['store_id'],
                'product_id' => $productId,
                'quantity'   => $openingStock,
                'updated_at' => $this->now(),
            ]);

            if ($openingStock > 0) {
                $this->recordMovement($context, $productId, 'opening_balance', $openingStock, $cost, 'product', $productId);
            }

            $this->recordAudit($context, 'product.created', 'products', $productId, ['sku' => $sku]);
            $this->completeTransaction();

            return $this->product($context, $productId);
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function adjustStock(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'stock.adjust');

        $productId = filter_var($input['product_id'] ?? null, FILTER_VALIDATE_INT);
        $delta = $this->number($input['quantity_delta'] ?? null, 'Jumlah perubahan stok');
        $movementType = (string) ($input['movement_type'] ?? 'stock_adjustment');
        $allowedTypes = ['purchase_receipt', 'damage', 'stock_adjustment'];

        if ($productId === false || $delta === 0.0 || ! in_array($movementType, $allowedTypes, true)) {
            throw new DomainException('Data pergerakan stok tidak valid.');
        }

        if ($movementType === 'purchase_receipt') {
            $this->assertPermission($context, 'purchase.receive');
            if ($delta < 0) {
                throw new DomainException('Penerimaan pembelian harus menambah stok.');
            }

            return $this->receivePurchase($context, $input, (int) $productId, $delta);
        }

        $this->db->transBegin();

        try {
            $this->lockStore($context);
            $product = $this->db->table('products')
                ->where('tenant_id', $context['tenant_id'])
                ->where('id', $productId)
                ->where('status', 'active')
                ->get()
                ->getRowArray();
            $inventory = $this->inventoryRow($context, (int) $productId);

            if ($product === null || $inventory === null) {
                throw new DomainException('Barang tidak ditemukan di cabang aktif.');
            }

            $newQuantity = (float) $inventory['quantity'] + $delta;
            if ($newQuantity < 0) {
                throw new DomainException('Stok tidak boleh menjadi negatif.');
            }

            $this->db->table('store_inventory')
                ->where('tenant_id', $context['tenant_id'])
                ->where('store_id', $context['store_id'])
                ->where('product_id', $productId)
                ->update(['quantity' => $newQuantity, 'updated_at' => $this->now()]);

            $unitCost = (int) ($input['unit_cost_rupiah'] ?? $product['cost_rupiah']);
            $this->recordMovement($context, (int) $productId, $movementType, $delta, $unitCost, 'manual', null);

            if ($delta < 0) {
                $this->db->table('cash_movements')->insert([
                    'tenant_id'          => $context['tenant_id'],
                    'store_id'           => $context['store_id'],
                    'actor_membership_id' => $context['membership_id'],
                    'direction'          => 'out',
                    'category'           => 'Kerugian barang',
                    'amount_rupiah'      => (int) round(abs($delta) * $unitCost),
                    'reference_type'     => 'stock_movement',
                    'reference_id'       => (int) $this->db->insertID(),
                    'created_at'         => $this->now(),
                ]);
            }

            $this->recordAudit($context, 'stock.adjusted', 'products', (int) $productId, [
                'quantity_delta' => $delta,
                'movement_type'  => $movementType,
                'note'           => trim((string) ($input['note'] ?? '')),
            ]);
            $this->completeTransaction();

            return $this->product($context, (int) $productId);
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    /** @param array<string, mixed> $actor @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    /** @param array<string, mixed> $context @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    private function receivePurchase(array $context, array $input, int $productId, float $quantity): array
    {
        $supplierName = trim((string) ($input['supplier_name'] ?? $input['supplier'] ?? ''));
        $paymentMethod = (string) ($input['payment_method'] ?? $input['payment'] ?? 'Tunai');
        $unitCost = $this->money($input['unit_cost_rupiah'] ?? $input['cost'] ?? 0, 'Harga modal');

        if ($supplierName === '' || $supplierName === 'Pemasok baru') {
            throw new DomainException('Pilih pemasok yang terdaftar sebelum menerima barang.');
        }
        if (! in_array($paymentMethod, ['Tunai', 'Utang pemasok'], true)) {
            throw new DomainException('Metode pembayaran pembelian tidak valid.');
        }

        $this->db->transBegin();

        try {
            $this->lockStore($context);
            $product = $this->db->table('products')
                ->where('tenant_id', $context['tenant_id'])
                ->where('id', $productId)
                ->where('status', 'active')
                ->get()
                ->getRowArray();
            $inventory = $this->inventoryRow($context, $productId);
            $supplier = $this->db->table('suppliers')
                ->where('tenant_id', $context['tenant_id'])
                ->where('name', $supplierName)
                ->get()
                ->getRowArray();

            if ($product === null || $inventory === null) {
                throw new DomainException('Barang tidak ditemukan di cabang aktif.');
            }
            if ($supplier === null) {
                throw new DomainException('Pemasok tidak ditemukan. Tambahkan pemasok terlebih dahulu.');
            }

            $now = $this->now();
            $total = (int) round($quantity * $unitCost);
            $orderNo = 'PO-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $receiptNo = 'GRN-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $this->db->table('purchase_orders')->insert([
                'tenant_id'                => $context['tenant_id'],
                'store_id'                 => $context['store_id'],
                'supplier_id'              => (int) $supplier['id'],
                'created_by_membership_id' => $context['membership_id'],
                'order_no'                 => $orderNo,
                'status'                   => 'received',
                'expected_on'              => date('Y-m-d'),
                'total_rupiah'             => $total,
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);
            $orderId = (int) $this->db->insertID();

            $this->db->table('purchase_order_items')->insert([
                'tenant_id'          => $context['tenant_id'],
                'store_id'           => $context['store_id'],
                'purchase_order_id'  => $orderId,
                'product_id'         => $productId,
                'ordered_quantity'   => $quantity,
                'unit_cost_rupiah'   => $unitCost,
                'line_total_rupiah'  => $total,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $this->db->table('goods_receipts')->insert([
                'tenant_id'                 => $context['tenant_id'],
                'store_id'                  => $context['store_id'],
                'purchase_order_id'         => $orderId,
                'received_by_membership_id' => $context['membership_id'],
                'receipt_no'                => $receiptNo,
                'status'                    => 'received',
                'received_at'               => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
            $receiptId = (int) $this->db->insertID();

            $this->db->table('goods_receipt_items')->insert([
                'tenant_id'         => $context['tenant_id'],
                'store_id'          => $context['store_id'],
                'goods_receipt_id'  => $receiptId,
                'product_id'        => $productId,
                'received_quantity' => $quantity,
                'unit_cost_rupiah'  => $unitCost,
                'line_total_rupiah' => $total,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $this->db->table('store_inventory')
                ->where('tenant_id', $context['tenant_id'])
                ->where('store_id', $context['store_id'])
                ->where('product_id', $productId)
                ->update(['quantity' => (float) $inventory['quantity'] + $quantity, 'updated_at' => $now]);
            $this->recordMovement($context, $productId, 'purchase_receipt', $quantity, $unitCost, 'goods_receipt', $receiptId);

            $isCredit = $paymentMethod === 'Utang pemasok';
            $this->db->table('payables')->insert([
                'tenant_id'      => $context['tenant_id'],
                'store_id'       => $context['store_id'],
                'goods_receipt_id' => $receiptId,
                'supplier_id'    => (int) $supplier['id'],
                'amount_rupiah'  => $total,
                'due_on'         => $isCredit ? date('Y-m-d', strtotime('+14 days')) : date('Y-m-d'),
                'status'         => $isCredit ? 'open' : 'paid',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $payableId = (int) $this->db->insertID();

            if (! $isCredit) {
                $this->db->table('payable_payments')->insert([
                    'tenant_id'             => $context['tenant_id'],
                    'store_id'              => $context['store_id'],
                    'payable_id'            => $payableId,
                    'paid_by_membership_id' => $context['membership_id'],
                    'method'                => 'Tunai',
                    'amount_rupiah'         => $total,
                    'created_at'            => $now,
                ]);
                $this->db->table('cash_movements')->insert([
                    'tenant_id'           => $context['tenant_id'],
                    'store_id'            => $context['store_id'],
                    'actor_membership_id' => $context['membership_id'],
                    'direction'           => 'out',
                    'category'            => 'Pembelian stok',
                    'amount_rupiah'       => $total,
                    'reference_type'      => 'payable',
                    'reference_id'        => $payableId,
                    'created_at'          => $now,
                ]);
            }

            $this->recordAudit($context, 'purchase.received', 'goods_receipts', $receiptId, ['receipt_no' => $receiptNo]);
            $this->completeTransaction();

            return $this->product($context, $productId);
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    public function checkout(array $actor, array $input): array
    {
        $context = $this->context($actor);
        $this->assertPermission($context, 'pos.sell');

        $payment = (string) ($input['payment_method'] ?? '');
        $saleType = (string) ($input['sale_type'] ?? 'retail');
        $lines = $input['lines'] ?? null;

        if (! in_array($payment, ['Tunai', 'QRIS', 'Bon'], true) || ! in_array($saleType, ['retail', 'wholesale'], true) || ! is_array($lines) || $lines === []) {
            throw new DomainException('Data transaksi tidak valid.');
        }

        $quantities = [];
        foreach ($lines as $line) {
            $productId = filter_var($line['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = $this->number($line['quantity'] ?? null, 'Jumlah barang');
            if ($productId === false || $quantity <= 0) {
                throw new DomainException('Barang atau jumlah transaksi tidak valid.');
            }
            $quantities[(int) $productId] = ($quantities[(int) $productId] ?? 0) + $quantity;
        }

        $this->db->transBegin();

        try {
            $this->lockStore($context);
            $saleLines = [];
            $subtotal = 0;
            $costTotal = 0;

            foreach ($quantities as $productId => $quantity) {
                $product = $this->db->table('products')
                    ->where('tenant_id', $context['tenant_id'])
                    ->where('id', $productId)
                    ->where('status', 'active')
                    ->get()
                    ->getRowArray();
                $inventory = $this->inventoryRow($context, $productId);

                if ($product === null || $inventory === null || (float) $inventory['quantity'] < $quantity) {
                    throw new DomainException('Stok salah satu barang tidak mencukupi.');
                }

                $unitPrice = (int) $product[$saleType === 'retail' ? 'retail_rupiah' : 'wholesale_rupiah'];
                $unitCost = (int) $product['cost_rupiah'];
                $lineTotal = (int) round($unitPrice * $quantity);
                $subtotal += $lineTotal;
                $costTotal += (int) round($unitCost * $quantity);
                $saleLines[] = [
                    'product_id'       => $productId,
                    'quantity'         => $quantity,
                    'unit_price_rupiah' => $unitPrice,
                    'unit_cost_rupiah'  => $unitCost,
                    'line_total_rupiah' => $lineTotal,
                    'product_name'     => $product['name'],
                    'unit'             => $product['unit'],
                ];
            }

            $businessDate = date('Y-m-d');
            $sequence = $this->nextReceiptSequence($context, $businessDate);
            $receiptNo = 'TRX-' . date('ymd') . '-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $customerName = trim((string) ($input['customer_name'] ?? ''));
            $customerId = $this->customerId($context, $customerName, $payment === 'Bon');
            $now = $this->now();

            $this->db->table('transactions')->insert([
                'tenant_id'             => $context['tenant_id'],
                'store_id'              => $context['store_id'],
                'receipt_no'            => $receiptNo,
                'cashier_membership_id' => $context['membership_id'],
                'customer_id'           => $customerId,
                'status'                => 'completed',
                'subtotal_rupiah'       => $subtotal,
                'discount_rupiah'       => 0,
                'total_rupiah'          => $subtotal,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
            $transactionId = (int) $this->db->insertID();

            foreach ($saleLines as $line) {
                $this->db->table('transaction_items')->insert([
                    'tenant_id'          => $context['tenant_id'],
                    'store_id'           => $context['store_id'],
                    'transaction_id'     => $transactionId,
                    'product_id'         => $line['product_id'],
                    'quantity'           => $line['quantity'],
                    'unit_price_rupiah'  => $line['unit_price_rupiah'],
                    'unit_cost_rupiah'   => $line['unit_cost_rupiah'],
                    'discount_rupiah'    => 0,
                    'line_total_rupiah'  => $line['line_total_rupiah'],
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);

                $inventory = $this->inventoryRow($context, $line['product_id']);
                $newQuantity = (float) $inventory['quantity'] - $line['quantity'];
                $this->db->table('store_inventory')
                    ->where('tenant_id', $context['tenant_id'])
                    ->where('store_id', $context['store_id'])
                    ->where('product_id', $line['product_id'])
                    ->update(['quantity' => $newQuantity, 'updated_at' => $now]);
                $this->recordMovement($context, $line['product_id'], 'sale', -$line['quantity'], $line['unit_cost_rupiah'], 'transaction', $transactionId);
            }

            if ($payment === 'Bon') {
                $this->db->table('receivables')->insert([
                    'tenant_id'       => $context['tenant_id'],
                    'store_id'        => $context['store_id'],
                    'transaction_id'  => $transactionId,
                    'customer_id'     => $customerId,
                    'amount_rupiah'   => $subtotal,
                    'due_on'          => date('Y-m-d', strtotime('+14 days')),
                    'status'          => 'open',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            } else {
                $this->db->table('transaction_payments')->insert([
                    'tenant_id'      => $context['tenant_id'],
                    'store_id'       => $context['store_id'],
                    'transaction_id' => $transactionId,
                    'method'         => $payment,
                    'amount_rupiah'  => $subtotal,
                    'created_at'     => $now,
                ]);
                $this->db->table('cash_movements')->insert([
                    'tenant_id'           => $context['tenant_id'],
                    'store_id'            => $context['store_id'],
                    'actor_membership_id' => $context['membership_id'],
                    'direction'           => 'in',
                    'category'            => 'Penjualan',
                    'amount_rupiah'       => $subtotal,
                    'reference_type'      => 'transaction',
                    'reference_id'        => $transactionId,
                    'created_at'          => $now,
                ]);
            }

            $this->recordAudit($context, 'transaction.completed', 'transactions', $transactionId, ['receipt_no' => $receiptNo]);
            $this->completeTransaction();

            return [
                'id'       => $receiptNo,
                'date'     => $businessDate,
                'customer' => $customerName === '' ? 'Pelanggan umum' : $customerName,
                'payment'  => $payment,
                'total'    => $subtotal,
                'cost'     => $costTotal,
                'status'   => $payment === 'Bon' ? 'Belum lunas' : 'Lunas',
                'items'    => array_sum(array_map(static fn (array $line): float => $line['quantity'], $saleLines)),
                'cashier'  => $actor['name'],
                'lines'    => $saleLines,
            ];
        } catch (Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }
    }

    /** @param array<string, mixed> $context
     *  @return list<array<string, mixed>>
     */
    private function products(array $context): array
    {
        $rows = $this->db->table('products p')
            ->select('p.id, p.sku, p.name, p.unit, p.minimum_stock, p.cost_rupiah, p.retail_rupiah, p.wholesale_rupiah, c.name AS category, si.quantity AS stock')
            ->join($this->db->prefixTable('store_inventory') . ' si', 'si.tenant_id = p.tenant_id AND si.store_id = ' . $context['store_id'] . ' AND si.product_id = p.id', 'left', false)
            ->join($this->db->prefixTable('product_categories') . ' c', 'c.tenant_id = p.tenant_id AND c.id = p.category_id', 'left', false)
            ->where('p.tenant_id', $context['tenant_id'])
            ->where('p.status', 'active')
            ->orderBy('p.name', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->productDto($row), $rows);
    }

    /** @param array<string, mixed> $context @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function productDto(array $row): array
    {
        $category = $row['category'] ?: 'Lainnya';

        return [
            'id'        => (string) $row['id'],
            'sku'       => $row['sku'],
            'name'      => $row['name'],
            'category'  => $category,
            'unit'      => $row['unit'],
            'stock'     => (float) ($row['stock'] ?? 0),
            'min'       => (float) $row['minimum_stock'],
            'retail'    => (int) $row['retail_rupiah'],
            'wholesale' => (int) $row['wholesale_rupiah'],
            'cost'      => (int) $row['cost_rupiah'],
            'color'     => ['Kantong' => 'green', 'Kemasan' => 'blue', 'Kertas' => 'orange', 'Perlengkapan' => 'yellow'][$category] ?? 'green',
        ];
    }

    /** @param array<string, mixed> $context @return array<string, mixed>|null */
    private function inventoryRow(array $context, int $productId): ?array
    {
        $sql = $this->db->table('store_inventory')
            ->where('tenant_id', $context['tenant_id'])
            ->where('store_id', $context['store_id'])
            ->where('product_id', $productId)
            ->getCompiledSelect();

        if ($this->db->DBDriver === 'MySQLi') {
            $sql .= ' FOR UPDATE';
        }

        return $this->db->query($sql)->getRowArray();
    }

    /** @param array<string, mixed> $context */
    private function lockStore(array $context): void
    {
        $sql = $this->db->table('stores')
            ->select('id')
            ->where('tenant_id', $context['tenant_id'])
            ->where('id', $context['store_id'])
            ->getCompiledSelect();

        if ($this->db->DBDriver === 'MySQLi') {
            $sql .= ' FOR UPDATE';
        }

        if ($this->db->query($sql)->getRowArray() === null) {
            throw new DomainException('Cabang aktif tidak ditemukan.');
        }
    }

    /** @param array<string, mixed> $context */
    private function nextReceiptSequence(array $context, string $businessDate): int
    {
        $sequence = $this->db->table('receipt_sequences')->where([
            'tenant_id'    => $context['tenant_id'],
            'store_id'     => $context['store_id'],
            'business_date' => $businessDate,
        ])->get()->getRowArray();

        if ($sequence === null) {
            $this->db->table('receipt_sequences')->insert([
                'tenant_id'    => $context['tenant_id'],
                'store_id'     => $context['store_id'],
                'business_date' => $businessDate,
                'next_number'  => 2,
            ]);

            return 1;
        }

        $number = (int) $sequence['next_number'];
        $this->db->table('receipt_sequences')->where([
            'tenant_id'    => $context['tenant_id'],
            'store_id'     => $context['store_id'],
            'business_date' => $businessDate,
        ])->update(['next_number' => $number + 1]);

        return $number;
    }

    /** @param array<string, mixed> $context */
    private function customerId(array $context, string $name, bool $create): ?int
    {
        if ($name === '' || mb_strtolower($name) === mb_strtolower('Pelanggan umum')) {
            if (! $create) {
                return null;
            }
            $name = 'Pelanggan umum';
        }

        $customer = $this->db->table('customers')
            ->where('tenant_id', $context['tenant_id'])
            ->where('name', $name)
            ->get()
            ->getRowArray();

        if ($customer !== null) {
            return (int) $customer['id'];
        }
        if (! $create) {
            return null;
        }

        $this->db->table('customers')->insert([
            'tenant_id'  => $context['tenant_id'],
            'name'       => $name,
            'status'     => 'active',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return (int) $this->db->insertID();
    }

    /** @param array<string, mixed> $context */
    private function recordMovement(array $context, int $productId, string $type, float $delta, int $unitCost, string $sourceType, ?int $sourceId): void
    {
        $this->db->table('stock_movements')->insert([
            'tenant_id'            => $context['tenant_id'],
            'store_id'             => $context['store_id'],
            'product_id'           => $productId,
            'movement_type'        => $type,
            'quantity_delta'       => $delta,
            'unit_cost_rupiah'     => $unitCost,
            'source_type'          => $sourceType,
            'source_id'            => $sourceId,
            'actor_membership_id'  => $context['membership_id'],
            'created_at'           => $this->now(),
        ]);
    }

    /** @param array<string, mixed> $context @param array<string, mixed> $details */
    private function recordAudit(array $context, string $action, string $entityType, int $entityId, array $details): void
    {
        $this->db->table('audit_logs')->insert([
            'tenant_id'          => $context['tenant_id'],
            'store_id'           => $context['store_id'],
            'actor_membership_id' => $context['membership_id'],
            'action'             => $action,
            'entity_type'        => $entityType,
            'entity_id'          => $entityId,
            'details'            => json_encode($details, JSON_THROW_ON_ERROR),
            'created_at'         => $this->now(),
        ]);
    }

    /** @param array<string, mixed> $context */
    private function assertPermission(array $context, string $permission): void
    {
        if (! $this->hasPermission($context, $permission)) {
            throw new DomainException('Anda tidak memiliki izin untuk tindakan ini.');
        }
    }

    /** @param array<string, mixed> $context */
    private function hasPermission(array $context, string $permission): bool
    {
        $membership = $this->db->table('memberships')
            ->where('id', $context['membership_id'])
            ->where('tenant_id', $context['tenant_id'])
            ->where('user_id', $context['user_id'])
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if ($membership === null) {
            return false;
        }

        $roleIds = [];
        if ($membership['tenant_role_id'] !== null) {
            $roleIds[] = (int) $membership['tenant_role_id'];
        }

        $storeRoles = $this->db->table('store_memberships')
            ->select('role_id')
            ->where('tenant_id', $context['tenant_id'])
            ->where('membership_id', $context['membership_id'])
            ->where('store_id', $context['store_id'])
            ->where('status', 'active')
            ->get()
            ->getResultArray();
        foreach ($storeRoles as $storeRole) {
            $roleIds[] = (int) $storeRole['role_id'];
        }

        if ($roleIds === []) {
            return false;
        }

        return $this->db->table('role_permissions')
            ->where('tenant_id', $context['tenant_id'])
            ->whereIn('role_id', array_unique($roleIds))
            ->where('permission_code', $permission)
            ->countAllResults() > 0;
    }

    /** @param array<string, mixed> $actor @return array<string, int> */
    private function context(array $actor): array
    {
        $context = [];
        foreach (['user_id', 'membership_id', 'tenant_id', 'store_id'] as $key) {
            $value = filter_var($actor[$key] ?? null, FILTER_VALIDATE_INT);
            if ($value === false || $value < 1) {
                throw new DomainException('Sesi tenant tidak valid.');
            }
            $context[$key] = $value;
        }

        return $context;
    }

    private function number(mixed $value, string $label): float
    {
        if (! is_numeric($value)) {
            throw new DomainException($label . ' tidak valid.');
        }

        $number = (float) $value;
        if (! is_finite($number) || abs($number) > 1_000_000_000) {
            throw new DomainException($label . ' di luar batas.');
        }

        return $number;
    }

    private function money(mixed $value, string $label): int
    {
        if (! is_numeric($value) || (float) $value < 0 || (float) $value > 9_000_000_000_000_000) {
            throw new DomainException($label . ' tidak valid.');
        }

        return (int) round((float) $value);
    }

    private function slug(string $value): string
    {
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');

        return $slug === '' ? 'kategori-' . bin2hex(random_bytes(3)) : substr($slug, 0, 120);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function completeTransaction(): void
    {
        if (! $this->db->transStatus()) {
            throw new DomainException('Transaksi database gagal. Tidak ada perubahan yang disimpan.');
        }

        $this->db->transCommit();
    }
}