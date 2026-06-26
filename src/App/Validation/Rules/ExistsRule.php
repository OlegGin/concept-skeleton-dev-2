<?php declare(strict_types=1);

namespace Concept\Common\Validation\Rules;

use Concept\Core\Services\Database\Contracts\DatabaseInterface;
use Concept\Core\Services\Validator\Contracts\RuleInterface;
use Concept\Core\Services\Validator\Rule;

class ExistsRule extends Rule implements RuleInterface
{
    /** @var string */
    protected string $message = ':attribute :value has been not found';

    /** @var array<int, string> */
    protected array $fillable = ['table', 'column', 'except', 'id_column'];

    protected array $required = ['table', 'column'];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function passes($value): bool
    {
        /** @var string $column */
        $column = $this->parameter('column');
        /** @var string $table */
        $table = $this->parameter('table');
        /** @var string $except */
        $except = $this->parameter('except');

        $query = $this->db->capsule()
            ->connection()
            ->table($table)
            ->where($column, '=', $value);

        if ($except && $except !== 'NULL') {
            /** @var string $idColumn */
            $idColumn = $this->parameter('id_column') ?? 'id';

            $query->where($idColumn, '<>', $except);
        }

        if ($this->db->capsule()::schema()->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->count() > 0;
    }
}