<?php declare(strict_types=1);

namespace Concept\Components\Acl;

use Concept\Components\Acl\Database\Seeders\AclRouteRulesSeeder;
use Concept\Components\Acl\Database\Seeders\AclSeeder;
use Concept\Components\Acl\Database\Seeders\AssignUserAclRolesSeeder;
use Concept\Components\Acl\Extensions\TwigExtension;
use Concept\Components\Acl\Providers\AclServiceProvider;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\DatabaseEloquent\Contracts\SeederInterface;
use Symfony\Component\Console\Command\Command;

class AclComponent implements ComponentInterface
{
    private const string NAME = 'Acl';
    private const string VERSION = '1.0.0';
    private const string DESCRIPTION = 'Laminas Permissions ACL integration.';
    private const string VIEW_NAMESPACE = 'acl';

    /** @var list<class-string<SeederInterface>> */
    private const array SEEDERS = [
        AclSeeder::class,
        AclRouteRulesSeeder::class,
        AssignUserAclRolesSeeder::class,
    ];

    /** @var list<class-string<Command>> */
    private const array COMMANDS = [
    ];

    /** @var list<class-string> */
    private const array VIEW_EXTENSIONS = [
        TwigExtension::class,
    ];

    /** @var array<string, string> route prefix => view namespace */
    private const array VIEW_ROUTE_NAMESPACE = [
        '/admin' => 'dashboard',
    ];

    protected string $componentDir = __DIR__;

    public function name(): string
    {
        return self::NAME;
    }

    public function version(): string
    {
        return self::VERSION;
    }

    public function description(): string
    {
        return self::DESCRIPTION;
    }

    public function routes(): ?string
    {
        return $this->componentDir . '/routes.php';
    }

    public function providers(): array
    {
        return [
            AclServiceProvider::class,
        ];
    }

    public function seeders(): array
    {
        return self::SEEDERS;
    }

    public function migrationPaths(): array
    {
        return [
            $this->componentDir . '/Database/Migrations',
        ];
    }

    public function commands(): array
    {
        return self::COMMANDS;
    }

    public function viewExtensions(): array
    {
        return self::VIEW_EXTENSIONS;
    }

    public function viewPaths(): array
    {
        return [
            self::VIEW_NAMESPACE => $this->componentDir . '/Views',
        ];
    }

    public function viewRouteNamespace(): array
    {
        return self::VIEW_ROUTE_NAMESPACE;
    }

    public function assets(): array
    {
        return [];
    }
}
