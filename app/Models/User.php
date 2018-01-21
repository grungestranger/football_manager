<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

use DB;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'confirmed', 'type', 'name', 'email', 'password', 'remember_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * onlineTime sec.
     */
    protected static $onlineTime = 600;

    /**
     * Settings
     */
    public function settings()
    {
        return $this->hasMany('App\Models\Settings')->orderBy('id');
    }

    /**
     * Select all confirmed users
     */
    public static function getList()
    {
        return self::where(['confirmed' => 1])->get();
    }

    /**
     * Override method boot, to add scope.
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('online', function (Builder $builder) {
            $rawString = 'IF(type = \'bot\' OR NOW() - last_active_at <= '
                . self::$onlineTime . ', 1, 0) as online';
            $builder->select('*', DB::raw($rawString));
        });
    }
}
