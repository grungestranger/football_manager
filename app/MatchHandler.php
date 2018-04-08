<?php

namespace App;

use App\Models\User;
use App\Models\Player;
use App\Models\Match as MatchModel;
use App\Jobs\Match as MatchJob;
use Carbon\Carbon;
use Cache;
use Predis;

class MatchHandler {

	protected $match;

	protected $matchModel;

    public function __construct(MatchModel $matchModel)
    {
        $this->matchModel = $matchModel;
    }

    public function create()
    {
    	dispatch((new MatchJob($this->matchModel))
    		->delay(config('match.preparation_time')));

    	$data = (object)[
    		'id' => $this->matchModel->id,
    		'time' => 0,
    		'teams' => [],
    	];

    	foreach ([1, 2] as $v) {
    		$user = $this->matchModel->{'user' . $v};

			$settings = $user->setting;
			$players = Player::getTeam($settings->id);

    		$data->teams[] = (object)[
    			'id' => $user->id,
    			'settings' => json_decode($settings->text),
    			'players' => $this->convertPlayers($players),
    		];
    	}

    	Cache::put('match:' . $this->matchModel->id, $data, 20);
    }

    protected function convertPlayers(array $players)
    {
    	$res = [];

    	foreach ($players as $v) {
    		$player = (object)[
    			'id' => $v->id,
    			'settings' => $v->settings,
    			'roles' => $v->roles,
    			'stats' => NULL,
    		];

    		unset($v->id, $v->settings, $v->roles, $v->name, $v->user_id);

    		$player->skills = $v;

    		$res[] = $player;
    	}

    	return $res;
    }

    public function exec()
    {

    }

    public function isStart()
    {
    	return Carbon::parse($this->matchModel->created_at)->timestamp
            - Carbon::now()->timestamp
            >= config('match.preparation_time');
    }

    protected function getMatchData()
    {
    	if (!($data = Cache::get('match:' . $this->matchModel->id))) {
    		throw new \Exception('Match data not exists');
    	}
    	return $data;
    }

    public function getTeam(User $user)
    {
    	$data = $this->getMatchData();

    	foreach ($data->teams as $v) {
    		if ($v->id == $user->id) {
    			$team = $v;
    			break;
    		}
    	}

    	if (!isset($team)) {
    		throw new \Exception('Wrong user');
    	}

    	$team->settings = (object)['settings' => $team->settings];

    	$players = [];

    	foreach ($team->players as $v) {
    		$player = $v->skills;
    		$player->roles = $v->roles;
    		$player->settings = $v->settings;
    		$player->id = $v->id;
    		$player->user_id = $user->id;
    		if (!($userPlayer = $user->players->where('id', $v->id)->first())) {
    			throw new \Exception('Player not exists');
    		}
    		$player->name = $userPlayer->name;

    		$players[] = $player;
    	}

    	$team->players = $players;

    	return $team;
    }

}
