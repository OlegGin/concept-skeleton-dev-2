<?php declare(strict_types=1);

namespace Concept\Components\Acl\Requests\Resources;

use Concept\Components\Acl\Dto\Resource\ResourceDto;
use Concept\Extensions\FormRequest\Requests\FormRequest;

/** @extends FormRequest<ResourceDto> */
class StoreResourceRequest extends FormRequest
{
    protected ?string $dtoClass = ResourceDto::class;

    public function rules(): array
    {
        return [
            'name' => ['required', 'min:2', 'max:150', 'regex:/^[a-z][a-z0-9._-]*$/', 'unique:acl_resources,name'],
            'parent_id' => ['nullable', 'integer', 'exists:acl_resources,id'],
        ];
    }
}
