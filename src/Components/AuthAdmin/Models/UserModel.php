<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Models;

use Concept\Common\Models\BaseModel;
use Concept\Components\Acl\Models\AclRoleModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserModel extends BaseModel
{
    use SoftDeletes;

    public const string TABLE_NAME = 'users';

    public const string FIELD_NAME = 'name';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_PASSWORD = 'password';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_VERIFICATION_TOKEN = 'verification_token';
    public const string FIELD_PASSWORD_RESET_TOKEN = 'password_reset_token';
    public const string FIELD_RESET_TOKEN_EXPIRES_AT = 'reset_token_expires_at';
    public const string FIELD_IS_ADMIN = 'is_admin';
    public const string FIELD_ACL_ROLE_ID = 'acl_role_id';
    public const string FIELD_EMAIL_VERIFIED_AT = 'email_verified_at';
    public const string FIELD_REMEMBER_TOKEN = 'remember_token';
    public const string FIELD_CREATED_AT = 'created_at';
    public const string FIELD_UPDATED_AT = 'updated_at';
    public const string FIELD_DELETED_AT = 'deleted_at';

    protected $table = self::TABLE_NAME;

    /** @var array<int, string> */
    protected $fillable = [
        self::FIELD_NAME,
        self::FIELD_EMAIL,
        self::FIELD_PASSWORD,
        self::FIELD_STATUS,
        self::FIELD_VERIFICATION_TOKEN,
        self::FIELD_PASSWORD_RESET_TOKEN,
        self::FIELD_RESET_TOKEN_EXPIRES_AT,
        self::FIELD_IS_ADMIN,
        self::FIELD_ACL_ROLE_ID,
        self::FIELD_EMAIL_VERIFIED_AT,
        self::FIELD_REMEMBER_TOKEN,
    ];

    /** @var string[] */
    protected $hidden = [
        self::FIELD_PASSWORD,
    ];

    /** @var string[] */
    protected $dates = [
        self::FIELD_DELETED_AT,
    ];

    public function getName(): string
    {
        return (string)$this->getAttribute(self::FIELD_NAME);
    }

    public function getEmail(): string
    {
        return (string)$this->getAttribute(self::FIELD_EMAIL);
    }

    public function isAdmin(): bool
    {
        return (bool)$this->getAttribute(self::FIELD_IS_ADMIN);
    }

    /** @return BelongsTo<AclRoleModel, $this> */
    public function aclRole(): BelongsTo
    {
        return $this->belongsTo(AclRoleModel::class, self::FIELD_ACL_ROLE_ID);
    }

    public function getStatus(): string
    {
        return (string)$this->getAttribute(self::FIELD_STATUS);
    }

    public function isEmailVerified(): bool
    {
        return $this->getAttribute(self::FIELD_EMAIL_VERIFIED_AT) !== null;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, (string)$this->getAttribute(self::FIELD_PASSWORD));
    }

    protected function setPasswordAttribute(string $value): void
    {
        $this->attributes[self::FIELD_PASSWORD] = password_hash($value, PASSWORD_DEFAULT);
    }
}