<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayersSettingsModel extends Model
{
	protected $table = 'players_settings';

    public $timestamps = false;

    protected $fillable = ['player_id', 'setting_id', 'text'];
}
