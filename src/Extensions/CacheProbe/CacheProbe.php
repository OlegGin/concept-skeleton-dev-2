<?php declare(strict_types=1);

namespace Concept\Extensions\CacheProbe;

use Concept\Extensions\LoggerMonolog\Contracts\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * Demo consumer: proves a custom brick can require cache + logging.
 */
final class CacheProbe
{
    private const string KEY = 'cache-probe';

    private const string ERR_UNEXPECTED = 'CacheProbe expected string from cache, got %s.';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function ping(): string
    {
        $this->cache->set(self::KEY, 'ok', 60);
        $value = $this->cache->get(self::KEY);

        if (!is_string($value)) {
            throw new RuntimeException(sprintf(self::ERR_UNEXPECTED, get_debug_type($value)));
        }

        $this->logger->debug('CacheProbe ping', ['value' => $value]);

        return $value;
    }
}
