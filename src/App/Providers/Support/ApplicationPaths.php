<?php declare(strict_types=1);

namespace Concept\App\Providers\Support;

use Concept\App\Foundation\PathName;
use Concept\Extensions\PathManager\PathManager;

final class ApplicationPaths
{
    public function __construct(private readonly PathManager $pathManager) {}

    public function logFile(string $logFile): string
    {
        return $this->pathManager->get(PathName::LOGS, $logFile);
    }

    public function root(string $path): string
    {
        return $this->pathManager->root($path);
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    public function resolveList(array $paths): array
    {
        return array_values(array_map(fn(string $path): string => $this->root($path), $paths));
    }

    /**
     * @param array<string, string> $paths
     * @return array<string, string>
     */
    public function resolveMap(array $paths): array
    {
        $resolved = [];
        foreach ($paths as $key => $path) {
            $resolved[$key] = $this->root($path);
        }

        return $resolved;
    }
}
