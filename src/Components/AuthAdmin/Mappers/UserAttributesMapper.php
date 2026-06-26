<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Mappers;

use Concept\Common\Mappers\FormValueNormalizer;
use Concept\Components\AuthAdmin\Dto\User\StoreUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserDto;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserPasswordDto;
use Concept\Components\AuthAdmin\Models\UserModel;

final class UserAttributesMapper
{
    /**
     * @return array<string, mixed>
     */
    public function fromStore(StoreUserDto $dto): array
    {
        return [
            UserModel::FIELD_NAME => $dto->name,
            UserModel::FIELD_EMAIL => $dto->email,
            UserModel::FIELD_PASSWORD => $dto->password,
            UserModel::FIELD_STATUS => $dto->status,
            UserModel::FIELD_IS_ADMIN => FormValueNormalizer::toBool($dto->is_admin),
            UserModel::FIELD_ACL_ROLE_ID => FormValueNormalizer::nullableInt($dto->acl_role_id),
            UserModel::FIELD_VERIFICATION_TOKEN => FormValueNormalizer::nullableString($dto->verification_token),
            UserModel::FIELD_PASSWORD_RESET_TOKEN => FormValueNormalizer::nullableString($dto->password_reset_token),
            UserModel::FIELD_RESET_TOKEN_EXPIRES_AT => FormValueNormalizer::nullableString($dto->reset_token_expires_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromUpdate(UpdateUserDto $dto): array
    {
        return [
            UserModel::FIELD_NAME => $dto->name,
            UserModel::FIELD_STATUS => $dto->status,
            UserModel::FIELD_IS_ADMIN => FormValueNormalizer::toBool($dto->is_admin),
            UserModel::FIELD_ACL_ROLE_ID => FormValueNormalizer::nullableInt($dto->acl_role_id),
            UserModel::FIELD_VERIFICATION_TOKEN => FormValueNormalizer::nullableString($dto->verification_token),
            UserModel::FIELD_PASSWORD_RESET_TOKEN => FormValueNormalizer::nullableString($dto->password_reset_token),
            UserModel::FIELD_RESET_TOKEN_EXPIRES_AT => FormValueNormalizer::nullableString($dto->reset_token_expires_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromPasswordUpdate(UpdateUserPasswordDto $dto): array
    {
        return [
            UserModel::FIELD_PASSWORD => $dto->password,
        ];
    }
}
