<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challenges extends Model
{
	protected $table = 'challenges';

    protected $fillable = ['user_to'];

    /**
     * remove updated_at
     */
    public function setUpdatedAt($value)
	{
		// Do nothing.
	}

    /**
     * user to
     */
    public function userTo()
    {
        return $this->belongsTo('App\Models\User', 'user_to');
    }

    /**
     * user from
     */
    public function userFrom()
    {
        return $this->belongsTo('App\Models\User', 'user_from');
    }
}
