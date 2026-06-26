<?php declare(strict_types=1);

namespace Concept\Components\Acl\Models;

use Concept\App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AclRoleModel extends BaseModel
{
    public const string TABLE_NAME = 'acl_roles';

    public const string FIELD_NAME = 'name';
    public const string FIELD_PARENT_ID = 'parent_id';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';

    protected $table = self::TABLE_NAME;

    /** @var array<int, string> */
    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_PARENT_ID,
    ];

    public function getName(): string
    {
        return (string) $this->getAttribute(self::FIELD_NAME);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, self::FIELD_PARENT_ID);
    }

    /** @return HasMany<AclRoleModel, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, self::FIELD_PARENT_ID);
    }

    /** @return HasMany<AclRuleModel, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(AclRuleModel::class, AclRuleModel::FIELD_ROLE_ID);
    }
}
