<?php declare(strict_types=1);

namespace Concept\testApp\jsonDbApp\src\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string|null $created_at
 * @property string|null $updated_at
 */
final class Item extends Model
{
    protected $table = 'jsondb_items';

    /** @var list<string> */
    protected $fillable = [
        'title',
    ];
}
