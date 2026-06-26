<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Models;

use Concept\App\Models\BaseModel;
use Concept\Components\SettingsManager\Enums\SettingGroup;

class SettingModel extends BaseModel
{
    public const string TABLE_NAME = 'settings';
    public const string FIELD_SETTING_KEY = 'setting_key';
    public const string FIELD_SETTING_VALUE = 'setting_value';
    public const string FIELD_SETTING_GROUP = 'setting_group';
    public const string FIELD_DATA_TYPE = 'data_type';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    public const string DEFAULT_GROUP = SettingGroup::GENERAL->value;

    protected $table = self::TABLE_NAME;

    /** @var array<int, string> */
    protected $fillable = [
        self::FIELD_SETTING_KEY,
        self::FIELD_SETTING_VALUE,
        self::FIELD_SETTING_GROUP,
        self::FIELD_DATA_TYPE,
        self::FIELD_DESCRIPTION,
    ];

    public function getSettingKey(): string
    {
        return (string)$this->getAttribute(self::FIELD_SETTING_KEY);
    }

    public function getSettingValue(): string
    {
        return (string)$this->getAttribute(self::FIELD_SETTING_VALUE);
    }

    public function getSettingGroup(): string
    {
        return (string)$this->getAttribute(self::FIELD_SETTING_GROUP);
    }

    public function getDataType(): string
    {
        return (string)$this->getAttribute(self::FIELD_DATA_TYPE);
    }
}
