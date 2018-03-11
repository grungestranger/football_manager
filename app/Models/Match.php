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
}