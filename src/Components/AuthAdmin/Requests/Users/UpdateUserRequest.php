<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Requests\Users;

use Concept\Components\AuthAdmin\Enums\UserStatus;
use Concept\Components\AuthAdmin\Dto\User\UpdateUserDto;
use Concept\Core\Http\Requests\FormRequest;

/** @extends FormRequest<UpdateUserDto> */
class UpdateUserRequest extends FormRequest
{
    protected ?string $dtoClass = UpdateUserDto::class;

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:3', 'max:255'],
            'status' => ['required', 'in:' . implode(',', array_column(UserStatus::cases(), 'value'))],
            'is_admin' => ['boolean'],
            'acl_role_id' => ['nullable', 'integer', 'exists:acl_roles,id'],
            'verification_token' => ['nullable'],
            'password_reset_token' => ['nullable'],
            'reset_token_expires_at' => ['nullable', 'after:now'],
        ];
    }
}