<?php

namespace App;

class Match {

	// Характеристики матча
	public static $data;

	// Игроки
	public static $players;

	// last action
	public static $la;

	// Действие на мяч
	public static $ball = [];

	// Стоп-сигнал
	public static $stop = [];

	// Событие
	public static $event = [];

	// Приблизительный промежуток времени между запросами к серверу (сек.)
	public static $query_period = 150;

	// Милисекунд на итерацию минимум
	// Тут может быть 2 типа влияния
	// 1. Растягивание времени [у игроков]
	// 2. Невозможность пройти расстояние, соответствующее меньшему времени [у мяча]
	public static $ms_min = 10; // должно быть кратно $dt

	// dt (ms) в циклах перемещения
	public static $dt = 10;

	// Милисекунд на итерацию максимум (если нет stop сигнала)
	public static $ms_max = 1000;

	// Расстояние взаимодействия (distance interaction)
	public static $di = 10;

	// Коэффициент зависимости скорости мяча от силы игрока при ударе
	public static $power_to_ball_speed = 3;

	// Изменение скорости мяча за 1 сек.
	public static $ball_speed_k = -50;

	// Последний игрок, коснувшийся мяча
	public static $playerLastTouchedBall;


	public static function distance($x1, $y1, $x2, $y2)
	{
		$w = $x2 - $x1;
		$h = $y2 - $y1;

		$distance = sqrt(pow($w, 2) + pow($h, 2));

		return round($distance, 2);
	}

	public static function direction($x1, $y1, $x2, $y2)
	{
		$w = $x2 - $x1;
		$h = $y2 - $y1;

		if ($w || $h) {
			if ($w) {
				$k = $h / $w;
			} else {
				$k = 10000 * sign($h);
			}

			$d = rad2deg(atan($k));
			if ($d < 0) {
				$d = $d + 360;
			}
			if ($w < 0) {
				$d = $d + 180 * sign($k, TRUE);
			}

			return round($d, 2);
		} else {
			return FALSE;
		}
	}

	// $s - мс, $t - мс
	public static function way($x1, $y1, $x2, $y2, $s, $t)
	{
		$w = $x2 - $x1;
		$h = $y2 - $y1;

		$stop = NULL;

		if ($w || $h) {
			$gip = sqrt(pow($w, 2) + pow($h, 2));
			$way = $s * $t;
			if ($way > $gip) {
				$stop = round($gip / $way * $t);

				$x = $x2;
				$y = $y2;
			} else {
				$cos = $w / $gip;

				$ky = 1;
				if ($y1 > $y2) {
					$ky = -1;
				}

				$x = $x1 + $way * $cos;
				$y = $y1 + sqrt(pow($way, 2) - pow($way * $cos, 2)) * $ky;
			}
		} else {
			$stop = 0;

			$x = $x1;
			$y = $y1;
		}

		return ['x' => round($x, 2), 'y' => round($y, 2), 'stop' => $stop];
	}

	// Новые координаты по направлению и расстоянию
	public static function point($x, $y, $d, $way)
	{
		$cos = cos(deg2rad($d));

		$nx = $x + $way * $cos;
		$ky = 1;
		if ($d > 180) {
			$ky = -1;
		}
		$ny = $y + sqrt(pow($way, 2) - pow($way * $cos, 2)) * $ky;

		return ['x' => round($nx, 2), 'y' => round($ny, 2)];
	}

	// Разница направлений
	public static function dd($d1, $d2)
	{
		$dd = $d2 - $d1;
		if (abs($dd) > 180) {
			$dd = (abs($dd) - 360) * sign($dd);
		}

		return round($dd, 2);
	}

	// Нормализовать направление
	public static function d_norm($d)
	{
		if ($d < 0) {
			$d = 360 + $d;
		} elseif ($d > 360) {
			$d = $d - 360;
		}

		return round($d, 2);
	}

	// Возвращает время в ms или FALSE, за которое мяч
	// с заданной начальной скоростью
	// преодолеет заданное расстояние
	public static function ball_move_time($distance, $s) // ms
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
	public static function ball_move_speed($s, $ms)
	{
		$ns = $s + self::$ball_speed_k * $ms / 1000;
		if ($ns < 0) {
			$ns = 0;
		}

		return round($ns, 2);
	}

	// Возвращает дистанцию, которую пройдет мяч
	// за заданное время, учитывая заданную начальную скорость
	public static function ball_move_distance($s, $ms)
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