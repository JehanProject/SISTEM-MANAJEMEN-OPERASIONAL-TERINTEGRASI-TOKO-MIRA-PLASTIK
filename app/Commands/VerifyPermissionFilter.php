<?php

namespace App\Commands;

use App\Filters\PermissionFilter;
use App\Libraries\TenantAuthRepository;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class VerifyPermissionFilter extends BaseCommand
{
    protected $group = 'App';

    protected $name = 'verify:permission-filter';

    protected $description = 'Verify route-level tenant RBAC against the test database.';

    public function run(array $params)
    {
        service('migrations')->latest('tests');
        $actor = (new TenantAuthRepository(db_connect('tests')))->createOwner('Permission Test', 'permission@example.invalid', 'permission-password-strong');
        session()->set('auth_user', $actor);
        $filter = new PermissionFilter();
        $request = service('request');

        if (! $request instanceof RequestInterface || $filter->before($request, ['pos.sell']) !== null) {
            throw new RuntimeException('Owner permission was not allowed.');
        }

        $denied = $filter->before($request, ['permission.that.does.not.exist']);
        if (! $denied instanceof ResponseInterface || $denied->getStatusCode() !== 403) {
            throw new RuntimeException('Unknown permission was not denied.');
        }

        $actor['tenant_id']++;
        session()->set('auth_user', $actor);
        $crossTenant = $filter->before($request, ['pos.sell']);
        if (! $crossTenant instanceof ResponseInterface || $crossTenant->getStatusCode() !== 403) {
            throw new RuntimeException('Mismatched tenant context was not denied.');
        }

        CLI::write('Route RBAC allows assigned owner permissions and denies unknown/cross-tenant context.', 'green');
    }
}