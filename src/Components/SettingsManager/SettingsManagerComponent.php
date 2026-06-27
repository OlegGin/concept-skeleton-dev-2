<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager;

use Concept\Components\SettingsManager\Database\Seeders\SettingsSeeder;
use Concept\Components\SettingsManager\Extensions\TwigExtension;
use Concept\Components\SettingsManager\Providers\SettingsManagerServiceProvider;
use Concept\App\Foundation\PathName;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\DatabaseEloquent\Contracts\SeederInterface;

class SettingsManagerComponent implements ComponentInterface
{
    private const string NAME = 'SettingsManager';
    private const string VERSION = '1.0.0';
    private const string DESCRIPTION = 'Centralized application settings storage and retrieval.';
    private const string VIEW_NAMESPACE = 'settings-manager';

    /** @var list<class-string<SeederInterface>> */
    private const array SEEDERS = [
        SettingsSeeder::class,
    ];

    /** @var array<string, string> */
    private const array VIEW_CONTEXTS = [
        '/admin' => 'dashboard',
    ];

    /** @var list<class-string> */
    private const array VIEW_EXTENSIONS = [
        TwigExtension::class,
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
        return [
            SettingsManagerServiceProvider::class,
        ];
    }

    public function seeders(): array
    {
        return self::SEEDERS;
    }

    public function migrationPaths(): array
    {
        return [
            $this->pathManager->toRelative($this->componentDir . '/Database/Migrations'),
        ];
    }

    public function commands(): array
    {
        return [];
    }

    public function viewExtensions(): array
    {
        return self::VIEW_EXTENSIONS;
    }

    public function viewPaths(): array
    {
        return [
            self::VIEW_NAMESPACE => $this->pathManager->toRelative($this->componentDir . '/Views'),
        ];
    }

    public function viewContexts(): array
    {
        return self::VIEW_CONTEXTS;
    }

    public function assets(): array
    {
        return [
            $this->pathManager->toRelative($this->componentDir . '/Assets/settings-form.js') =>
                $this->pathManager->getRelative(PathName::PUBLIC, 'components/settings-manager/js/settings-form.js'),
        ];
    }
}
