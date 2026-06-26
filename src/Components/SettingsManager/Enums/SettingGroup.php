<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Enums;

enum SettingGroup: string
{
    case GENERAL = 'general';
    case MAIL = 'mail';
    case FEATURES = 'features';
}
