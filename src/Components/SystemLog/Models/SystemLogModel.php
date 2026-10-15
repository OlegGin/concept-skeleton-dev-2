<?php declare(strict_types=1);

namespace Concept\Components\SystemLog\Models;

use Concept\App\Models\BaseModel;

class SystemLogModel extends BaseModel
{
    public const string TABLE_NAME = 'system_log';

    public const string FIELD_EVENT = 'event';
    public const string FIELD_ACTOR_USER_ID = 'actor_user_id';
    public const string FIELD_SUBJECT_TYPE = 'subject_type';
    public const string FIELD_SUBJECT_ID = 'subject_id';
    public const string FIELD_MESSAGE = 'message';
    public const string FIELD_CONTEXT = 'context';
    public const string FIELD_CREATED_AT = 'created_at';

    public const string SUBJECT_USER = 'user';

    public $timestamps = false;

    protected $table = self::TABLE_NAME;

    /** @var list<string> */
    protected $fillable = [
        self::FIELD_EVENT,
        self::FIELD_ACTOR_USER_ID,
        self::FIELD_SUBJECT_TYPE,
        self::FIELD_SUBJECT_ID,
        self::FIELD_MESSAGE,
        self::FIELD_CONTEXT,
        self::FIELD_CREATED_AT,
    ];

    /** @var array<string, string> */
    protected $casts = [
        self::FIELD_CONTEXT => 'array',
        self::FIELD_CREATED_AT => 'datetime',
    ];
}
