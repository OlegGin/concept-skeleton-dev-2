<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Services;

use Concept\Components\SettingsManager\Cache\Contracts\SettingsCacheInterface;
use Concept\Components\SettingsManager\Models\SettingModel;
use Concept\Components\SettingsManager\Services\Contracts\SettingsManagerInterface;
use Concept\Components\SettingsManager\Support\SettingValueCodec;

final class SettingsManager implements SettingsManagerInterface
{
    private const string CACHE_MISS = '__settings_cache_miss__';

    public function __construct(
        private readonly SettingValueCodec $codec,
        private readonly SettingsCacheInterface $cache,
    ) {}

    public function get(string $key, mixed $default = null, string $group = SettingModel::DEFAULT_GROUP): mixed
    {
        $cacheKey = $this->settingCacheKey($group, $key);

        if ($this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);

            return $cached === self::CACHE_MISS ? $default : $cached;
        }

        $setting = $this->findSetting($group, $key);

        if ($setting === null) {
            $this->cache->put($cacheKey, self::CACHE_MISS);

            return $default;
        }

        $value = $this->codec->cast($setting->getSettingValue(), $setting->getDataType());
        $this->cache->put($cacheKey, $value);

        return $value;
    }

    public function set(
        string $key,
        mixed $value,
        string $group = SettingModel::DEFAULT_GROUP,
        ?string $dataType = null,
        ?int $id = null,
    ): void {
        $resolvedDataType = $dataType ?? $this->codec->determineDataType($value);
        $serializedValue = $this->codec->serialize($value, $resolvedDataType);

        if ($id !== null) {
            $this->updateExisting($id, $key, $group, $serializedValue, $resolvedDataType);

            return;
        }

        SettingModel::query()->updateOrCreate(
            [
                SettingModel::FIELD_SETTING_GROUP => $group,
                SettingModel::FIELD_SETTING_KEY => $key,
            ],
            [
                SettingModel::FIELD_SETTING_VALUE => $serializedValue,
                SettingModel::FIELD_DATA_TYPE => $resolvedDataType,
            ]
        );

        $this->invalidateSettingCache($group, $key);
        $this->invalidateGroupCache($group);
    }

    public function delete(string $key, string $group = SettingModel::DEFAULT_GROUP): void
    {
        $existing = $this->findSetting($group, $key);

        if ($existing === null) {
            return;
        }

        $existing->delete();

        $this->invalidateSettingCache($group, $key);
        $this->invalidateGroupCache($group);
    }

    public function getGroup(string $group): array
    {
        $cacheKey = $this->groupCacheKey($group);

        if ($this->cache->has($cacheKey)) {
            /** @var array<string, mixed> $cached */
            $cached = $this->cache->get($cacheKey);

            return $cached;
        }

        $settings = SettingModel::query()
            ->where(SettingModel::FIELD_SETTING_GROUP, $group)
            ->get();

        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $this->codec->cast(
                $setting->getSettingValue(),
                $setting->getDataType()
            );
        }

        $this->cache->put($cacheKey, $result);

        return $result;
    }

    public function has(string $key, string $group = SettingModel::DEFAULT_GROUP): bool
    {
        $cacheKey = $this->settingCacheKey($group, $key);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey) !== self::CACHE_MISS;
        }

        return $this->findSetting($group, $key) !== null;
    }

    private function updateExisting(
        int $id,
        string $key,
        string $group,
        string $serializedValue,
        string $resolvedDataType,
    ): void {
        $existing = SettingModel::query()->find($id);

        if (!($existing instanceof SettingModel)) {
            return;
        }

        $previousGroup = $existing->getSettingGroup();
        $previousKey = $existing->getSettingKey();

        $existing->update([
            SettingModel::FIELD_SETTING_KEY => $key,
            SettingModel::FIELD_SETTING_GROUP => $group,
            SettingModel::FIELD_SETTING_VALUE => $serializedValue,
            SettingModel::FIELD_DATA_TYPE => $resolvedDataType,
            SettingModel::FIELD_UPDATED_AT => date('Y-m-d H:i:s'),
        ]);

        $this->invalidateSettingCache($previousGroup, $previousKey);
        $this->invalidateSettingCache($group, $key);
        $this->invalidateGroupCache($previousGroup);
        $this->invalidateGroupCache($group);
    }

    private function findSetting(string $group, string $key): ?SettingModel
    {
        $setting = SettingModel::query()
            ->where(SettingModel::FIELD_SETTING_GROUP, $group)
            ->where(SettingModel::FIELD_SETTING_KEY, $key)
            ->first();

        return $setting instanceof SettingModel ? $setting : null;
    }

    private function invalidateSettingCache(string $group, string $key): void
    {
        $this->cache->forget($this->settingCacheKey($group, $key));
    }

    private function invalidateGroupCache(string $group): void
    {
        $this->cache->forget($this->groupCacheKey($group));
    }

    private function settingCacheKey(string $group, string $key): string
    {
        return 'setting:' . $group . ':' . $key;
    }

    private function groupCacheKey(string $group): string
    {
        return 'group:' . $group;
    }
}
