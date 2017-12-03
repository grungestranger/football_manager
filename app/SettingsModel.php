<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SettingsModel extends Model
{
	protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = ['user_id', 'name', 'text'];

    /**
     * options
     */
    protected static $options = [
        'tactic' => [
            'defence',
            'balance',
            'attack',
        ],
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
     * get options
     */
    public static function getOptions()
    {
        return self::$options;
    }

    /**
     * create method default settings
     */
    public static function createDefault($user_id)
    {
        return self::create([
        	'user_id' => $user_id,
        	'name' => self::$defaultName,
        	'text' => json_encode(self::$defaultSettings),
        ]);
    }

    /**
     * Validate settings
     */
    public static function validateSettings(stdClass $settings)
    {
    }
}
