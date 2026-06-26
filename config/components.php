<?php declare(strict_types=1);

use Concept\Components\Acl\AclComponent;
use Concept\Components\AuthAdmin\AuthAdminComponent;
use Concept\Components\SettingsManager\SettingsManagerComponent;

return [
    'components' => [
        AclComponent::class => AclComponent::class,
        AuthAdminComponent::class => AuthAdminComponent::class,
        SettingsManagerComponent::class => SettingsManagerComponent::class,
    ],
];
