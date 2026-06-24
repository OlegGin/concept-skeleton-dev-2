<?php declare(strict_types=1);

namespace Concept\Extensions\Validation\Adapters;

use Concept\Extensions\Validation\Contracts\RuleInterface;
use Rakit\Validation\MissingRequiredParameterException;
use Rakit\Validation\Rule as LibraryRule;

final class RuleAdapter extends LibraryRule
{
    public function __construct(private readonly RuleInterface $customRule)
    {
        $this->setMessage($this->customRule->getMessage());
        $this->fillableParams = $this->customRule->getFillable();
    }

    /**
     * @throws MissingRequiredParameterException
     */
    public function check(mixed $value): bool
    {
        $this->requireParameters($this->customRule->getRequired());
        $this->customRule->setParameters($this->params);

        return $this->customRule->passes($value);
    }
}
