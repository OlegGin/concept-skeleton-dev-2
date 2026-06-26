<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Requests;

use Concept\Components\SettingsManager\Dto\StoreSettingDto;
use Concept\Components\SettingsManager\Enums\SettingDataType;
use Concept\Components\SettingsManager\Enums\SettingGroup;
use Concept\Core\Http\Requests\FormRequest;

/** @extends FormRequest<StoreSettingDto> */
class StoreSettingRequest extends FormRequest
{
    protected ?string $dtoClass = StoreSettingDto::class;

    public function rules(): array
    {
        $group = $this->resolveGroupFromInput();

        return [
            'setting_key' => ['required', 'max:255', 'unique:settings,setting_key,NULL,NULL,setting_group,' . $group],
            'setting_group' => ['required', 'in:' . implode(',', array_column(SettingGroup::cases(), 'value'))],
            'data_type' => ['required', 'in:' . implode(',', array_column(SettingDataType::cases(), 'value'))],
            'setting_value' => ['required'],
            'description' => ['nullable', 'max:65535'],
        ];
    }

    private function resolveGroupFromInput(): string
    {
        $group = $this->all()['setting_group'] ?? SettingGroup::GENERAL->value;

        return is_string($group) ? $group : SettingGroup::GENERAL->value;
    }
}
