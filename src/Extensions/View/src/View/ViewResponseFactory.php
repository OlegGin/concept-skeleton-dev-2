<?php declare(strict_types=1);

namespace Concept\Extensions\View\View;

use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Protocol\HttpValue;
use Concept\Extensions\View\Contracts\ViewInterface;
use Concept\Extensions\View\Contracts\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class ViewResponseFactory implements ViewResponseFactoryInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ViewInterface $view,
    ) {}

    public function create(
        string $template,
        array $data = [],
        int $code = HttpStatusCode::OK,
    ): ResponseInterface {
        $content = $this->view->render($template, $data);
        $response = $this->responseFactory->createResponse($code);
        $response->getBody()->write($content);

        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::HTML);
    }
}
