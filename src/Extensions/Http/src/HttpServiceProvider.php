<?php declare(strict_types=1);

namespace Concept\Extensions\Http;

use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Contracts\UrlGeneratorInterface;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\Http\Response\ResponseFactory;
use Concept\Extensions\Http\Routing\RouteDescriptor;
use Concept\Extensions\Http\Routing\UrlGenerator;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Route\Router;

final class HttpServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            UrlGeneratorInterface::class,
            RouteDescriptor::class,
            RequestFormat::class,
            ResponseFactoryInterface::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(UrlGeneratorInterface::class, function () use ($container) {
            /** @var Router $router */
            $router = $container->get(Router::class);

            return new UrlGenerator($router);
        })->setShared(true);

        $container->add(RouteDescriptor::class, function () use ($container) {
            /** @var Router $router */
            $router = $container->get(Router::class);

            return new RouteDescriptor($router);
        })->setShared(true);

        $container->add(RequestFormat::class, fn() => new RequestFormat())->setShared(true);

        $container->add(ResponseFactoryInterface::class, function () use ($container) {
            /** @var UrlGeneratorInterface $urlGenerator */
            $urlGenerator = $container->get(UrlGeneratorInterface::class);

            return new ResponseFactory($urlGenerator);
        })->setShared(true);
    }
}
