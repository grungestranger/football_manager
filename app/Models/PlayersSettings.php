<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayersSettings extends Model
{
	protected $table = 'players_settings';

    public $timestamps = false;

    protected $fillable = ['player_id', 'setting_id', 'text'];
}
