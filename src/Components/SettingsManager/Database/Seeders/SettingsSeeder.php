<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Database\Seeders;

use Concept\Components\SettingsManager\Enums\SettingGroup;
use Concept\Components\SettingsManager\Models\SettingModel;
use Concept\Components\SettingsManager\Services\Contracts\SettingsManagerInterface;
use Concept\Core\Services\Database\Contracts\SeederInterface;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder implements SeederInterface
{
    public function __construct(private readonly SettingsManagerInterface $settings) {}

    public function run(): void
    {
        SettingModel::query()->truncate();

        $this->settings->set('app.name', 'Concept Demo', SettingGroup::GENERAL->value, 'string');
        $this->settings->set('app.maintenance', false, SettingGroup::GENERAL->value, 'bool');
        $this->settings->set('mail.from_address', 'noreply@example.com', SettingGroup::MAIL->value, 'string');
        $this->settings->set('mail.retry_attempts', 3, SettingGroup::MAIL->value, 'int');
        $this->settings->set('features.enabled', ['users', 'dashboard'], SettingGroup::FEATURES->value, 'json');
    }
}
