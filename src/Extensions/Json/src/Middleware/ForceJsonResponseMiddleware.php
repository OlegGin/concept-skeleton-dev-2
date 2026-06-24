<?php declare(strict_types=1);

namespace Concept\Extensions\Json\Middleware;

use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ForceJsonResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withHeader(HttpHeader::ACCEPT, HttpValue::JSON);

        $response = $handler->handle($request);

        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::JSON);
    }
}
