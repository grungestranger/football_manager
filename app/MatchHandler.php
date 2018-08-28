<?php

namespace App;

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
            'prevValues' => NULL,
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

    // For create
    protected function convertPlayers(array $players)
    {
    	$res = [];

    	foreach ($players as $v) {
            $stats = (object)[
                'in_time' => $v->settings->position ? 0 : NULL,
                'out_time' => NULL,
                'goals_count' => 0,
                'goals_time' => [],
                'yellow_cards_count' => 0,
                'yellow_cards_time' => [],
                'red_card_time' => NULL,
            ];

            $tmp = ['id', 'name', 'user_id', 'settings', 'roles'];

            $player = new \stdClass();

            foreach ($tmp as $item) {
                $player->{$item} = $v->{$item};
                unset($v->{$item});
            }

            $player->stats = $stats;
            $player->prevStats = NULL;
    		$player->skills = $v;

    		$res[$player->id] = $player;
    	}

    	return $res;
    }

    public function exec()
    {
        $data = $this->getMatchData();

        $values = $data->actions ? end($data->actions['motions'])[1] : NULL;

        $data1 = [
            'user1_id' => $this->matchModel->user1_id,
            'field' => config('match.field'),
        ];

        $teams = json_decode(json_encode($data->teams), TRUE);

        $match = new Match($data1, $teams, $values, $data->time);
        $data->actions = $match->getAction();

        $stats = $match->getStats();
        foreach ($data->teams as $team) {
            foreach ($team->players as $player) {
                $player->prevStats = $player->stats;
                $player->stats = (object)$stats[$player->id];
            }
        }

        return $data->actions;

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

    public function getData(int $user_id)
    {
        $data = $this->getMatchData();

        $userTeam = $data->teams[$user_id];

        $settings = (object)[
            'id' => $userTeam->settings_id,
            'settings' => $userTeam->settings,
        ];

        if ($data->actions) {
            $prevValues = $data->prevValues ?: $data->actions['motions'][0][1];

            $motions = $data->actions['motions'];
            $events = $data->actions['events'];

            $t = 0;
            foreach ($motions as $item) {
                $t += $item[0];
            }
            $dt = $this->time - $data->time + $t;

            // motions

            $t = 0;
            foreach ($motions as $k => $v) {
                $t += $v[0];
                if ($t > $dt) {
                    $key = $k;
                    break;
                }
            }

            $startMs = $t - $v[0];

            if ($key) {
                foreach ($motions[$key - 1] as $k => $v) {
                    $prevValues[$k] = $v;
                }
                $motions = array_slice($motions, $key);
            }

            array_unshift($motions, [0, $prevValues]);

            // events

            $t = 0;
            foreach ($events as $k => $v) {
                $t += $v[0];
                if ($t > $startMs) {
                    $key = $k;
                    break;
                }
                if (isset($v[1]['stats'])) {
                    foreach ($v[1]['stats'] as $player_id => $value) {
                        foreach ($data->teams as $team) {
                            if (isset($team->players[$player_id])) {
                                $team->players[$player_id]->prevStats = (object)array_merge(
                                    (array)$team->players[$player_id]->prevStats,
                                    $value
                                );
                                break;
                            }
                        }
                    }
                }
            }

            foreach ($data->teams as $team) {
                foreach ($team->players as $player) {
                    $player->stats = $player->prevStats;
                }
            }
        }
    }

    protected function getTime()
    {
    	return Carbon::now()->timestamp
            - Carbon::parse($this->matchModel->created_at)->timestamp
            - config('match.preparation_time');
    }

    public function getAction()
    {
        return NULL;
    }

    public function getTeams()
    {
        return $this->getMatchData()->teams;
    }

    protected function getMatchData()
    {
        if(!($data = Cache::get('match:' . $this->matchModel->id))) {
            throw new \Exception('Match data do not exists');
        }
    	return $data;
    }

    public function getTeam(int $user_id)
    {
    	$team = $this->getMatchData()->teams[$user_id];

    	$team->settings = (object)[
            'id' => $team->settings_id,
            'settings' => $team->settings,
        ];

    	$players = [];

    	foreach ($team->players as $v) {
            $player = (object)array_merge((array)$v, (array)$v->skills);
            unset($player->skills);

    		$players[] = $player;
    	}

    	$team->players = Player::sortPlayers($players);

    	return $team;
    }

    public function saveTeam(int $user_id, array $players, array $settings, $settings_id)
    {
        $data = $this->getMatchData();

        $team = $data->teams[$user_id];

        foreach ($team->players as $v) {
            $v->settings = (object)$players[$v->id];
        }

        $team->settings = (object)$settings;
        $team->settings_id = $settings_id;

        // TODO to stats

        Cache::put('match:' . $this->matchModel->id, $data, $this->cacheTime);
    }

}
