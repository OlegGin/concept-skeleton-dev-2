<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Session\SessionKey;
use Concept\Extensions\Http\Requests\RequestAttribute;
use Concept\Extensions\Session\Contracts\FlashBagInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ShareViewDataMiddleware implements MiddlewareInterface
{
    private const string TEMPLATE_ERRORS = 'errors';
    private const string TEMPLATE_OLD_INPUT = 'old';
    private const string TEMPLATE_FLASHES = 'flashes';

    public function __construct(private readonly FlashBagInterface $flashBag) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute(RequestAttribute::VIEW_PAYLOAD, [
            self::TEMPLATE_ERRORS => $this->flashBag->get(SessionKey::VALIDATION_ERRORS, []),
            self::TEMPLATE_OLD_INPUT => $this->flashBag->get(SessionKey::VALIDATION_DATA, []),
            self::TEMPLATE_FLASHES => $this->flashBag->all(),
        ]));
    }
}
