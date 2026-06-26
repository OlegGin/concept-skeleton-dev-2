<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Requests\Users;

use Concept\Components\AuthAdmin\Dto\User\UpdateUserPasswordDto;
use Concept\Extensions\FormRequest\Requests\FormRequest;

/** @extends FormRequest<UpdateUserPasswordDto> */
class UpdateUserPasswordRequest extends FormRequest
{
    protected ?string $dtoClass = UpdateUserPasswordDto::class;

    protected array $except = [
        'password_confirmation',
    ];

    public function rules(): array
    {
        return [
            'password' => ['required', 'min:8'],
            'password_confirmation' => ['required', 'min:8', 'same:password'],
        ];
    }
}
