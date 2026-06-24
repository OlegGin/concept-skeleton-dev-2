<?php declare(strict_types=1);

namespace Concept\App\Http;

use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Protocol\HttpValue;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;

final class LoginFormResponse
{
    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed> $old
     */
    public static function create(array $errors = [], array $old = [], ?string $success = null): ResponseInterface
    {
        $emailValue = $old['email'] ?? '';
        $email = htmlspecialchars(is_string($emailValue) ? $emailValue : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $errorHtml = self::renderErrors($errors);
        $successHtml = $success !== null
            ? '<p class="success">' . htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            : '';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body { font-family: sans-serif; max-width: 24rem; margin: 2rem auto; }
        label { display: block; margin-top: 1rem; }
        input { width: 100%; padding: 0.5rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: 0.5rem 1rem; }
        .errors { color: #b00020; margin: 0; padding-left: 1.2rem; }
        .success { color: #0a7a2f; }
    </style>
</head>
<body>
    <h1>Login</h1>
    {$successHtml}
    {$errorHtml}
    <form method="post" action="/login">
        <label>
            Email
            <input type="email" name="email" value="{$email}" autocomplete="username">
        </label>
        <label>
            Password
            <input type="password" name="password" autocomplete="current-password">
        </label>
        <button type="submit">Sign in</button>
    </form>
</body>
</html>
HTML;

        $response = new Response();
        $response->getBody()->write($html);

        return $response
            ->withStatus(HttpStatusCode::OK)
            ->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::HTML);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private static function renderErrors(array $errors): string
    {
        if ($errors === []) {
            return '';
        }

        $items = [];
        foreach ($errors as $fieldErrors) {
            foreach ($fieldErrors as $message) {
                $items[] = '<li>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
            }
        }

        return '<ul class="errors">' . implode('', $items) . '</ul>';
    }
}
