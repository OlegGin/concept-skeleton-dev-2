<?php declare(strict_types=1);

namespace Concept\Common\Validation\Rules;

use Concept\Core\Services\Database\Contracts\DatabaseInterface;
use Concept\Core\Services\Validator\Contracts\RuleInterface;
use Concept\Core\Services\Validator\Rule;

/**
 * Database uniqueness validation.
 *
 * Global unique:
 *   unique:users,email
 *   unique:users,email,{id}
 *
 * Scoped unique (e.g. unique within a group/tenant/category):
 *   unique:settings,setting_key,NULL,NULL,setting_group,general
 *   unique:settings,setting_key,{id},id,setting_group,general
 */
class UniqueRule extends Rule implements RuleInterface
{
    protected string $message = ':attribute :value has been used';

    /** @var array<int, string> */
    protected array $fillable = ['table', 'column', 'except', 'id_column', 'scope_column', 'scope', 'without_trashed'];

    protected array $required = ['table', 'column'];

    public function __construct(private readonly DatabaseInterface $db) {}

    public function passes($value): bool
    {
        /** @var string $column */
        $column = $this->parameter('column');
        /** @var string $table */
        $table = $this->parameter('table');
        /** @var string|null $except */
        $except = $this->parameter('except');

        $query = $this->db->capsule()
            ->connection()
            ->table($table)
            ->where($column, '=', $value);

        $scopeColumn = $this->parameter('scope_column');
        if (is_string($scopeColumn) && $scopeColumn !== '') {
            $query->where($scopeColumn, '=', $this->parameter('scope'));
        }

        if ($except && $except !== 'NULL') {
            /** @var string $idColumn */
            $idColumn = $this->parameter('id_column') ?? 'id';

            $query->where($idColumn, '<>', $except);
        }

        $withoutTrashed = $this->parameter('without_trashed');
        $shouldFilterTrashed = $withoutTrashed === null || ($withoutTrashed !== 'false' && $withoutTrashed !== false);

        if ($shouldFilterTrashed && $this->db->capsule()::schema()->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->count() === 0;
    }
}
