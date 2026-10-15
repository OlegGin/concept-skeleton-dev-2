<?php declare(strict_types=1);

namespace Concept\Components\SystemLog;

use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\PathManager\PathManager;

class SystemLogComponent implements ComponentInterface
{
    private const string NAME = 'SystemLog';
    private const string VERSION = '1.0.0';
    private const string DESCRIPTION = 'System audit log with admin list view.';
    private const string VIEW_NAMESPACE = 'system-log';

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
        return [];
    }

    public function migrationPaths(): array
    {
        return [
            $this->componentDir . '/Database/Migrations',
        ];
    }

    public function commands(): array
    {
        return [];
    }

    public function viewExtensions(): array
    {
        return [];
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
