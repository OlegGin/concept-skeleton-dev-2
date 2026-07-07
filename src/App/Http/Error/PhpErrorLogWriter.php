<?php declare(strict_types=1);

namespace Concept\App\Http\Error;

use Concept\App\Http\Error\Contracts\ExceptionReporterInterface;
use Throwable;

final class PhpErrorLogWriter implements ExceptionReporterInterface
{
    private const string LOG_LINE_FORMAT = "[%s] app.ERROR: %s %s\n";
    private const string LOG_LINE_FALLBACK_FORMAT = "[%s] app.ERROR: %s\n";

    public function report(Throwable $exception, string $uri = '', bool $bootstrap = false): void
    {
        if ($uri === '' && isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        try {
            $context = json_encode([
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'uri' => $uri,
                'bootstrap' => true,
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            $line = sprintf(
                self::LOG_LINE_FORMAT,
                date('c'),
                $exception->getMessage(),
                $context,
            );
        } catch (Throwable) {
            $line = sprintf(
                self::LOG_LINE_FALLBACK_FORMAT,
                date('c'),
                $exception->getMessage(),
            );
        }

        error_log(rtrim($line));
    }
}
