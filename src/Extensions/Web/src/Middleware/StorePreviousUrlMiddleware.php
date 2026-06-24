<?php declare(strict_types=1);

namespace Concept\Extensions\Web\Middleware;

use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpMethod;
use Concept\Extensions\Http\Protocol\HttpValue;
use Concept\Extensions\Http\Requests\RequestAttribute;
use Concept\Extensions\SessionSymfony\Contracts\SessionInterface;
use Concept\Extensions\Web\Protocol\UrlSessionKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class StorePreviousUrlMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionInterface $session,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        if ($method === HttpMethod::GET && !$this->isAjax($request)) {
            $currentInSession = $this->session->get(UrlSessionKey::CURRENT);

            if ($uri !== $currentInSession) {
                $this->session->set(UrlSessionKey::PREVIOUS, $currentInSession);
                $this->session->set(UrlSessionKey::CURRENT, $uri);
            }
        }

        $backUrl = $method === HttpMethod::GET
            ? $this->session->get(UrlSessionKey::PREVIOUS)
            : $this->session->get(UrlSessionKey::CURRENT);

        return $handler->handle(
            $request->withAttribute(RequestAttribute::SAFE_BACK_URL, $backUrl),
        );
    }

    private function isAjax(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine(HttpHeader::X_REQUESTED_WITH) === HttpValue::XML_HTTP_REQUEST;
    }
}
