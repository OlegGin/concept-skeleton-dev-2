<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit;

use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;

final class ValidationServiceProvider extends AbstractServiceProvider
{
    /**
     * @param array<string, class-string<RuleInterface>> $customRules
     */
    public function __construct(
        private readonly array $customRules = [],
        private readonly bool $logEnabled = false,
        private readonly string $logPath = '',
        private readonly int $logMaxFiles = 7,
    ) {}

    public function provides(string $id): bool
    {
        return in_array($id, [
            ValidatorInterface::class,
            ValidationLogger::class,
        ], true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ValidationLogger::class, function() use ($container): ValidationLogger {
            $monolog = new Monolog('validation');
            $monolog->pushHandler(new RotatingFileHandler(
                $this->logPath,
                $this->logMaxFiles,
                Level::Debug,
            ));

            /** @var DataMaskerInterface|null $masker */
            $masker = $container->has(DataMaskerInterface::class)
                ? $container->get(DataMaskerInterface::class)
                : null;

            return new ValidationLogger($this->logEnabled, $monolog, $masker);
        })->setShared(true);

        $container->add(ValidatorInterface::class, function() use ($container) {
            $validator = new Validator($container);
            $validator->addRules($this->customRules);

            return $validator;
        })->setShared(true);
    }
}
