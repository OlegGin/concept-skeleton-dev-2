<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Support;

use Concept\Core\Services\DataMasker\Contracts\DataMaskerInterface;
use Concept\Core\Services\Session\Contracts\SessionInterface;
use Concept\Core\Services\Telemetry\TelemetryCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use Psr\Http\Message\ServerRequestInterface;

class CustomDebugBar extends DebugBar
{
    public function __construct(
        ServerRequestInterface $request,
        SessionInterface $session,
        TelemetryCollector $telemetryCollector,
        DataMaskerInterface $masker
    ) {
        $this->addCollector(new RequestDataCollector($request, $session, $telemetryCollector, $masker));
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MessagesDataCollector($telemetryCollector));
        $this->addCollector(new TimeDataCollector());
        $this->addCollector(new MemoryCollector());
    }
}
