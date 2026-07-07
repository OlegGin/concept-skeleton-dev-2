<?php declare(strict_types=1);

namespace Concept\App\Http\Error;

use Concept\App\Http\Error\Contracts\ExceptionReporterInterface;
use Concept\App\Http\Error\Contracts\HttpErrorRendererInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Protocol\HttpValue;
use Concept\Extensions\Http\Requests\RequestFormat;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Concept\Extensions\View\Support\ViewRouteNamespaceResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class TwigHttpErrorRenderer implements HttpErrorRendererInterface
{
    private const string DEFAULT_NAMESPACE = 'frontend';
    private const string TEMPLATE_FORMAT = '@%s/errors/%d';
    private const string FALLBACK_FILE_FORMAT = '%s/%d.php';
    private const string HTML_CRITICAL_ERROR = '<h1>%d %s</h1>';

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly RequestFormat $requestFormat,
        private readonly ViewRouteNamespaceResolver $routeNamespaceResolver,
        private readonly ExceptionReporterInterface $exceptionReporter,
        private readonly string $fallbackPath,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors
     */
    public function render(
        ServerRequestInterface $request,
        int $code,
        ?string $message = null,
        array $data = [],
        array $errors = [],
    ): ResponseInterface {
        $message ??= HttpStatusCode::getReasonPhrase($code);

        if ($this->requestFormat->expectsJson($request)) {
            return $this->responseFactory->jsonError($message, $code, $errors);
        }

        $namespace = $this->routeNamespaceResolver->resolve($request) ?? self::DEFAULT_NAMESPACE;
        $template = sprintf(self::TEMPLATE_FORMAT, $namespace, $code);

        try {
            return $this->viewResponse->create($template, $data, $code);
        } catch (Throwable $exception) {
            $this->exceptionReporter->report($exception);

            return $this->renderFallback($code);
        }
    }

    private function renderFallback(int $code): ResponseInterface
    {
        $file = sprintf(self::FALLBACK_FILE_FORMAT, $this->fallbackPath, $code);
        if (!is_file($file)) {
            $file = sprintf(self::FALLBACK_FILE_FORMAT, $this->fallbackPath, HttpStatusCode::INTERNAL_SERVER_ERROR);
        }

        ob_start();
        if (is_file($file)) {
            include $file;
        } else {
            echo sprintf(
                self::HTML_CRITICAL_ERROR,
                $code,
                HttpStatusCode::getReasonPhrase($code),
            );
        }

        $response = $this->responseFactory->createResponse($code);
        $response->getBody()->write((string) ob_get_clean());

        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::HTML);
    }
}
