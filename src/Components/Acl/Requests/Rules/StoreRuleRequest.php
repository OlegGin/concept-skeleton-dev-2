<?php declare(strict_types=1);

namespace Concept\Components\Acl\Requests\Rules;

use Concept\Components\Acl\Dto\Rule\RuleDto;
use Concept\Components\Acl\Enums\AclPrivilege;
use Concept\Components\Acl\Enums\AclRuleType;
use Concept\Core\Http\Requests\FormRequest;

/** @extends FormRequest<RuleDto> */
class StoreRuleRequest extends FormRequest
{
    protected ?string $dtoClass = RuleDto::class;

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:' . implode(',', array_column(AclRuleType::cases(), 'value'))],
            'role_id' => ['nullable', 'integer', 'exists:acl_roles,id'],
            'resource_id' => ['nullable', 'integer', 'exists:acl_resources,id'],
            'privilege' => ['nullable', AclPrivilege::validationRule()],
        ];
    }
}
