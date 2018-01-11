<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\StatsModel;
use App\MatchModel;
use App\ActionModel;
use App\Match;
use App\Player;
use App\Ball;

use App\User;
use Validator;
use Cache;

class MatchController extends Controller
{
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

    public function getActions() // ajax
    {
        $fw = 1000; // длина поля
        $fh = 600; // ширина поля

        $match = MatchModel::find(1);

        if (!$match->write && time() >= $match->time) {
            $match->write = 1;
            //$match->save();//------

            $last_actions = $this->get_last_actions();
            if ($last_actions) {
                $la_array = json_decode($last_actions->text, TRUE);
                Match::$la = end($la_array)[0];

                // Вносим value мяча (у игроков - в конструкторе)
                Ball::$value = Match::$la[0];

                // Сокращаем последний элемент предыдущей записи
                $this->reduce_last_item($la_array);
                $last_actions->text = json_encode($la_array);
                $last_actions->save(); // лучше сохранять после успешной записи текущего actions
            } else {
                Match::$event = ['name' => 'first_half'];
            }

            Match::$data = [
                'user1_id' => $match->user1_id,
                'field' => [
                    'w' => $fw,
                    'h' => $fh
                ]
            ];

            Match::$players = [];
            $players = $this->get_players();
            foreach ($players as $item) {
                Match::$players[$item['player_id']] = new Player($item);
            }

            $json = [];
            $actions = [];

            $qpms = Match::$query_period * 1000; // query period ms
            $time = 0;
            while ($time < $qpms) {
                $la = [[]];

                // Действия игроков
                foreach (Match::$players as $k => $v) {
                    $la[0][$k] = $v->do_action();
                }

                // Действие мяча
                $la[0][0] = Ball::do_action();

                if (Match::$stop) {
                    $ms = min(Match::$stop);

                    foreach (Match::$players as $k => $v) {
                        $la[0][$k] = $v->value = isset($v->valArr[$ms]) ? $v->valArr[$ms] : end($v->valArr);
                    }

                    Match::$stop = [];
                } else {
                    $ms = Match::$ms_max;
                }

                Match::$event = [];
                Match::$la = $la[0];
                $time += $la[1] = $ms;

                $temp = [[], $la[1]];
                foreach ($la[0] as $k => $v) {
                    $temp[0][$k] = [round($v['x']), round($v['y'])];
                }
                $json[] = $temp;
                if ($time < $qpms) { // и если конец матча
                    $actions[] = $temp;
                } else {
                    $actions[] = $la;
                }
            }

            /*ActionModel::create(
                [
                    'match_id' => $match->id,
                    'text' => json_encode($actions)
                ]
            );*/
            $match->time = time() + floor($time / 1000);
            $match->write = 0;
            //$match->save();//-------
        } else {
            while ($match->write) {
                sleep(1);
                $match = MatchModel::find(1);
            }
            $last_actions = $this->get_last_actions();

            $json = json_decode($last_actions->text, TRUE);
            $this->reduce_last_item($json);
        }

        return response()->json($json);
    }

    protected function get_players()
    {
        $players = StatsModel::with('player')
                    ->where('match_id', 1)
                    ->where('out_time', NULL)
                    ->get()->toArray();

        return $players;
    }

    protected function get_last_actions()
    {
        $last_actions = ActionModel::where('match_id', 1)
                        ->orderBy('id', 'desc')
                        ->limit(1)->get()->first();

        return $last_actions;
    }

    protected function reduce_last_item(&$actions)
    {
        foreach ($actions[count($actions) - 1][0] as &$item) {
            $item = [round($item['x']), round($item['y'])];
        }
    }
}
