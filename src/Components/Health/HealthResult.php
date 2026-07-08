<?php declare(strict_types=1);

namespace Concept\Components\Health;

final readonly class HealthResult
{
    public function __construct(
        public HealthStatus $status,
        public string $message,
    ) {}

    public static function ok(string $message): self
    {
        return new self(HealthStatus::Ok, $message);
    }

    public static function warn(string $message): self
    {
        return new self(HealthStatus::Warn, $message);
    }

    public static function fail(string $message): self
    {
        return new self(HealthStatus::Fail, $message);
    }

    public static function skip(string $message): self
    {
        return new self(HealthStatus::Skip, $message);
    }
}
