<?php declare(strict_types=1);

namespace Concept\App\Http\Requests;

use Concept\App\Http\Dto\LoginDto;
use Concept\Extensions\FormRequest\Requests\FormRequest;

/**
 * @extends FormRequest<LoginDto>
 */
final class LoginRequest extends FormRequest
{
    protected ?string $dtoClass = LoginDto::class;

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'email:required' => 'Email is required.',
            'email:email' => 'Email must be valid.',
            'password:required' => 'Password is required.',
            'password:min' => 'Password must be at least 8 characters.',
        ];
    }
}
