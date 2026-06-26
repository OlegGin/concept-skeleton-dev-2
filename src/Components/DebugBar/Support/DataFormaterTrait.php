<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\App\Telemetry\Contracts\TelemetryItemInterface;
use DateTimeImmutable;

trait DataFormaterTrait
{
    private function formatStartedAt(?float $startedAt): string
    {
        if ($startedAt === null) {
            return '-';
        }

        $date = DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', $startedAt));
        if ($date === false) {
            return (string) $startedAt;
        }

        return $date->format('Y-m-d H:i:s.v');
    }

    private function getContextAttribute(TelemetryItemInterface $telemetryItem, string $attribute): string
    {
        $itemContext = $telemetryItem->getContext();
        if (is_scalar($itemContext[$attribute])) {
            return (string) $itemContext[$attribute];
        }

        return '';
    }

    public function formatDuration(?float $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }

        if ($seconds < 0.0001) {
            return round($seconds * 1000000, 2) . 'μs';
        } elseif ($seconds < 0.1) {
            return round($seconds * 1000, 2) . 'ms';
        }

        return round($seconds, 2) . 's';
    }
}