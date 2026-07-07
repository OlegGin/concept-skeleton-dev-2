<?php declare(strict_types=1);

namespace Concept\App\Http\Error\Handlers;

use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Whoops\Handler\Handler;

final class FallbackFileHandler extends Handler
{
    public function __construct(private readonly string $errorsFallbackFilePath) {}

    public function handle(): int
    {
        if (!headers_sent()) {
            http_response_code(HttpStatusCode::INTERNAL_SERVER_ERROR);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (is_file($this->errorsFallbackFilePath)) {
            include $this->errorsFallbackFilePath;
        }

        return Handler::QUIT;
    }
}
