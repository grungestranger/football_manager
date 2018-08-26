<?php

namespace App;

use App\Match;
use App\Math;

class Player {

 	// Match
 	protected $match;

 	// Математические функции
 	protected $math;

 	// MatchData
 	protected $matchData;

 	// ID
 	protected $id;
 
 	// Сторона (команда)
 	protected $side;
 
 	// Установки игрока
 	protected $settings;

 	// Позиция (координаты)
 	protected $pos;

	// Характеристики игрока
	protected $skills;

	//
	protected $roles;

	//
	protected $stats;

	// Значения во время матча
	protected $val;

	protected $valArr = [];

	// Массив - координаты точки, куда двигаться, и скорость.
	protected $target;


	public function __construct(array $data, Match $match, Math $math)
	{
		$this->match = $match;
		$this->math = $math;
		$this->matchData = $match->getData();
		$this->id = $data['id'];
		$this->settings = $data['settings'];
		$this->skills = $data['skills'];
		$this->stats = $data['stats'];
		$this->val = $this->getVal();

		// side - TODO firs, second time
		if ($data['user_id'] == $this->matchData['user1_id']) {
			$this->side = 'l';
		} else {
			$this->side = 'r';
		}

		// current position
		if ($this->isOnField()) {
			$this->pos = $this->settings['position'];
			if ($this->side == 'r') {
				$this->pos['x'] = $this->matchData['field']['width'] - $this->pos['x'];
				$this->pos['y'] = $this->matchData['field']['height'] - $this->pos['y'];
			}
		}
	}

	public function setStopVal($ms)
	{
		$this->val = isset($this->valArr[$ms]) ? $this->valArr[$ms] : end($this->valArr);
		return $this->val;
	}

	public function getVal()
	{
		return $this->match->getPlayerVal($this->id);
	}

	public function isOnField()
	{
		return $this->stats['in_time'] !== NULL && $this->stats['out_time'] === NULL
			&& $this->stats['red_card_time'] === NULL;
	}

	public function do_action()
	{
		if (!$this->isOnField() && !$this->match->isEvent('first_half')) {
			return NULL;
		}

		if ($event = $this->selectEvent()) {
			$this->go_to_event($event);
		} else {
			$this->logic();
			$this->go_to();
		}

		return $this->val;
	}

	protected function selectEvent()
	{
		if ($this->match->isEvent('first_half')) {
			return ['name' => 'first_half'];
		}
		return NULL;
	}

	protected function go_to()
	{
		$this->valArr = []; // Очищаем от предыдущего

		$ms_min = $this->match->getMs_min();
		$ms_max = $this->match->getMs_max();
		$dt = $this->match->getDt();
		$stop = $this->match->getStop();

		if ($stop) {
			$time = min($stop);
		} else {
			$time = $ms_max;
		}

		$x = $this->val['x'];
		$y = $this->val['y'];
		$s = $this->val['s'];
		$d = $this->val['d'];
		$speed = $this->skills['speed'];
		$acceleration = $this->skills['acceleration'];
		$coordination = $this->skills['coordination'];

		$maxDist = $ms_max * $speed / 1000;

		if ($this->target) {
			$tx = $this->target['x'];
			$ty = $this->target['y'];
			$ts = isset($this->target['s']) ? $this->target['s'] : NULL;
			$this->target = NULL;
		} else {
			$point = $this->math->point($x, $y, $d, $maxDist);
			$tx = $point['x'];
			$ty = $point['y'];
			$ts = 0;
		}

		$wasStop = FALSE;

		for ($ms = 0; $ms <= $time; $ms += $dt) {
			if ($ms) {
				$s_max = $s + $acceleration * $dt / 2500;
				if ($s_max > $speed) {
					$s_max = $speed;
				}
				$s_min = $s - $coordination * $dt / 2500;
				if ($s_min < 0) {
					$s_min = 0;
				}
				if ($ts === NULL || $ts > $s_max) {
					$ns = $s_max;
				} elseif ($ts < $s_min) {
					$ns = $s_min;
				} else {
					$ns = $ts;
				}

				$nd = $this->math->direction($x, $y, $tx, $ty); // $d === FALSE не может быть

				// dd - разница направлений
				$dd = $this->math->dd($d, $nd);

				if ($dd) {
					$s_for_dd = abs($coordination * $dt / $dd / 20);

					if ($s_for_dd < $ns) {
						if ($s_for_dd >= $s_min) {
							$ns = $s_for_dd;
						} else {
							$ns = $s_min;
							$dd_for_s = $coordination * $dt / $ns / 20;
							$nd = $this->math->d_norm($d + $this->math->sign($dd) * $dd_for_s);
						}
					}
				}

				$point = $this->math->point($x, $y, $nd, $ns / 1000 * $dt);

				$x = $point['x'];
				$y = $point['y'];
				$d = $nd;
				$s = round($ns, 2);
			}
			$this->valArr[$ms] = [
				'x' => $x,
				'y' => $y,
				'd' => $d,
				's' => $s,
			];
			if (!$wasStop && $this->math->distance($x, $y, $tx, $ty) < 1) {
				$this->match->addStop($ms >= $ms_min ? $ms : $ms_min);
				if ($s && $ms < $ms_min) {
					$wasStop = TRUE;
					$point = $this->math->point($x, $y, $d, $maxDist);
					$tx = $point['x'];
					$ty = $point['y'];
					$ts = $s;
				} else {
					break;
				}
			}
			if ($wasStop && $ms >= $ms_min) {
				break;
			}
			if ($s == 0 && $ts === 0) {
				break;
			}
		}
		$this->val = end($this->valArr);
	}

	protected function go_to_event($event)
	{
		switch ($event['name']) {
			case 'first_half':
				if ($this->isOnField()) {
					if ($this->side == 'l') {
						$d = 0;
						$x = $this->pos['x'] / 2;
					} else {
						$d = 180;
						$x = $this->pos['x'] + ($this->matchData['field']['width'] - $this->pos['x']) / 2;
					}
					$y = $this->pos['y'];
				} else {
					$d = 90;
					$y = -20;
					$pos = $this->settings['reserveIndex'] * 20;
					if ($this->side == 'l') {
						$x = $pos;
					} else {
						$x = $this->matchData['field']['width'] - $pos;
					}
				}
				$this->val = [
					'x' => $x,
					'y' => $y,
					'd' => $d,
					's' => 0,
				];
				break;
		}
	}

	// Ближайший игрок своей команды
	protected function nearest_player()
	{
		$x = $this->value['x'];
		$y = $this->value['y'];

		$distances = [];

		foreach (Match::$players as $k => $v) {
			if ($v->side == $this->side && $k != $this->id) {
				$distances[$k] = $this->get_distance(Match::$la[$k]['x'], Match::$la[$k]['y']);
			}
		}

		$players = array_keys($distances, min($distances));

		return $players[0];
	}

	protected function ballToPlayer($px, $py, $bx, $by, $ps, $bs, $pd)
	{
		$di = Match::$di; // расстояние взаимодействия
		$dt = 10; // ms

		for ($ms = 0; ; $ms += $dt) {
			$playerPoint = Match::point($px, $py, $pd, $ps * $ms / 1000);
			$ballDistance = Match::ball_move_distance($bs, $ms);
			$distance = Match::distance($playerPoint['x'], $playerPoint['y'], $bx, $by) - $ballDistance;
			if ($distance <= $di) {
				return $playerPoint;
			} elseif ($ms && ($distance >= $prevDistance || $ballDistance == $prevBallDistance)) {
				// TODO проверить, может ли после $distance == $prevDistance быть снова уменьшение дистанции
				return FALSE;
			}
			$prevDistance = $distance;
			$prevBallDistance = $ballDistance;
		}
	}

	protected function playerToBall($px, $py, $bx, $by, $ps, $bs, $bd) // TODO Переделать
	{
		$di = Match::$di; // расстояние взаимодействия
		$dt = 10; // ms
		$maxMs = 2000;

		for ($ms = 0; $ms <= $maxMs; $ms += $dt) {
			$playerDistance = $ms * $ps;
			$ballDistance = Match::ball_move_distance($bs, $ms);
			$ballPoint = Match::point($bx, $by, $bd, $ballDistance);
			$distance = Match::distance($ballPoint['x'], $ballPoint['y'], $px, $py) - $playerDistance;
			if ($distance <= $di) {
				return $ballPoint;
			}
			if ($ms && $ps == 0) {
				return FALSE;
			}
		}
		return $ballPoint;
	}

	// Пас в ноги, если $passOnGo == FALSE
	protected function pass($id, $power = NULL, $passOnGo = FALSE)
	{
		$accuracy = $this->data['accuracy'];
		$vision = $this->data['vision'];

		$power = $this->correct_skill('power', $power);

		$s = $power * Match::$power_to_ball_speed;

		$ball = Match::$la[0];
		$tp = Match::$la[$id]; // target player

		$distance = Match::distance($ball['x'], $ball['y'], $tp['x'], $tp['y']);

		if ($passOnGo) {
			$tpSpeed = Match::$players[$id]->data['speed'];
			// Можно брать значение не точно, либо 100
		} else {
			$v1 = $distance - 100;
			if ($v1 < 0) {
				$v1 = 0;
			} elseif ($v1 > 900) {
				$v1 = 900;
			}
			$k = 1 - $v1 / 900;
			$tpSpeed = $k * $tp['s'];
			// Тут корреляция от дистанции
			// Если целевой игрок близко - пас на ход с текущей скоростью
			// Если далеко - то пас в то место, где он находится в данный момент
		}

		if ($tpSpeed) {
			$target = $this->ballToPlayer($tp['x'], $tp['y'], $ball['x'], $ball['y'], $tpSpeed, $s, $tp['d']);
		} else {
			$target = FALSE;
		}

		if ($target === FALSE) {
			// Пусть, при недолете мяча до цели, он просто бьет в направлении цели. Плевать!
			$target = ['x' => $tp['x'], 'y' => $tp['y']];
		}

		$d = Match::direction($ball['x'], $ball['y'], $target['x'], $target['y']);

		// Создаем отклонение от точности
		$l = intval(200 - $accuracy - $vision + sqrt($distance)) / 10;

		$d = Match::d_norm($d + rand(-$l, $l));

		Match::$ball[$this->id] = [
			's' => $s,
			'd' => $d,
		];
	}

	// Пас на ход
	// (Пас в точку, куда попадет игрок двигаясь в текущем направлении со своей максимальной скоростью)
	protected function passOnGo($id, $power = NULL)
	{
		$this->pass($id, $power, TRUE);
	}

	protected function kick($x, $y, $power = NULL)
	{

	}

	// Расстояние от игрока до точки
	protected function get_distance($tx, $ty)
	{
		$x = $this->value['x'];
		$y = $this->value['y'];

		return Match::distance($x, $y, $tx, $ty);
	}

	protected function correct_skill($name, $value)
	{
		$value_max = $this->data[$name];
		if ($value === NULL || $value > $value_max) {
			$value = $value_max;
		} elseif ($value < 0) {
			$value = 0;
		}

		return $value;
	}

	protected function toBall($speed = NULL) // TODO Переделать
	{
		$ball = Match::$la[0];
		if ($speed = $this->correct_skill('speed', $speed)) {
			$this->target = $this->playerToBall($this->value['x'], $this->value['y'], $ball['x'], $ball['y'], $speed, $ball['s'], $ball['d']);
		} else {
			$this->target = [$ball['x'], $ball['y']];
		}
	}

	protected function logic()
	{

		$this->target = ['x' => 200, 'y' => 120];
		return;

		$x = $this->value['x'];
		$y = $this->value['y'];

		$ball = Match::$la[0];

		// Мяч на расстоянии взаимодействия
		if ($this->get_distance($ball['x'], $ball['y']) <= Match::$di) {
			$ballContact = TRUE;
		} else {
			$ballContact = FALSE;
		}

		$gyes = array_keys(Match::$players);
		unset($gyes[array_search($this->id, $gyes)]);

		$pl = $gyes[array_rand($gyes)];


		if ($ballContact) {
			//$this->pass($pl);
			$this->pass($this->nearest_player());
		}

		$dist = [];
		foreach (Match::$players as $key => $value) {
			$dist[$key] = Match::distance($ball['x'], $ball['y'], Match::$la[$key]['x'], Match::$la[$key]['y']);
		}

		$min = min($dist);

		$min_pl = array_search($min, $dist);

		if ($this->id == 1) {
			if (Match::distance(250, 450, $x, $y) > 5) {
				$this->target = ['x' => 250, 'y' => 450];
			}
		} elseif ($this->id == 2) {
			if (Match::distance(250, 150, $x, $y) > 5) {
				$this->target = ['x' => 250, 'y' => 150];
			}
		} elseif ($this->id == 3) {
			if (Match::distance(750, 450, $x, $y) > 5) {
				$this->target = ['x' => 750, 'y' => 450];
			}
		} else {
			if (Match::distance(750, 150, $x, $y) > 5) {
				$this->target = ['x' => 750, 'y' => 150];
			}
		}

		//$this->target = ['x' => $tx, 'y' => $ty];

	}

	// Предсказать позицию игрока ($id) через определенное время ($ms), основываясь на текущих позиции, скорости и направлении.
	/*public function predict_pos($id, $ms)
	{
		$vision = $this->data['vision'];

		$way = Match::$la[$id]['s'] * $ms / 1000;
		$point = Match::point(Match::$la[$id]['x'], Match::$la[$id]['y'], Match::$la[$id]['d'], $way);

		$distance = $this->get_distance(Match::$la[$id]['x'], Match::$la[$id]['y']);
		$l = intval((100 - $vision + sqrt($distance)) / 3);

		$x = $point['x'] + rand(-$l, $l);
		$y = $point['y'] + rand(-$l, $l);

		return ['x' => $x, 'y' => $y];
	}*/

	/*public function estimate_distance($tx, $ty)
	{
		$vision = $this->data['vision'];

		$distance = $this->get_distance($tx, $ty);
		$l = intval(100 - $vision + sqrt($distance));
		$res = $distance + rand(-$l, $l);
		if ($res < 0) {
			$res = 0;
		}
		return $res;
	}*/

	/*public function get_ball_move_time($distance, $power = NULL) // ms
	{
		$power = $this->correct_skill('power', $power);

		$s = $power * Match::$power_to_ball_speed;

		return Match::ball_move_time($distance, $s);
	}*/

	/*public function estimate_ball_move_time($distance, $power = NULL) // ms
	{
		$vision = $this->data['vision'];

		$ball_move_time = $this->get_ball_move_time($distance, $power);

		if ($ball_move_time !== FALSE) {
			$l = intval(100 - $vision + sqrt($distance)) * 10;
			$res = $ball_move_time + rand(-$l, $l);
			if ($res < 0) {
				$res = 0;
			}
		} else { // Достаточно херово, но... Плевать!
			$l = 2 - round($vision / 100, 2);
			$power = $this->correct_skill('power', $power) * $l;
			$s = $power * Match::$power_to_ball_speed;
			$res = Match::ball_move_time($distance, $s);
		}

		return $res;
	}*/
}
