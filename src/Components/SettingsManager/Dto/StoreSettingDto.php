<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Dto;

use Concept\Core\Services\Dto\Contracts\DtoInterface;
use Concept\Core\Services\Dto\Dto;

class StoreSettingDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $setting_key,
        public readonly string $setting_group,
        public readonly string $data_type,
        public readonly string $setting_value,
        public readonly ?string $description = null,
    ) {}
}
