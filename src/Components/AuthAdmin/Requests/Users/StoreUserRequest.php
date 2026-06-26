<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Requests\Users;

use Concept\Components\AuthAdmin\Enums\UserStatus;
use Concept\Components\AuthAdmin\Dto\User\StoreUserDto;
use Concept\Core\Http\Requests\FormRequest;

/** @extends FormRequest<StoreUserDto> */
class StoreUserRequest extends FormRequest
{
    protected ?string $dtoClass = StoreUserDto::class;

    protected array $except = [
        'password_confirmation',
    ];

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:3'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'password_confirmation' => ['required', 'min:8', 'same:password'],
            'status' => ['required', 'in:' . implode(',', array_column(UserStatus::cases(), 'value'))],
            'is_admin' => ['nullable', 'boolean'],
            'acl_role_id' => ['nullable', 'integer', 'exists:acl_roles,id'],
            'verification_token' => ['nullable'],
            'password_reset_token' => ['nullable'],
            'reset_token_expires_at' => ['nullable', 'after:now'],
        ];
    }
}