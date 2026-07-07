<?php declare(strict_types=1);

namespace Concept\App\Http\Error\Handlers;

use Closure;
use Concept\App\Http\Error\Contracts\ExceptionReporterInterface;
use Throwable;
use Whoops\Handler\Handler;

final class ReportExceptionHandler extends Handler
{
    /**
     * @param Closure(): ExceptionReporterInterface $reporterFactory
     */
    public function __construct(
        private readonly Closure $reporterFactory,
    ) {}

    public function handle(): int
    {
        try {
            ($this->reporterFactory)()->report($this->getException());
        } catch (Throwable) {
        }

        return Handler::DONE;
    }
}
