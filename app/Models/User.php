<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
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
        return $this->hasMany('App\Models\Challenge', 'user_from')
            ->orderBy('created_at', 'desc')
            ->with('userTo');
    }

    /**
     * the challenges sent to me
     */
    public function challengesTo()
    {
        return $this->hasMany('App\Models\Challenge', 'user_to')
            ->orderBy('created_at', 'desc')
            ->with('userFrom');
    }

    /**
     * 
     */
    public function match1()
    {
        return $this->hasMany('App\Models\Match', 'user1_id')
            ->whereNull('result');
    }

    /**
     * 
     */
    public function match2()
    {
        return $this->hasMany('App\Models\Match', 'user2_id')
            ->whereNull('result');
    }

    /**
     * 
     */
    public function getMatchAttribute()
    {
        return $this->match1->count() ? $this->match1->first()->id : (
            $this->match2->count() ? $this->match2->first()->id : NULL
        );
    }

    /**
     * Select all confirmed users
     */
    public static function getList()
    {
        return self::where(['confirmed' => 1])
            ->with(['match1', 'match2'])->get();
    }

    /**
     * Find confirmed users
     */
    public static function findConfirmed($data)
    {
        return self::where(['confirmed' => 1])->find($data);
    }
}
