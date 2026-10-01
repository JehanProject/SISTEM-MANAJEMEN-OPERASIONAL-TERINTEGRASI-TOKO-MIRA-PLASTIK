<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

abstract class TenantSchemaMigration extends Migration
{
    /**
     * @param list<list<string>> $uniqueKeys
     * @param list<list<string>> $keys
     * @param list<array{columns: list<string>, table: string, references: list<string>, onDelete?: string, onUpdate?: string, name: string}> $foreignKeys
     */
    protected function createAppTable(
        string $table,
        array $fields,
        array $primaryKey,
        array $uniqueKeys = [],
        array $keys = [],
        array $foreignKeys = [],
    ): void {
        $this->forge->addField($fields);
        $this->forge->addPrimaryKey($primaryKey);

        foreach ($uniqueKeys as $key) {
            $this->forge->addUniqueKey($key);
        }

        foreach ($keys as $key) {
            $this->forge->addKey($key);
        }

        foreach ($foreignKeys as $foreignKey) {
            $foreignKeyName = $this->db->DBDriver === 'MySQLi' ? $foreignKey['name'] : '';

            $this->forge->addForeignKey(
                $foreignKey['columns'],
                $foreignKey['table'],
                $foreignKey['references'],
                $foreignKey['onDelete'] ?? 'RESTRICT',
                $foreignKey['onUpdate'] ?? 'CASCADE',
                $foreignKeyName,
            );
        }

        $tableOptions = $this->db->DBDriver === 'MySQLi' ? ['ENGINE' => 'InnoDB'] : [];
        $this->forge->createTable($table, true, $tableOptions);
    }

    protected function idField(): array
    {
        return [
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
        ];
    }

    protected function tenantIdField(): array
    {
        return [
            'tenant_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
        ];
    }

    protected function timestamps(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    protected function tenantForeignKey(string $name, string $onDelete = 'CASCADE'): array
    {
        return $this->foreignKey($name, ['tenant_id'], 'tenants', ['id'], $onDelete);
    }

    protected function foreignKey(
        string $name,
        array $columns,
        string $table,
        array $references,
        string $onDelete = 'RESTRICT',
    ): array {
        return [
            'columns'    => $columns,
            'table'      => $table,
            'references' => $references,
            'onDelete'   => $onDelete,
            'name'       => $name,
        ];
    }
}