<?php declare(strict_types=1);

namespace Concept\Components\Acl\Requests\Roles;

use Concept\Components\Acl\Dto\Role\RoleDto;
use Concept\Core\Http\Requests\FormRequest;

/** @extends FormRequest<RoleDto> */
class UpdateRoleRequest extends FormRequest
{
    protected ?string $dtoClass = RoleDto::class;

    public function rules(): array
    {
        $idParam = $this->getRouteParam('id', 0);
        $id = is_numeric($idParam) ? (int) $idParam : 0;

        return [
            'name' => ['required', 'min:2', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/', 'unique:acl_roles,name,' . $id . ',id'],
            'parent_id' => ['nullable', 'integer', 'exists:acl_roles,id', 'not_in:' . $id],
        ];
    }
}
