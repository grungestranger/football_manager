<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
	protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = ['user_id', 'name', 'text'];

    /**
     * players' settings
     */
    public function playersSettings()
    {
        return $this->hasMany('App\Models\PlayersSettings', 'setting_id');
    }

    /**
     * create method default settings
     */
    public static function createDefault($user_id)
    {
        return self::create([
        	'user_id' => $user_id,
        	'name' => config('settings.defaultName'),
        	'text' => json_encode(config('settings.defaultSettings')),
        ]);
    }

    /**
     * Validate settings
     */
    public static function validateSettings($settings)
    {
        if (!is_array($settings)) {
            return FALSE;
        }
        foreach (config('settings.options') as $k => $v) {
            if (!isset($settings[$k]) || !in_array($settings[$k], $v)) {
                return FALSE;
            }
        }
        return TRUE;
    }
}
