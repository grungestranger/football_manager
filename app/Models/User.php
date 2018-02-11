<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'confirmed', 'type', 'name', 'email', 'password',
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
     * Settings
     */
    public function settings()
    {
        return $this->hasMany('App\Models\Settings')->orderBy('id');
    }

    /**
     * the challenges I sent
     */
    public function challengesFrom()
    {
        return $this->hasMany('App\Models\Challenges', 'user_from')
            ->orderBy('created_at', 'desc')
            ->with('userTo');
    }

    /**
     * the challenges sent to me
     */
    public function challengesTo()
    {
        return $this->hasMany('App\Models\Challenges', 'user_to')
            ->orderBy('created_at', 'desc')
            ->with('userFrom');
    }

    /**
     * Select all confirmed users
     */
    public static function getList()
    {
        return self::where(['confirmed' => 1])->get();
    }

    /**
     * Find confirmed users
     */
    public static function findConfirmed($data)
    {
        return self::where(['confirmed' => 1])->find($data);
    }
}
