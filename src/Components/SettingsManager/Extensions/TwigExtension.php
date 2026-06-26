<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Extensions;

use Concept\Components\SettingsManager\Enums\SettingGroup;
use Concept\Components\SettingsManager\Services\Contracts\SettingsManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    public function __construct(private readonly SettingsManagerInterface $settings) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('settings', fn(): SettingsManagerInterface => $this->settings),
            new TwigFunction('setting', $this->getSetting(...)),
            new TwigFunction('has_setting', $this->hasSetting(...)),
            new TwigFunction('settings_group', $this->getGroup(...)),
        ];
    }

    public function getSetting(
        string $key,
        mixed $default = null,
        string $group = SettingGroup::GENERAL->value,
    ): mixed {
        return $this->settings->get($key, $default, $group);
    }

    public function hasSetting(string $key, string $group = SettingGroup::GENERAL->value): bool
    {
        return $this->settings->has($key, $group);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group = SettingGroup::GENERAL->value): array
    {
        return $this->settings->getGroup($group);
    }
}
