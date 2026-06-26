<?php declare(strict_types=1);

namespace Concept\App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public const string FIELD_ID = 'id';
    public const string SORT_ASC = 'asc';
    public const string SORT_DESC = 'desc';

    public function getId(): int
    {
        return (int)$this->getAttribute(self::FIELD_ID);
    }
}