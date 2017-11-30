<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

use DB;

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

    public function settings()
    {
        return $this->hasMany('App\SettingsModel')->orderBy('id');
    }

    /**
     * Redefine create method - add creating default settings
     */
    public static function create(array $attributes = [])
    {
        $user = parent::create($attributes);
        SettingsModel::createDefault($user->id);
        return $user;
    }

    /**
     * onlineTime sec.
     */
    protected static $onlineTime = 600;

    /**
     * Select all confirmed users
     */
    public static function getList()
    {
        $rawString = 'IF(type = \'bot\' OR NOW() - last_active_at <= '
            . self::$onlineTime . ', 1, 0) as online';
        return self::select('*', DB::raw($rawString))
            ->where(['confirmed' => 1])->get()->toArray();
    }
}
