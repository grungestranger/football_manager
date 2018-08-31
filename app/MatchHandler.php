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

    public function __construct(MatchModel $match)
    {
        $this->match = $match;
    }

    public function create()
    {
    	$data = (object)[
    		'time' => 0,
            'prevTime' => 0,
    		'teams' => [],
            'actions' => NULL,
            'values' => NULL,
            'prevValues' => NULL,
    	];

    	foreach ([1, 2] as $v) {
    		$user = $this->match->{'user' . $v};

			$settings = $user->setting;
			$players = Player::getTeam($settings->id);

    		$data->teams[$user->id] = (object)[
    			'settings' => json_decode($settings->text),
                'settings_id' => $settings->id,
    			'players' => $this->convertPlayers($players),
    		];
    	}

    	Cache::put('match:' . $this->match->id, $data, config('match.cache_time'));

        dispatch(
            (new MatchJob($this->match))
                ->delay(config('match.preparation_time'))
        );
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
            $player->prevStats = $stats;
    		$player->skills = $v;

    		$res[$player->id] = $player;
    	}

    	return $res;
    }

    public function exec()
    {
        $data = $this->getMatchData();

        $data1 = [
            'user1_id' => $this->match->user1_id,
            'field' => config('match.field'),
        ];

        $teams = json_decode(json_encode($data->teams), TRUE);

        $match = new Match($data1, $teams, $data->values, $data->time);

        $data->actions = $match->getActions();

        $data->prevTime = $data->time;
        $data->time = $match->getTime();

        $stats = $match->getStats();
        foreach ($data->teams as $team) {
            foreach ($team->players as $player) {
                $player->prevStats = $player->stats;
                $player->stats = (object)$stats[$player->id];
            }
        }

        $data->prevValues = $data->values;
        $data->values = $match->getValues();

        Cache::put('match:' . $this->match->id, $data, config('match.cache_time'));

        foreach ([1, 2] as $v) {
            $user = $this->match->{'user' . $v};

            if ($user->type == 'man' && $user->online) {
                Predis::publish('user:' . $user->id, json_encode([
                    'action' => 'matchActions',
                    'actions' => $data->actions,
                ]));
            }
        }

        // TODO if match not ended ...
        dispatch(
            (new MatchJob($this->match))
                ->delay(floor(($data->time - $data->prevTime) / 1000))
        );
    }

    public function getData(int $user_id)
    {
        $time = $this->getTime();
        $data = $this->getMatchData();

        if ($data->actions) {
            $motions = &$data->actions['motions'];
            $events = &$data->actions['events'];

            // dt

            $dt = $time * 1000 - $data->prevTime;

            // motions

            $prevValues = $data->prevValues ?: $motions[0][1];

            $t = 0;
            foreach ($motions as $k => $v) {
                $t += $v[0];
                if ($t > $dt) {
                    $key = $k;
                    break;
                }
                foreach ($v[1] as $k1 => $v1) {
                    $prevValues[$k1] = $v1;
                }
            }

            $startMs = $t - $v[0];

            $motions = array_slice($motions, $key);

            array_unshift($motions, [0, $prevValues]);

            // events/stats

            $t = 0;
            foreach ($events as $k => $v) {
                $t += $v[0];
                if ($t > $startMs) {
                    $key = $k;
                    break;
                }
                if (isset($v[1]['stats'])) {
                    foreach ($v[1]['stats'] as $player_id => $stats) {
                        foreach ($data->teams as $team) {
                            if (isset($team->players[$player_id])) {
                                $team->players[$player_id]->prevStats = (object)array_merge(
                                    (array)$team->players[$player_id]->prevStats,
                                    $stats
                                );
                                break;
                            }
                        }
                    }
                }
            }

            $events = array_slice($events, $key);

            foreach ($data->teams as $team) {
                foreach ($team->players as $player) {
                    $player->stats = $player->prevStats;
                }
            }
        }

        $team = $data->teams[$user_id];

        // settings

        $settings = (object)[
            'id' => $team->settings_id,
            'settings' => $team->settings,
        ];

        // players

        $players = [];

        foreach ($team->players as $v) {
            $player = (object)array_merge((array)$v, (array)$v->skills);
            unset($player->skills);

            $players[] = $player;
        }

        $players = Player::sortPlayers($players);

        return (object)[
            'time' => $time,
            'teams' => $data->teams,
            'actions' => $data->actions,
            'settings' => $settings,
            'players' => $players,
        ];
    }

    // sec
    protected function getTime()
    {
    	return Carbon::now()->timestamp
            - Carbon::parse($this->match->created_at)->timestamp
            - config('match.preparation_time');
    }

    protected function getMatchData()
    {
        if(!($data = Cache::get('match:' . $this->match->id))) {
            throw new \Exception('Match data do not exists');
        }
    	return $data;
    }

    public function saveTeam(int $user_id, array $playersSettings, array $settings, $settings_id)
    {
        $data = $this->getMatchData();

        $team = $data->teams[$user_id];

        foreach ($team->players as $v) {
            $pSet = (object)$playersSettings[$v->id];

            // in/out to stats
            // TODO add match events (in/out) to player goes (away from field / on field)
            if ($v->settings->position !== NULL && $pSet->position === NULL) {
                if ($data->time) { // match already start
                    $v->stats->out_time = $data->time;
                } else {
                    $v->stats->in_time = NULL;
                }
            } elseif ($v->settings->position === NULL && $pSet->position !== NULL) {
                $v->stats->in_time = $data->time;
            }

            $v->settings = $pSet;
        }

        $team->settings = (object)$settings;
        $team->settings_id = $settings_id;

        Cache::put('match:' . $this->match->id, $data, config('match.cache_time'));
    }

}
