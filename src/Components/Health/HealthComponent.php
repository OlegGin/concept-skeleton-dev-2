<?php declare(strict_types=1);

namespace Concept\Components\Health;

use Concept\Components\Health\Commands\AppHealthCommand;
use Concept\Components\Health\Providers\HealthServiceProvider;
use Concept\Extensions\Components\Contracts\ComponentInterface;
use Symfony\Component\Console\Command\Command;

final class HealthComponent implements ComponentInterface
{
    private const string NAME = 'Health';
    private const string VERSION = '1.0.0';
    private const string DESCRIPTION = 'CLI app:health smoke checks after boot.';

    /** @var list<class-string<Command>> */
    private const array COMMANDS = [
        AppHealthCommand::class,
    ];

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
        return null;
    }

    public function providers(): array
    {
        return [
            HealthServiceProvider::class,
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
        return self::COMMANDS;
    }

    public function viewExtensions(): array
    {
        return [];
    }

    public function viewPaths(): array
    {
        return [];
    }

    public function viewRouteNamespace(): array
    {
        return [];
    }

    public function assets(): array
    {
        return [];
    }
}
