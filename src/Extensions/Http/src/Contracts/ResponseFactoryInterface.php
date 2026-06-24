<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Contracts;

use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Psr\Http\Message\ResponseFactoryInterface as PsrResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResponseFactoryInterface extends PsrResponseFactoryInterface
{
    public function json(
        mixed $data,
        int $code = HttpStatusCode::OK,
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface;

    public function jsonSuccess(
        mixed $data = [],
        int $code = HttpStatusCode::OK,
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface;

    /**
     * @param array<string, mixed> $errors
     */
    public function jsonError(
        string $message,
        int $code = HttpStatusCode::INTERNAL_SERVER_ERROR,
        array $errors = [],
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface;

    public function redirect(string $url, int $status = HttpStatusCode::FOUND): ResponseInterface;

    /**
     * @param array<string, mixed> $parameters
     */
    public function redirectByName(
        ServerRequestInterface $request,
        string $urlName,
        array $parameters = [],
        int $status = HttpStatusCode::FOUND
    ): ResponseInterface;

    public function redirectBack(
        ServerRequestInterface $request,
        int $status = HttpStatusCode::FOUND,
        string $fallback = '/'
    ): ResponseInterface;
}
