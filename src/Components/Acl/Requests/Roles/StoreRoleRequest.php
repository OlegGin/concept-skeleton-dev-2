<?php declare(strict_types=1);

namespace Concept\Components\Acl\Requests\Roles;

use Concept\Components\Acl\Dto\Role\RoleDto;
use Concept\Extensions\FormRequest\Requests\FormRequest;

/** @extends FormRequest<RoleDto> */
class StoreRoleRequest extends FormRequest
{
    protected ?string $dtoClass = RoleDto::class;

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/', 'unique:acl_roles,name'],
            'parent_id' => ['nullable', 'integer', 'exists:acl_roles,id'],
        ];
    }
}
