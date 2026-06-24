<?php declare(strict_types=1);

namespace Concept\Extensions\ValidationRakit\Adapters;

use Concept\Extensions\ValidationRakit\Contracts\ValidationInterface;
use Rakit\Validation\Validation as LibraryValidation;

final class ValidationAdapter implements ValidationInterface
{
    public function __construct(private readonly LibraryValidation $validation) {}

    public function validate(): void
    {
        $this->validation->validate();
    }

    public function isValid(): bool
    {
        return !$this->validation->fails();
    }

    public function getValidData(): array
    {
        return $this->validation->getValidData();
    }

    public function getErrors(): array
    {
        return $this->validation->errors()->toArray();
    }

    public function setAliases(array $aliases): void
    {
        $this->validation->setAliases($aliases);
    }

    public function setMessages(array $messages): void
    {
        $this->validation->setMessages($messages);
    }

    public function setTranslations(array $translations): void
    {
        $this->validation->setTranslations($translations);
    }
}
