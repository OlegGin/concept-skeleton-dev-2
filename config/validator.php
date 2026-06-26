<?php declare(strict_types=1);

use Concept\App\Validation\Rules\ExistsRule;
use Concept\App\Validation\Rules\UniqueRule;

return [
    'validator' => [
        'rules' => [
            ExistsRule::class,
            UniqueRule::class,
        ],
        'log_enabled' => false,
        'log_path' => 'validation.log',
        'log_max_files' => 7,
    ],
];
