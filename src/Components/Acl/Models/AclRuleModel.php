<?php declare(strict_types=1);

namespace Concept\Components\Acl\Models;

use Concept\Common\Models\BaseModel;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Enums\AclRuleType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AclRuleModel extends BaseModel
{
    public const string TABLE_NAME = 'acl_rules';

    public const string FIELD_TYPE = 'type';
    public const string FIELD_ROLE_ID = 'role_id';
    public const string FIELD_RESOURCE_ID = 'resource_id';
    public const string FIELD_PRIVILEGE = 'privilege';
    public const string FIELD_CREATED_AT = 'created_at';

    protected $table = self::TABLE_NAME;

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        self::FIELD_TYPE,
        self::FIELD_ROLE_ID,
        self::FIELD_RESOURCE_ID,
        self::FIELD_PRIVILEGE,
    ];

    public function getType(): AclRuleType
    {
        return AclRuleType::from((string) $this->getAttribute(self::FIELD_TYPE));
    }

    public function getPrivilege(): ?string
    {
        $value = $this->getAttribute(self::FIELD_PRIVILEGE);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getPrivilegeEnum(): ?AclPrivilege
    {
        $value = $this->getPrivilege();

        return $value !== null ? AclPrivilege::tryFrom($value) : null;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AclRoleModel::class, self::FIELD_ROLE_ID);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(AclResourceModel::class, self::FIELD_RESOURCE_ID);
    }
}
