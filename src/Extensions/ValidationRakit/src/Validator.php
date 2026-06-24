<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit;

use Concept\Extensions\ValidationRakit\Adapters\RuleAdapter;
use Concept\Extensions\ValidationRakit\Adapters\ValidationAdapter;
use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidationInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Rakit\Validation\Validator as LibraryValidator;

final class Validator implements ValidatorInterface
{
    private const string ERR_RULE_MUST_IMPLEMENT_INTERFACE = 'Rule %s must implement %s.';

    private readonly LibraryValidator $libraryValidator;

    public function __construct(
        private readonly ContainerInterface $container,
        ?LibraryValidator $libraryValidator = null,
    ) {
        $this->libraryValidator = $libraryValidator ?? new LibraryValidator();
    }

    public function addRules(array $rules): void
    {
        foreach ($rules as $name => $class) {
            $customRule = $this->container->get($class);
            if (!$customRule instanceof RuleInterface) {
                throw new InvalidArgumentException(sprintf(
                    self::ERR_RULE_MUST_IMPLEMENT_INTERFACE,
                    $class,
                    RuleInterface::class,
                ));
            }

            $this->libraryValidator->addValidator($name, new RuleAdapter($customRule));
        }
    }

    public function make(array $data, array $rulesConfig): ValidationInterface
    {
        return new ValidationAdapter($this->libraryValidator->make($data, $rulesConfig));
    }
}
