<?php declare(strict_types=1);

namespace Concept\Extensions\Json;

use Concept\Extensions\Json\Middleware\ForceJsonResponseMiddleware;
use Concept\Extensions\Json\Middleware\ParseJsonBodyMiddleware;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class JsonServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            ParseJsonBodyMiddleware::class,
            ForceJsonResponseMiddleware::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ParseJsonBodyMiddleware::class, fn (): ParseJsonBodyMiddleware => new ParseJsonBodyMiddleware())
            ->setShared(true);

        $container->add(ForceJsonResponseMiddleware::class, fn (): ForceJsonResponseMiddleware => new ForceJsonResponseMiddleware())
            ->setShared(true);
    }
}
