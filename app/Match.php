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

    //
    protected $players = [];

    //
    protected $playersOnField = [];

    //
    protected $values;

    // Действие на мяч
    protected $ballActions = [];

    // Стоп-сигнал
    protected $stop = [];

    // События
    protected $events = [];

    // Приблизительный промежуток времени выполнения одного расчета (сек.)
    protected $period = 10;

    // Милисекунд на итерацию минимум
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
    public function __construct($data, $teams, $values, $time) // Может быть еще и $events
    {
        $this->data = $data;

        $this->values = $values;

        $math = new Math();

        //$this->$ball = new Ball($this, $math);

        foreach ($teams as $team) {
            foreach ($team['players'] as $item) {
                $this->players[$item['id']] = new Player($item, $this, $math);
            }
        }

        $this->teams = $teams;
    }

    //
    public function getPlayerVal($id)
    {
        return $this->values ? $this->values[$id] : NULL;
    }

    //
    public function getBallVal()
    {
        return $this->values ? $this->values[0] : NULL;
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
    public function isEvent(string $name)
    {
        return isset($this->events[$name]);
    }

    //
    public function addEvent(string $name, array $data = [])
    {
        return $this->events[$name] = $data;
    }

    //
    public function getActions()
    {
        $actions = [
            'motions' => [],
            'events' => [],
        ];

        if ($this->time == 0) {
            $this->addEvent('first_half');
        }

        $period = $this->period * 1000; // period ms
        $time = 0;
        while ($time < $period) {
            $values = [];

            // Действия игроков
            foreach ($this->players as $k => $v) {
                if ($act = $v->do_action()) {
                    $values[$k] = $act;
                }
            }

            // Действие мяча
            //$values[0] = $this->ball->do_action();

            // Обработка стоп-сигнала
            if ($this->stop) {
                $ms = min($this->stop);

                foreach ($values as $k => &$v) {
                    if ($k) {
                        $v = $this->players[$k]->setStopVal($ms);
                    }
                }
                unset($v);

                $this->stop = [];
            } elseif ($this->isEvent('first_half')) {
                $ms = 0;
            } else {
                $ms = $this->ms_max;
            }

            // Обработка событий и очистка тех событий, который можно очистить
            // Заполнение событий [карточки и т.д.]
            $this->events = [];
            $events = [];

            // Заполнение values
            foreach ($values as $k => $v) {
                $this->values[$k] = $v;
            }

            $time += $ms;

            if ($time < $period) {
                foreach ($values as &$item) {
                    $item = [round($item['x']), round($item['y'])];
                }
                unset($item);
            }

            $actions['motions'][] = [$ms, $values];
            if ($events) {
                $actions['events'][] = [$time, $events];
            }
        }

        $this->time += $time;

        return $actions;
    }

    //
    public function getStats()
    {
        $res = [];

        foreach ($this->players as $item) {
            $res[$item->id] = $item->stats;
        }

        return $res;
    }

    //
    public function getValues()
    {
        return $this->values;
    }

    //
    public function getTime()
    {
        return $this->time;
    }
}

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
