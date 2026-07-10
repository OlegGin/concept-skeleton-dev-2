<?php declare(strict_types=1);

namespace Concept\App\Providers\Layers;

use Concept\App\Foundation\ConfigKey;
use Concept\App\Foundation\PathName;
use Concept\App\Providers\Support\DataMaskerFactory;
use Concept\Core\Container\ContainerDependency;
use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\Config\Contracts\ConfigInterface;
use Concept\Extensions\FormRequest\FormRequestServiceProvider;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Concept\Extensions\ValidationRakit\ValidationLogger;
use Concept\Extensions\ValidationRakit\ValidationServiceProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class ValidationLayerProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $container = $this->getContainer();

        $pathManager = ContainerDependency::get($container, PathManager::class);
        $config = ContainerDependency::get($container, ConfigInterface::class);

        $container->addServiceProvider(new ValidationServiceProvider(
            customRules: $this->getValidatorRules($config),
            logEnabled: $config->getBool(ConfigKey::VALIDATOR_LOG_ENABLED),
            logFilePath: $pathManager->get(PathName::LOGS, $config->getString(ConfigKey::VALIDATOR_LOG_FILE)),
            logMaxFiles: $config->getInt(ConfigKey::VALIDATOR_LOG_MAX_FILES, 7),
            dataMaskerFactory: DataMaskerFactory::fromContainer($container),
        ));

        /** @var array<string> $globalExcept */
        $globalExcept = $config->getArray(ConfigKey::FORM_REQUEST_GLOBAL_EXCEPT);
        $container->addServiceProvider(new FormRequestServiceProvider(
            validatorFactory: fn(): ValidatorInterface => ContainerDependency::get($container, ValidatorInterface::class),
            globalExcept: $globalExcept,
            casterFactory: function() use ($container): ?CasterInterface {
                if (!$container->has(CasterInterface::class)) {
                    return null;
                }

                return ContainerDependency::get($container, CasterInterface::class);
            },
            validationLoggerFactory: function() use ($container): ?ValidationLogger {
                if (!$container->has(ValidationLogger::class)) {
                    return null;
                }

                return ContainerDependency::get($container, ValidationLogger::class);
            },
        ));
    }

    /**
     * @return array<string, class-string<RuleInterface>>
     */
    private function getValidatorRules(ConfigInterface $config): array
    {
        /** @var array<string, class-string<RuleInterface>> $rules */
        $rules = $config->getArray(ConfigKey::VALIDATOR_RULES);

        return $rules;
    }
}
