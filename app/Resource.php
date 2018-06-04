<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * @property string password
 * @property string|null api_token
 * @property integer size
 * @property-read string identity
 * @property string internal_identity
 * @property-read string type_id
 * @property-read  string size_string
 * @property-read  string type_string
 * @property string name
 * @property integer|null user_id
 * @property string|Carbon deleted_at
 * @property string|Carbon created_at
 * @property string|Carbon updated_at
 * @property integer reviews_count
 * @property User user
 * @method static Builder where($column, $value)
 * @method static Builder|static withTrashed()
 * @method static Resource create($attributes)
 * @method static $this findOrFail($id)
 * @mixin Model
 */
class Resource extends Model
{
    use SoftDeletes;

    protected $appends = ['size_string', 'type_string'];

    protected $fillable = [
        'user_id',
        'type_id',
        'name',
        'size',
        'is_public',
        'is_private',
        'internal_identity',
        'identity',
        'reviews_count'
    ];

    protected $hidden = ['internal_identity'];

    protected $casts = [
        'is_public' => 'bool',
        'is_private' => 'bool',
        'is_delete' => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSizeStringAttribute()
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($this->size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function getTypeStringAttribute()
    {
        return Type::getById($this->type_id);
    }

    public function getIsDeletedAttribute()
    {
        return $this->deleted_at !== null;
    }

    public function getUserStringAttribute()
    {
        return optional($this->user)->username;
    }

    public function getStatusStringAttribute()
    {
        switch ($this->attributes['is_public'] . $this->attributes['is_private']) {
            case '11':
                return 'Для зареєстрованих';
            case '01':
                return 'Приватний';
            case '10':
                return 'Публічний';
            default:
                return null;
        }
    }
}
