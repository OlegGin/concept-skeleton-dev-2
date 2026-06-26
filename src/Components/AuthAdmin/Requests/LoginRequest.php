<?php declare(strict_types=1);

namespace Concept\Components\AuthAdmin\Requests;

use Concept\Components\AuthAdmin\Dto\LoginDto;
use Concept\Core\Http\Requests\FormRequest;

/** @extends FormRequest<LoginDto> */
class LoginRequest extends FormRequest
{
    protected ?string $dtoClass = LoginDto::class;

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }
}