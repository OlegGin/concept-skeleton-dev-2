<?php declare(strict_types=1);

use Concept\Components\Acl\AclComponent;
use Concept\Components\AuthAdmin\AuthAdminComponent;
use Concept\Components\Health\HealthComponent;
use Concept\Components\SettingsManager\SettingsManagerComponent;
use Concept\Components\SystemLog\SystemLogComponent;

return [
    'components' => [
        AuthAdminComponent::class => AuthAdminComponent::class,
        AclComponent::class => AclComponent::class,
        SettingsManagerComponent::class => SettingsManagerComponent::class,
        SystemLogComponent::class => SystemLogComponent::class,
        HealthComponent::class => HealthComponent::class,
    ],
];
