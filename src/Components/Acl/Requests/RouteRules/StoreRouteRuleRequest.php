<?php declare(strict_types=1);

namespace Concept\Components\Acl\Requests\RouteRules;

use Concept\Components\Acl\Dto\RouteRule\RouteRuleDto;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Extensions\FormRequest\Requests\FormRequest;

/** @extends FormRequest<RouteRuleDto> */
class StoreRouteRuleRequest extends FormRequest
{
    protected ?string $dtoClass = RouteRuleDto::class;

    public function rules(): array
    {
        return [
            'route_name' => ['required', 'min:2', 'max:150', 'regex:/^[a-z][a-z0-9._-]*$/', 'unique:acl_route_rules,route_name'],
            'resource_id' => ['required', 'integer', 'exists:acl_resources,id'],
            'privilege' => ['nullable', AclPrivilege::validationRule()],
            'redirect_route_name' => ['nullable', 'max:150', 'regex:/^[a-z][a-z0-9._-]*$/'],
        ];
    }
}
