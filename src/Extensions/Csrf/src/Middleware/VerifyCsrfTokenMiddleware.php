<?php declare(strict_types=1);

namespace Concept\Extensions\Csrf\Middleware;

use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\Csrf\Exceptions\CsrfException;
use Concept\Extensions\Csrf\Protocol\CsrfField;
use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class VerifyCsrfTokenMiddleware implements MiddlewareInterface
{
    private const array PROTECTED_METHODS = [
        HttpMethod::POST,
        HttpMethod::PUT,
        HttpMethod::PATCH,
        HttpMethod::DELETE,
    ];

    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), self::PROTECTED_METHODS, true)) {
            return $handler->handle($request);
        }

        if (!$this->csrfTokenManager->validate($this->getTokenFromRequest($request))) {
            throw new CsrfException();
        }

        return $handler->handle($request);
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody) && isset($parsedBody[CsrfField::NAME])) {
            $token = $parsedBody[CsrfField::NAME];
            if (is_string($token)) {
                return $token;
            }
        }

        if ($request->hasHeader(HttpHeader::X_CSRF_TOKEN)) {
            return $request->getHeaderLine(HttpHeader::X_CSRF_TOKEN);
        }

        if ($request->hasHeader(HttpHeader::X_XSRF_TOKEN)) {
            return urldecode($request->getHeaderLine(HttpHeader::X_XSRF_TOKEN));
        }

        return null;
    }
}
