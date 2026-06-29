<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit\Adapters;

use Concept\Extensions\ValidationRakit\Contracts\RuleInterface;
use Rakit\Validation\MissingRequiredParameterException;
use Rakit\Validation\Rule as RakitRule;

final class RuleAdapter extends RakitRule
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
