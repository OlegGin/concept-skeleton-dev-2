<?php declare(strict_types=1);

namespace Concept\App\Http\Requests;

use Concept\Extensions\FormRequest\Requests\FormRequest;

/**
 * @extends FormRequest<never>
 */
final class TestEchoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email',
        ];
    }
}
