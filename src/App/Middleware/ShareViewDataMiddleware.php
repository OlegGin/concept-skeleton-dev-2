<?php declare(strict_types=1);

namespace Concept\App\Middleware;

use Concept\App\Foundation\SessionKey;
use Concept\Extensions\Csrf\Contracts\CsrfTokenManagerInterface;
use Concept\Extensions\Http\Requests\RequestAttribute;
use Concept\Extensions\SessionSymfony\Contracts\FlashBagInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ShareViewDataMiddleware implements MiddlewareInterface
{
    private const string TEMPLATE_ERRORS = 'errors';
    private const string TEMPLATE_OLD_INPUT = 'old';
    private const string TEMPLATE_FLASHES = 'flashes';
    private const string TEMPLATE_CSRF_TOKEN = 'csrf_token';

    public function __construct(
        private readonly FlashBagInterface $flashBag,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute(RequestAttribute::VIEW_PAYLOAD, [
            self::TEMPLATE_ERRORS => $this->flashBag->get(SessionKey::VALIDATION_ERRORS, []),
            self::TEMPLATE_OLD_INPUT => $this->flashBag->get(SessionKey::VALIDATION_DATA, []),
            self::TEMPLATE_FLASHES => $this->flashBag->all(),
            self::TEMPLATE_CSRF_TOKEN => $this->csrfTokenManager->getToken(),
        ]));
    }
}
