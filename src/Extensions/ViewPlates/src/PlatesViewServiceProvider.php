<?php declare(strict_types=1);

namespace Concept\Extensions\ViewPlates;

use Concept\Extensions\View\Contracts\ViewInterface;
use Concept\Extensions\View\Registry\ViewRegistry;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class PlatesViewServiceProvider extends AbstractServiceProvider
{
    public const string DEFAULT_EXTENSION = '.php';

    public function __construct(
        private readonly string $root,
        private readonly string $viewsPath,
        private readonly string $defaultExtension = self::DEFAULT_EXTENSION,
    ) {}

    public function provides(string $id): bool
    {
        return $id === ViewInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ViewInterface::class, function () use ($container): PlatesView {
            $engine = new Engine($this->viewsPath, ltrim($this->defaultExtension, '.'));

            /** @var ViewRegistry $viewRegistry */
            $viewRegistry = $container->get(ViewRegistry::class);
            $this->addExtensions($engine, $viewRegistry->extensions()->all());
            $this->addFolders($engine, $this->root, $viewRegistry->paths()->all());

            return new PlatesView($engine);
        })->setShared(true);
    }

    /**
     * @param array<int, class-string> $extensions
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function addExtensions(Engine $engine, array $extensions): void
    {
        foreach ($extensions as $extensionClass) {
            /** @var ExtensionInterface $extension */
            $extension = $this->getContainer()->get($extensionClass);
            $engine->loadExtension($extension);
        }
    }

    /**
     * @param array<string, string> $namespaces
     */
    private function addFolders(Engine $engine, string $rootPath, array $namespaces): void
    {
        foreach ($namespaces as $namespace => $path) {
            $engine->addFolder(
                $namespace,
                rtrim($rootPath, '/') . '/' . ltrim($path, '/'),
                true,
            );
        }
    }
}
