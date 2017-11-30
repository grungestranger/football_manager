<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StatsModel extends Model
{
	protected $table = 'stats';

    public $timestamps = false;

	/*public function player()
	{
		return $this->belongsTo('App\PlayerModel');
	}*/
}