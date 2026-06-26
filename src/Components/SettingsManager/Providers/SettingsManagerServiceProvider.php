<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Providers;

use Concept\Components\SettingsManager\Cache\Contracts\SettingsCacheInterface;
use Concept\Components\SettingsManager\Cache\InMemorySettingsCache;
use Concept\Components\SettingsManager\Extensions\TwigExtension;
use Concept\Components\SettingsManager\Mappers\SettingFormValueMapper;
use Concept\Components\SettingsManager\Services\Contracts\SettingsManagerInterface;
use Concept\Components\SettingsManager\Services\SettingsManager;
use Concept\Components\SettingsManager\Support\SettingValueCodec;
use League\Container\ServiceProvider\AbstractServiceProvider;

class SettingsManagerServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [
            SettingsCacheInterface::class,
            SettingValueCodec::class,
            SettingFormValueMapper::class,
            SettingsManagerInterface::class,
            TwigExtension::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(SettingsCacheInterface::class, function () {
            return new InMemorySettingsCache();
        })->setShared(true);

        $container->add(SettingValueCodec::class, function () {
            return new SettingValueCodec();
        })->setShared(true);

        $container->add(SettingFormValueMapper::class, function () use ($container) {
            /** @var SettingValueCodec $codec */
            $codec = $container->get(SettingValueCodec::class);

            return new SettingFormValueMapper($codec);
        })->setShared(true);

        $container->add(SettingsManagerInterface::class, function () use ($container) {
            /** @var SettingValueCodec $codec */
            $codec = $container->get(SettingValueCodec::class);
            /** @var SettingsCacheInterface $cache */
            $cache = $container->get(SettingsCacheInterface::class);

            return new SettingsManager($codec, $cache);
        })->setShared(true);

        $container->add(TwigExtension::class, function () use ($container) {
            /** @var SettingsManagerInterface $settings */
            $settings = $container->get(SettingsManagerInterface::class);

            return new TwigExtension($settings);
        })->setShared(true);
    }
}
