<?php declare(strict_types=1);

namespace Concept\Components\Acl\Models;

use Concept\Common\Models\BaseModel;
use Concept\Components\Acl\Enums\AclPrivilege;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AclRouteRuleModel extends BaseModel
{
    public const string TABLE_NAME = 'acl_route_rules';

    public const string FIELD_ROUTE_NAME = 'route_name';
    public const string FIELD_RESOURCE_ID = 'resource_id';
    public const string FIELD_PRIVILEGE = 'privilege';
    public const string FIELD_REDIRECT_ROUTE_NAME = 'redirect_route_name';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $table = self::TABLE_NAME;

    /** @var array<int, string> */
    protected $fillable = [
        self::FIELD_ROUTE_NAME,
        self::FIELD_RESOURCE_ID,
        self::FIELD_PRIVILEGE,
        self::FIELD_REDIRECT_ROUTE_NAME,
    ];

    public function getRouteName(): string
    {
        return (string) $this->getAttribute(self::FIELD_ROUTE_NAME);
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

    public function getRedirectRouteName(): ?string
    {
        $value = $this->attributes[self::FIELD_REDIRECT_ROUTE_NAME] ?? null;
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /** @return BelongsTo<AclResourceModel, $this> */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(AclResourceModel::class, self::FIELD_RESOURCE_ID);
    }
}
