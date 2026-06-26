<?php declare(strict_types=1);

namespace Concept\Components\Acl\Requests\Matrix;

use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Dto\ValidatedArrayDto;
use Concept\Core\Http\Requests\FormRequest;

/** @extends FormRequest<ValidatedArrayDto> */
class UpdateMatrixAccessRequest extends FormRequest
{
    protected ?string $dtoClass = null;

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:acl_roles,id'],
            'resource_id' => ['required', 'integer', 'exists:acl_resources,id'],
            'action' => ['required', 'in:allow,deny,inherit'],
            'privilege' => ['nullable', AclPrivilege::validationRule()],
        ];
    }
}
