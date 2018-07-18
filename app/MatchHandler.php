<?php

namespace App;

use App\Models\User;
use App\Models\Player;
use App\Models\Match as MatchModel;
use App\Jobs\Match as MatchJob;
use Carbon\Carbon;
use Cache;
use Predis;
use App\Match;

class MatchHandler {

	protected $match;

	protected $matchModel;

    protected $cacheTime;

    protected $time;

    public function __construct(MatchModel $matchModel)
    {
        $this->matchModel = $matchModel;
        $this->cacheTime = config('match.cache_time');
        $this->time = $this->getTime();
    }

    public function create()
    {
    	dispatch((new MatchJob($this->matchModel))
    		->delay(config('match.preparation_time')));

    	$data = (object)[
    		'id' => $this->matchModel->id,
    		'time' => 0,
    		'teams' => [],
            'actions' => NULL,
    	];

    	foreach ([1, 2] as $v) {
    		$user = $this->matchModel->{'user' . $v};

			$settings = $user->setting;
			$players = Player::getTeam($settings->id);

    		$data->teams[$user->id] = (object)[
    			'settings' => json_decode($settings->text),
                'settings_id' => $settings->id,
    			'players' => $this->convertPlayers($players),
    		];
    	}

    	Cache::put('match:' . $this->matchModel->id, $data, $this->cacheTime);
    }

    protected function convertPlayers(array $players)
    {
    	$res = [];

    	foreach ($players as $v) {
    		$player = (object)[
    			'id' => $v->id,
                'user_id' => $v->user_id,
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
        $matchData = $this->getMatchData();

        $la = $matchData->actions ? end($matchData->actions)[0] : NULL;

        $data = [
            'user1_id' => $this->matchModel->user1_id,
            'field' => config('match.field'),
        ];

        $teams = json_decode(json_encode($matchData->teams), TRUE);

        $match = new Match($data, $teams, $la, $matchData->time);
        $action = $match->getAction();
        //$stats = $match->getStats();

        return $action;

/*
        if ($this->match->user1->type == 'man') {
            Predis::publish('user:' . $this->match->user1_id, json_encode($action));
        }
        if ($this->match->user1->type == 'man') {
            Predis::publish('user:' . $this->match->user1_id, json_encode($action));
        }
    /*

        if ($user2->type == 'man') {
                Predis::publish('user:' . $user2->id, json_encode([

                ]));
            }

        $job = (new static($this->match))->delay(10);

        dispatch($job);*/
    }

    public function getTime()
    {
    	return $this->time !== NULL ? $this->time
            : Carbon::now()->timestamp
            - Carbon::parse($this->matchModel->created_at)->timestamp
            - config('match.preparation_time');
    }

    public function getAction()
    {
        return NULL;
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
    	$team = $this->getMatchData()->teams[$user->id];

    	$team->settings = (object)[
            'id' => $team->settings_id,
            'settings' => $team->settings,
        ];

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

    	$team->players = Player::sortPlayers($players);

    	return $team;
    }

    public function saveTeam(User $user, array $players, array $settings, $settings_id)
    {
        $data = $this->getMatchData();

        $team = $data->teams[$user->id];

        foreach ($team->players as $v) {
            $v->settings = (object)$players[$v->id];
        }

        $team->settings = (object)$settings;
        $team->settings_id = $settings_id;

        Cache::put('match:' . $this->matchModel->id, $data, $this->cacheTime);
    }

}
