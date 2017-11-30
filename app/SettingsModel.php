<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SettingsModel extends Model
{
	protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = ['user_id', 'name', 'text'];

    /**
     * tactics
     */
    public static $tactics = [
        'defence',
        'balance',
        'attack',
    ];

    /**
     * default name
     */
    protected static $defaultName = 'Настройки 1';

    /**
     * default settings
     */
    protected static $defaultSettings = [
    	'tactic' => 'balance',
    ];

    /**
     * create method default settings
     */
    public static function createDefault($user_id)
    {
        self::create([
        	'user_id' => $user_id,
        	'name' => self::$defaultName,
        	'text' => json_encode(self::$defaultSettings),
        ]);
    }
}
