<?php declare(strict_types=1);

namespace Concept\Components\DebugBar\Middlewares;

use Concept\Core\Http\Requests\RequestFormat;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use DebugBar\JavascriptRenderer;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DebugBarMiddleware implements MiddlewareInterface
{
    private const string HEAD_TAG_OPEN = '<head>';
    private const string BODY_TAG_CLOSE = '</body>';

    public function __construct(
        private readonly RequestFormat $requestFormat,
        private readonly ConfigInterface $config,
        private readonly JavascriptRenderer $debugBarRenderer,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->config->getBool('app.debug', false)) {
            return $handler->handle($request);
        }

        // Send request throw kernel (for render templates...)
        $response = $handler->handle($request);

        if ($this->requestFormat->expectsHtml($request)) {
            return $this->injectDebugBar($response);
        }

        return $response;
    }

    private function injectDebugBar(ResponseInterface $response): ResponseInterface
    {
        $head = $this->debugBarRenderer->renderHead();
        $body = $this->debugBarRenderer->render();

        $content = (string) $response->getBody();

        if (str_contains($content, self::BODY_TAG_CLOSE)) {
            // Replace only the last occurrence of </body>
            $pos = strrpos($content, self::BODY_TAG_CLOSE);
            if ($pos !== false) {
                $content = substr_replace($content, $body . self::BODY_TAG_CLOSE, $pos, strlen(self::BODY_TAG_CLOSE));
            }

            // Replace only the first occurrence of <head>
            $pos = strpos($content, self::HEAD_TAG_OPEN);
            if ($pos !== false) {
                $content = substr_replace($content, self::HEAD_TAG_OPEN . $head, $pos, strlen(self::HEAD_TAG_OPEN));
            }
        } else {
            $content .= $head . $body;
        }

        $factory = new StreamFactory();
        $bodyStream = $factory->createStream($content);

        return $response->withBody($bodyStream);
    }
}