<?php

namespace App;

use App\Player;
use App\Math;

class Match {

    // Время от начала матча
    protected $time;

    // Характеристики матча
    protected $data;

    // Ball
    protected $ball;

    // Команды
    protected $teams;

    // last action
    protected $la;

    // Действие на мяч
    protected $ballActions = [];

    // Стоп-сигнал
    protected $stop = [];

    // Событие
    protected $event;

    // Приблизительный промежуток времени между запросами к серверу (сек.)
    protected $query_period = 10;

    // Милисекунд на итерацию минимум
    // Тут может быть 2 типа влияния
    // 1. Растягивание времени [у игроков]
    // 2. Невозможность пройти расстояние, соответствующее меньшему времени [у мяча]
    protected $ms_min = 10; // должно быть кратно $dt

    // dt (ms) в циклах перемещения
    protected $dt = 10;

    // Милисекунд на итерацию максимум (если нет stop сигнала)
    protected $ms_max = 1000;

    // Расстояние взаимодействия (distance interaction)
    protected $di = 10;

    // Коэффициент зависимости скорости мяча от силы игрока при ударе
    protected $power_to_ball_speed = 3;

    // Изменение скорости мяча за 1 сек.
    protected $ball_speed_k = -50;

    // Последний игрок, коснувшийся мяча
    protected $playerLastTouchedBall;

    //
    public function __construct($data, $teams, $la, $time)
    {
        $this->data = $data;

        $this->la = $la;

        $math = new Math();

        //$this->$ball = new Ball($this->getBallVal(), $this, $math);

        foreach ($players as $item) {
            if ($item->settings->position != NULL) {
                $this->players[$item->id] = new Player($item, $this->getPlayerVal($item->id), $this, $math);
            }
        }
    }

    //
    public function getPlayerVal($id)
    {
        return $this->la ? $this->la[$id] : NULL;
    }

    //
    public function getBallVal()
    {
        return $this->la ? $this->la[0] : NULL;
    }

    //
    public function getStop()
    {
        return $this->stop;
    }

    //
    public function addStop($ms)
    {
        $this->stop[] = $ms;
    }

    //
    public function getMs_max()
    {
        return $this->ms_max;
    }

    //
    public function getMs_min()
    {
        return $this->ms_min;
    }

    //
    public function getDt()
    {
        return $this->dt;
    }

    //
    public function getData()
    {
        return $this->data;
    }

    //
    public function getEvent()
    {
        return $this->event;
    }

    //
    public function getAction()
    {
        $actions = [];

        if (!$this->la) {
            $this->event = ['name' => 'first_half'];
        }

        $qpms = $this->query_period * 1000; // query period ms
        $time = 0;
        while ($time < $qpms) {
            $la = [[]];

            // Действия игроков
            foreach ($this->players as $k => $v) {
                $la[0][$k] = $v->do_action();
            }

            // Действие мяча
            //$la[0][0] = $this->ball->do_action();

            if ($this->stop) {
                $ms = min($this->stop);

                foreach ($this->players as $k => $v) {
                    $la[0][$k] = $v->setStopVal($ms);
                }

                $this->stop = [];
            } else {
                $ms = $this->ms_max;
            }

            $this->event = NULL;
            $this->la = $la[0];
            $time += $la[1] = $ms;

            if ($time < $qpms) {
                foreach ($la[0] as &$item) {
                    $item = [round($item->x), round($item->y)];
                }
                unset($item);
            }

            $actions[] = $la;
        }

        return $actions;
    }

    // Тест траектории
    public function test1()
    {
        $start = microtime(true);

        $request = request();

        $tx = $request->input('tx', 500);
        $ty = $request->input('ty', 300);
        $ts = $request->input('ts', 100);

        $x = $request->input('x', 0);
        $y = $request->input('y', 0);
        $s = $request->input('s', 0);
        $d = $request->input('d', 90);

        $speed = $request->input('speed', 100);
        $acceleration = $request->input('acceleration', 100);
        $coordination = $request->input('coordination', 100);

        $time = $request->input('time', 1000);

        echo '<form action="?">';
        echo '<input type="text" name="x" value="'.$x.'"> - x<br>';
        echo '<input type="text" name="y" value="'.$y.'"> - y<br>';
        echo '<input type="text" name="s" value="'.$s.'"> - s<br>';
        echo '<input type="text" name="d" value="'.$d.'"> - d<br>';
        echo '<input type="text" name="speed" value="'.$speed.'"> - speed<br>';
        echo '<input type="text" name="acceleration" value="'.$acceleration.'"> - acceleration<br>';
        echo '<input type="text" name="coordination" value="'.$coordination.'"> - coordination<br>';
        echo '<input type="text" name="tx" value="'.$tx.'"> - tx<br>';
        echo '<input type="text" name="ty" value="'.$ty.'"> - ty<br>';
        echo '<input type="text" name="ts" value="'.$ts.'"> - ts<br>';
        echo '<input type="text" name="time" value="'.$time.'"> - time<br>';
        echo '<input type="submit">';
        echo '</form>';

        Match::$ms_max = $time;

        $players = $this->get_players();
        $player = new Player($players[0]);
        $player->data['speed'] = $speed;
        $player->data['acceleration'] = $acceleration;
        $player->data['coordination'] = $coordination;
        $player->value = [
            'x' => $x,
            'y' => $y,
            's' => $s,
            'd' => $d,
        ];

        $player->target = [
            'x' => $tx,
            'y' => $ty,
            's' => $ts,
        ];
        $player->go_to();
        echo '<div style="float: left; position: relative; width: 1000px; height: 600px; border-left: 1px solid black; border-bottom: 1px solid black;">';
        foreach ($player->valArr as $k => $v) {
            echo '<div style="position: absolute; left: '.$v['x'].'px; bottom: '.$v['y'].'px">.</div>';
        }
        echo '<div style="color: red; position: absolute; left: '.$tx.'px; bottom: '.$ty.'px">x</div>';
        echo '</div>';
        echo '<div style="width: 500px; height: 600px; overflow: scroll;">';
        echo '<table border="1" style="width: 100%; border-collapse: collapse;">';
        echo '<tr><th>MS</th><th>X</th><th>Y</th><th>S</th><th>D</th></tr>';
        foreach ($player->valArr as $k => $v) {
            echo '<tr><td>'.$k.'</td><td>'.$v['x'].'</td><td>'.$v['y'].'</td><td>'.$v['s'].'</td><td>'.$v['d'].'</td></tr>';
        }
        echo '</table>';
        echo '</div>';

        if (Match::$stop) {
            echo '<div style="position: absolute; top: 0px; left: 600px;">Stop - '.Match::$stop[0].' ms</div>';
        }

        echo '<div style="position: absolute; top: 0px; left: 1200px;">'.round(microtime(true) - $start, 3).' sec</div>';
        exit;
    }

    // Позиции игроков
    public function test2()
    {
        $players = request()->input('players', NULL);
        if (is_array($players)) {
            foreach ($players as $k => $v) {
                \DB::table('stats')
                    ->where('player_id', $k)
                    ->update(['position' => json_encode([['x' => intval($v['x']), 'y' => intval($v['y'])]])]);
            }
        }
    }

    public function index()
    {


/*
        $k = Match::$ball_speed_k;
        $b = 300;
        $s = 100;
        $r = 500;
        $a = 50;


        $A = pow($k, 2) / 4;
        $B = $k * $b;
        $C = pow($b, 2) - pow($s, 2) - $r * cos(rad2deg($a)) * $k;
        $D = -2 * $r * cos(rad2deg($a)) * $b;
        $E = pow($r, 2);



        $P = (8 * $A * $C - 3 * pow($B, 2)) / (8 * pow($A, 2));
        $Q = (8 * pow($A, 2) * $D + pow($B, 3) - 4 * $A * $B * $C) / (8 * pow($A, 3));
        $R = (16 * $A * pow($B, 2) * $C - 64 * pow($A, 2) * $B * $D - 3 * pow($B, 4) + 256 * pow($A, 3) * $E) / (256 * pow($A, 4));




        $A3 = 1;
        $B3 = $P;
        $C3 = (pow($P, 2) - 4 * $R) / 4;
        $D3 = -pow($Q, 2) / 8;




        $P3 = (3 * $A3 * $C3 - pow($B3, 2)) / (3 * pow($A3, 2));
        $Q3 = (2 * pow($B3, 3) - 9 * $A3 * $B3 * $C3 + 27 * pow($A3, 2) * $D3) / (27 * pow($A3, 3));



        $Qbig = pow($P3 / 3, 3) + pow($Q3 / 2, 2);


        $alpha = pow(-$Q3 / 2 + sqrt($Qbig), 1 / 3);
        $beta = pow(-$Q3 / 2 - sqrt($Qbig), 1 / 3);

        //https://ru.wikipedia.org/wiki/Уравнение_четвёртой_степени
        //https://ru.wikipedia.org/wiki/Формула_Кардано
        //http://www.cleverstudents.ru/equations/cubic_equations.html#Cardano_formula
        //http://ateist.spb.ru/mw/alg4.htm
*/




        $players = $this->get_players();

        $data = [
            'players' => $players
        ];

        return view('match', $data);
    }
}
