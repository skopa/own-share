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
 * @method static Builder where($column, $value)
 * @method static Builder|static withTrashed()
 * @method static Resource create($attributes)
 * @method static $this findOrFail($id)
 * @mixin Model
 */
class File extends Model
{
    //use SoftDeletes;

    public function getSizeAttribute()
    {
        $s = explode(' ', $this->size_string);
        switch ($s[1]) {
            case 'B':
                return $s[0];
            case 'KB':
                return intval($s[0] * 1024);
            case 'MB':
                return intval($s[0] * 1024 * 1024);
            default:
                return 0;
        }
    }

    public function getExtAttribute()
    {
        $e = explode('.', $this->name);
        $c = count($e);
        return $c == 1 ? '' : $e[$c - 1];
    }
}
