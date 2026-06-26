<?php declare(strict_types=1);

namespace Concept\Components\DebugBar;

use Concept\Components\AuthAdmin\Commands\UserListCommand;
use Concept\Components\AuthAdmin\Database\Seeders\UserSeeder;
use Concept\Components\AuthAdmin\Extensions\TwigExtension;
use Concept\Components\DebugBar\Providers\DebugBarServiceProvider;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Services\Component\Contracts\ComponentInterface;
use Concept\Core\Services\Database\Contracts\SeederInterface;
use Symfony\Component\Console\Command\Command;

class DebugBarComponent implements ComponentInterface
{
    private const string NAME = 'DebugBar';
    private const string VERSION = '1.0.0';
    private const string DESCRIPTION = 'DebugBar component.';

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
        return [
            DebugBarServiceProvider::class,
        ];
    }

    public function seeders(): array
    {
        return [];
    }

    public function migrationPaths(): array
    {
        return [];
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
        return [];
    }

    public function viewContexts(): array
    {
        return [];
    }

    public function assets(): array
    {
        return [
            'vendor/php-debugbar/php-debugbar/resources/dist' =>
                $this->pathManager->getRelative(PathName::PUBLIC, 'components/debug-bar/dist'),
        ];
    }
}
