<?php

namespace App;

class Match {

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

	// Знак
    public function sign($x, $not_null = FALSE) {
        if ($x == 0 && !$not_null) {
            return 0;
        } else {
            return $x < 0 ? -1 : 1;
        }
    }
}
