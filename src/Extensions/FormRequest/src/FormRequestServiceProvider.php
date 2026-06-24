<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest;

use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Factory\FormRequestFactory;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class FormRequestServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === FormRequestFactoryInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(FormRequestFactoryInterface::class, fn () => new FormRequestFactory($container))
            ->setShared(true);
    }
}
