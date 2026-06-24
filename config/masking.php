<?php declare(strict_types=1);

return [
    'masking' => [
        'patterns' => [
            '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.[a-z]{2,}/i' => '***@***.***',
            '/\d{4}-\d{4}-\d{4}-\d{4}/' => '****-****-****-****',
            '/(password|passwd|pwd|repeat_password|password_confirmation|token|_csrf_token|csrf_token|api_key|secret|authorization)[:=]+([^\s,;]+)/i' => '$1=*****',
        ],
        'key_patterns' => [
            '/.*password.*/i',
            '/.*token.*/i',
            '/.*_csrf_token.*/i',
            '/.*secret.*/i',
            '/api_key/i',
            '/authorization/i',
        ],
        'rules' => [],
    ],
];
