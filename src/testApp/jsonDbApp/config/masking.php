<?php declare(strict_types=1);

return [
    'masking' => [
        'patterns' => [
            '/(password|token|secret|authorization)[:=]+([^\s,;]+)/i' => '$1=*****',
        ],
        'key_patterns' => [
            '/.*password.*/i',
            '/.*token.*/i',
            '/.*secret.*/i',
        ],
        'rules' => [],
    ],
];
