<?php

namespace App;

class Ball {

	// Значения во время матча
	public $value;

	public function do_action()
	{
		if (Match::$event) {
			if (in_array(Match::$event['name'], ['first_half', 'second_half'])) {
				$nx = Match::$data['field']['w'] / 2;
				$ny = Match::$data['field']['h'] / 2;
				$ns = 0;
				$nd = 0;
			}
			// считаем, что все значения правильно округлены, либо округляем тут
		} else {
			if (Match::$ball) { // если удар по мячу
				if (count(Match::$ball) > 1) { // единоборство
					$sum = [];
					foreach (Match::$ball as $k => $v) {
						$sum[$k] = Match::$players[$k]->data['coordination'] + Match::$players[$k]->data['power'] + rand(-10, 10);
					}
					$winners = array_keys($sum, max($sum));
					$key = $winners[rand(0, count($winners) - 1)];
					Match::$playerLastTouchedBall = $key;
					$res = Match::$ball[$key];
				} else {
					$res = reset(Match::$ball);
				}
				$s = $res['s'];
				$d = $res['d'];
				Match::$ball = []; // очищаем
			} else {
				$s = self::$value['s'];
				$d = self::$value['d'];
			}
			$x = self::$value['x'];
			$y = self::$value['y'];

			// Игроки, изначально находящиеся на расстоянии взаимодействия
			$nearestPlayers = [];
			foreach (Match::$players as $k => $v) {
				if (Match::distance($x, $y, $v->valArr[0]['x'], $v->valArr[0]['y']) <= Match::$di) {
					$nearestPlayers[] = $k;
				}
			}

			if (Match::$stop) {
				$time = min(Match::$stop);
			} else {
				$time = Match::$ms_max;
			}

			for ($ms = Match::$ms_min; $ms <= $time; $ms += Match::$dt) {
				$way = Match::ball_move_distance($s, $ms);
				$point = Match::point($x, $y, $d, $way);
				foreach (Match::$players as $k => $v) {
					if (!in_array($k, $nearestPlayers)) {
						if (isset($v->valArr[$ms])) {
							$px = $v->valArr[$ms]['x'];
							$py = $v->valArr[$ms]['y'];
						} else {
							$end = end($v->valArr);
							$px = $end['x'];
							$py = $end['y'];
						}
						if (Match::distance($point['x'], $point['y'], $px, $py) <= Match::$di) {
							Match::$stop[] = $ms;
							break 2;
						}
					}
				}
			}

			$ns = Match::ball_move_speed($s, $ms <= $time ? $ms : $time);
			$nx = $point['x'];
			$ny = $point['y'];
			$nd = $d;

			if ($nx < 0 || $nx > Match::$data['field']['w'] || $ny < 0 || $ny > Match::$data['field']['h']) {
				$nx = Match::$data['field']['w'] / 2;
				$ny = Match::$data['field']['h'] / 2;
				$ns = 0;
			}
		}

		self::$value = [
			'x' => $nx,
			'y' => $ny,
			'd' => $nd,
			's' => $ns,
		];

		return self::$value;
	}

	// Возвращает время в ms или FALSE, за которое мяч
	// с заданной начальной скоростью
	// преодолеет заданное расстояние
	public function ball_move_time($distance, $s) // ms
	{
		// Решаем интеграл по графику зависимости изменения скорости от времени
		// Решаем квадратное уравнение (решение интеграла)
		$a = self::$ball_speed_k / 2;
		$b = $s;
		$c = -$distance;

		$D = pow($b, 2) - 4 * $a * $c;

		if ($D >= 0) {
			$x = -($b - sqrt($D)) / 2 / $a; // берем x2
			$res = round($x * 1000);
		} else {
			$res = FALSE;
		}

		return $res;
	}

	// Возвращает скорость мяча (в сек.)
	// поистечении заданного времени, учитывая заданную начальную скорость
	public function ball_move_speed($s, $ms)
	{
		$ns = $s + self::$ball_speed_k * $ms / 1000;
		if ($ns < 0) {
			$ns = 0;
		}

		return round($ns, 2);
	}

	// Возвращает дистанцию, которую пройдет мяч
	// за заданное время, учитывая заданную начальную скорость
	public function ball_move_distance($s, $ms)
	{
		$t = $ms / 1000;
		$ns = $s + self::$ball_speed_k * $t;
		if ($ns < 0) {
			$distance = $s / -self::$ball_speed_k * $s / 2; // подразумевается, что ball_speed_k отрицательное число
		} else {
			$distance = pow($t, 2) * self::$ball_speed_k / 2 + $s * $t;
		}

		return round($distance, 2);
	}
}
