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
            'prevTime' => Carbon::now()->timestamp + config('match.preparation_time'),
            'teams' => [],
            'actions' => NULL,
            'values' => NULL,
            'prevValues' => NULL,
    	];

    	foreach ([1, 2] as $v) {
    		$user = $this->match->{'user' . $v};

			$settings = $user->setting;
			$players = Player::getTeam($settings->id);

            $playersSettings = $playersData = [];

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

                $playersSettings[$v->id] = $v->settings;
                unset($v->settings);

                $player = new \stdClass();

                foreach (['id', 'name', 'user_id', 'roles'] as $item) {
                    $player->{$item} = $v->{$item};
                    unset($v->{$item});
                }

                $player->stats = $stats;
                $player->prevStats = clone $stats;
                $player->skills = $v;

                $playersData[$player->id] = $player;
            }

            $this->saveTeam($user->id, $playersSettings, json_decode($settings->text), $settings->id);

    		$data->teams[$user->id] = (object)[
    			'players' => $playersData,
    		];
    	}

    	Cache::put('match:' . $this->match->id, $data, config('match.cache_time'));

        dispatch(
            (new MatchJob($this->match))
                ->delay(config('match.preparation_time'))
        );
        \Log::info('create');
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

        $data->prevTime = Carbon::now()->timestamp;
        $data->time = $match->getTime();

        $stats = $match->getStats();
        foreach ($data->teams as $team) {
            unset($team->settings, $team->settings_id);
            foreach ($team->players as $player) {
                unset($player->settings);
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
                ->delay(10)
                //->delay(floor(($data->time - $data->prevTime) / 1000))
        );
        \Log::info('exec');
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

        foreach ($data->teams as $user_id => $team) {
            if(!($teamData = Cache::get('match:' . $this->match->id . 'team:' . $user_id))) {
                throw new \Exception('Match data do not exists');
            }
            $team->settings = $teamData->settings;
            $team->settings_id = $teamData->settings_id;
            foreach ($team->players as $player) {
                $player->settings = $teamData->playersSettings->{$player->id};
            }
        }

    	return $data;
    }

    public function saveTeam(int $user_id, array $playersSettings, $settings, $settings_id)
    {
        $team = (object)[
            'playersSettings' => json_decode(json_encode($playersSettings)),
            'settings' => (object)$settings,
            'settings_id' => $settings_id,
        ];

        Cache::put('match:' . $this->match->id . 'team:' . $user_id, $team, config('match.cache_time'));
    }

}
