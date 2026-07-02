<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin;

use Concept\Components\AuthAdmin\Commands\UserListCommand;
use Concept\Components\AuthAdmin\Database\Seeders\UserSeeder;
use Concept\Components\AuthAdmin\Extensions\TwigExtension;
use Concept\App\Foundation\PathName;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\DatabaseEloquent\Contracts\SeederInterface;
use Symfony\Component\Console\Command\Command;

class AuthAdminComponent implements ComponentInterface
{
    private const string NAME = 'AuthAdmin';
    private const string VERSION = '1.0.0';
    private const string DESCRIPTION = 'Admin authentication, dashboard and user management.';
    private const string VIEW_NAMESPACE = 'auth-admin';

    /** @var list<class-string<SeederInterface>> */
    private const array SEEDERS = [
        UserSeeder::class,
    ];

    /** @var list<class-string<Command>> */
    private const array COMMANDS = [
        UserListCommand::class,
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

    public function __construct(private readonly PathManager $pathManager) {}

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
        return [];
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
        return [
            $this->componentDir . '/Assets/admin-tokens.js' =>
                $this->pathManager->get(PathName::PUBLIC, 'components/auth-admin/js/admin-tokens.js'),
        ];
    }
}
