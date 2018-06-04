<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string password
 * @property string|null api_token
 * @property integer size
 * @property-read  integer size_string
 * @method static \Illuminate\Database\Eloquent\Builder where($column, $value)
 * @method static \Illuminate\Database\Eloquent\Builder withTrashed()
 * @method static User create($attributes)
 */
class Type
{
    protected static $map = [
        1 => 'f',
        2 => 'l'
    ];

    protected static $mapO = [
        'f' => 1,
        'l' => 2,
    ];

    public static $FILE = 1;
    public static $LINK = 2;

    public static function getByChar($char)
    {
        return static::$mapO[$char];
    }

    public static function getById($id)
    {
        return static::$map[$id];
    }
}
