<?php declare(strict_types=1);

use Concept\App\Validation\Rules\ExistsRule;
use Concept\App\Validation\Rules\UniqueRule;

return [
    'validator' => [
        'rules' => [
            'exists' => ExistsRule::class,
            'unique' => UniqueRule::class,
        ],
        'log_enabled' => false,
        'log_file' => 'validation.log',
        'log_max_files' => 7,
    ],
];
