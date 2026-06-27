<?php declare(strict_types=1);

namespace Concept\Extensions\DataMasker;

use Concept\Extensions\DataMasker\Contracts\DataMaskerInterface;
use Concept\Extensions\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Container\ContainerInterface;

final class DataMaskerServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'data-masker';

    /**
     * @param array<string, string> $patterns
     * @param list<string> $keyPatterns
     * @param list<class-string<DataMaskerRuleInterface>> $rules
     */
    public function __construct(
        private readonly array $patterns = [],
        private readonly array $keyPatterns = [],
        private readonly array $rules = [],
    ) {}

    public function provides(string $id): bool
    {
        return $id === DataMaskerInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(DataMaskerInterface::class, function() use ($container): DataMasker {
            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: DataMaskerInterface::class,
            ));

            $masker = new DataMasker();

            if ($this->patterns !== [] || $this->keyPatterns !== []) {
                $masker->addRule(new RegexDataMaskerRule($this->patterns, $this->keyPatterns));
            }

            foreach ($this->rules as $ruleClass) {
                $masker->addRule($this->resolveRule($container, $ruleClass));
            }

            return $masker;
        })->setShared(true);
    }

    /**
     * @param class-string<DataMaskerRuleInterface> $ruleClass
     */
    private function resolveRule(ContainerInterface $container, string $ruleClass): DataMaskerRuleInterface
    {
        $rule = $container->get($ruleClass);

        if (!$rule instanceof DataMaskerRuleInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Data masker rule "%s" must implement %s.',
                $ruleClass,
                DataMaskerRuleInterface::class,
            ));
        }

        return $rule;
    }
}
