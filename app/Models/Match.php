<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Match extends Model
{
	protected $table = 'matches';

	protected $fillable = ['user2_id'];

    /**
     * remove updated_at
     */
    public function setUpdatedAt($value)
	{
		// Do nothing.
	}

    /**
     *
     */
    public function user1()
    {
        return $this->belongsTo('App\Models\User', 'user1_id');
    }

    /**
     *
     */
    public function user2()
    {
        return $this->belongsTo('App\Models\User', 'user2_id');
    }
}