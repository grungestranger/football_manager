<?php

namespace App;

use Predis;
use App\Models\User;

class SocketHandler {

	public static function work($data)
	{
		if (
			is_object($data = json_decode($data))
			&& isset($data->action)
			&& is_string($action = $data->action)
			&& method_exists(__CLASS__, $action)
		) {
			self::$action($data);
		}
	}

	protected static function userConnect($data)
	{
		if (!empty($data->id) && is_int($data->id)) {
			// TODO block table
			$user = User::find($data->id);
			// TODO unblock table
		}
	}

	protected static function userDisconnect($data)
	{
		//Predis::publish('user:' . $data->id, 'confirmed');
		$redis = Predis::connection();
		$redis->publish('user:' . $data->id, 'confirmed');
	}

	protected static function startServer($data)
	{

	}
}
