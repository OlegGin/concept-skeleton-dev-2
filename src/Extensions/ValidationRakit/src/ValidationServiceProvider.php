<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit;

use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class ValidationServiceProvider extends AbstractServiceProvider
{
    /**
     * @param array<string, class-string<RuleInterface>> $customRules
     */
    public function __construct(
        private readonly array $customRules = [],
    ) {}

    public function provides(string $id): bool
    {
        return $id === ValidatorInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ValidatorInterface::class, function () use ($container) {
            $validator = new Validator($container);
            $validator->addRules($this->customRules);

            return $validator;
        })->setShared(true);
    }
}
