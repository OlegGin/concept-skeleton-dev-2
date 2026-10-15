<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Foundation\PathName;
use Concept\Extensions\PathManager\PathManager;
use Concept\Extensions\Telemetry\TelemetryCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sandbox helper: dump collector payload to storage/logs when ?telemetry=1.
 */
final class DumpTelemetryMiddleware implements MiddlewareInterface
{
    private const string QUERY_FLAG = 'telemetry';
    private const string DUMP_FILE = 'telemetry-dump.json';
    private const string HEADER = 'X-Telemetry-Dump';

    public function __construct(
        private readonly TelemetryCollector $collector,
        private readonly PathManager $pathManager,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->shouldDump($request)) {
            return $response;
        }

        $path = $this->pathManager->get(PathName::LOGS, self::DUMP_FILE);
        $payload = [
            'request' => [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'dumped_at' => date('c'),
            ],
            'telemetry' => $this->collector->toArray(),
        ];

        file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        return $response->withHeader(self::HEADER, $path);
    }

    private function shouldDump(ServerRequestInterface $request): bool
    {
        $query = $request->getQueryParams();

        return isset($query[self::QUERY_FLAG])
            && (string) $query[self::QUERY_FLAG] === '1';
    }
}
