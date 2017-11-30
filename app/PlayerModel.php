<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class PlayerModel extends Model
{
	protected $table = 'players';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'speed', 'acceleration', 'coordination',
        'power', 'accuracy', 'vision', 'reaction', 'in_gate', 'on_out',
    ];

    /*public function user()
    {
        return $this->belongsTo('App\User');
    }*/

    /**
     * Coordinates of areas of roles on a field
     */
    private static $rolesAreas = [
        'Вр' => [
            'x' => [0, 143],
            'y' => [150, 450],
        ],
        'КЗЛ' => [
            'x' => [0, 334],
            'y' => [450, 600],
        ],
        'ЦЗ' => [
            'x' => [143, 286],
            'y' => [150, 450],
        ],
        'КЗП' => [
            'x' => [0, 334],
            'y' => [0, 150],
        ],
        'ОП' => [
            'x' => [286, 429],
            'y' => [150, 450],
        ],
        'КПЛ' => [
            'x' => [334, 667],
            'y' => [450, 600],
        ],
        'ЦП' => [
            'x' => [429, 572],
            'y' => [150, 450],
        ],
        'КПП' => [
            'x' => [334, 667],
            'y' => [0, 150],
        ],
        'АП' => [
            'x' => [572, 715],
            'y' => [150, 450],
        ],
        'ОФ' => [
            'x' => [715, 858],
            'y' => [150, 450],
        ],
        'КФЛ' => [
            'x' => [667, 1000],
            'y' => [450, 600],
        ],
        'ЦФ' => [
            'x' => [858, 1000],
            'y' => [150, 450],
        ],
        'КФП' => [
            'x' => [667, 1000],
            'y' => [0, 150],
        ],
    ];

    /**
     * Select all players by team with specific settings
     */
    public static function getTeam($settingId)
    {
        $rawRolesIds = 'GROUP_CONCAT(roles.id ORDER BY roles.id) AS roles_ids';
        $rawRolesNames = 'GROUP_CONCAT(roles.name ORDER BY roles.id) AS roles_names';
        $arr = DB::table('players_settings')
            ->select(
                'players.*',
                'players_settings.text AS settings',
                DB::raw($rawRolesIds),
                DB::raw($rawRolesNames)
            )
            ->leftJoin('players', 'players.id', '=', 'players_settings.player_id')
            ->leftJoin('players_roles', 'players_roles.player_id', '=', 'players.id')
            ->leftJoin('roles', 'roles.id', '=', 'players_roles.role_id')
            ->where(['setting_id' => $settingId])
            ->groupBy('players.id')->get();

        $result = [];
        $temp = [];

        foreach ($arr as &$item) {
            $item->settings = json_decode($item->settings);
            $item->roles = array_combine(explode(',', $item->roles_ids), explode(',', $item->roles_names));
            unset($item->roles_ids, $item->roles_names);

            if ($item->settings->position) {
                foreach (array_values(self::$rolesAreas) as $k => $v) {
                    if (
                        $item->settings->position->x >= $v['x'][0]
                        && $item->settings->position->x < $v['x'][1]
                        && $item->settings->position->y >= $v['y'][0]
                        && $item->settings->position->y < $v['y'][1]
                    ) {
                        $temp[$k][] = $item;
                        break;
                    }
                }
            } else {
                $result[11 + $item->settings->reserveIndex] = $item;
            }
        }
        unset($item);

        foreach ($temp as &$item) {
            if (count($item) > 1) {
                usort($item, function ($a, $b) {
                    if ($a->settings->position->y > $b->settings->position->y) {
                        return 1;
                    } elseif ($a->settings->position->y < $b->settings->position->y) {
                        return -1;
                    } else {
                        if ($a->settings->position->x < $b->settings->position->x) {
                            return 1;
                        } elseif ($a->settings->position->x > $b->settings->position->x) {
                            return -1;
                        } else {
                            return 0;
                        }
                    }
                });
            }
        }
        unset($item);
         
        ksort($temp);

        $i = 0;
        foreach ($temp as $item) {
            foreach ($item as $item1) {
                $result[$i] = $item1;
                $i++;
            }
        }
         
        ksort($result);

        return $result;
    }

    private static $addRoleMaxCount = 1;

    /**
     * Settings of roles for creating team
     */
    private static $roles = [
        'Вр' => [
            'count' => 2,
            'addRole' => [],
            'dataRange' => [
                'speed' => [10, 50],
                'acceleration' => [10, 50],
                'coordination' => [30, 60],
                'power' => [30, 60],
                'accuracy' => [10, 50],
                'vision' => [10, 50],
                'reaction' => [30, 60],
                'in_gate' => [30, 60],
                'on_out' => [30, 60],
            ],
            'defaultPos' => [
                [
                    'x' => 71,
                    'y' => 300,
                ],
            ],
        ],
        'КЗЛ' => [
            'count' => 1,
            'addRole' => ['ЦЗ', 'КЗП', 'КПЛ'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [30, 60],
                'coordination' => [30, 60],
                'power' => [10, 50],
                'accuracy' => [10, 50],
                'vision' => [10, 50],
                'reaction' => [30, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 214,
                    'y' => 525,
                ],
            ],
        ],
        'ЦЗ' => [
            'count' => 3,
            'addRole' => ['КЗЛ', 'КЗП', 'ОП'],
            'dataRange' => [
                'speed' => [10, 50],
                'acceleration' => [10, 50],
                'coordination' => [30, 60],
                'power' => [30, 60],
                'accuracy' => [10, 50],
                'vision' => [10, 50],
                'reaction' => [30, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 214,
                    'y' => 375,
                ],
                [
                    'x' => 214,
                    'y' => 225,
                ],
            ],
        ],
        'КЗП' => [
            'count' => 1,
            'addRole' => ['ЦЗ', 'КЗЛ', 'КПП'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [30, 60],
                'coordination' => [30, 60],
                'power' => [10, 50],
                'accuracy' => [10, 50],
                'vision' => [10, 50],
                'reaction' => [30, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 214,
                    'y' => 75,
                ],
            ],
        ],
        'ОП' => [
            'count' => 1,
            'addRole' => ['ЦЗ', 'ЦП'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [10, 50],
                'coordination' => [30, 60],
                'power' => [30, 60],
                'accuracy' => [10, 50],
                'vision' => [10, 50],
                'reaction' => [30, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
        ],
        'КПЛ' => [
            'count' => 1,
            'addRole' => ['ЦП', 'КЗЛ', 'КПП'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [30, 60],
                'coordination' => [30, 60],
                'power' => [10, 50],
                'accuracy' => [10, 50],
                'vision' => [10, 50],
                'reaction' => [30, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 500,
                    'y' => 525,
                ],
            ],
        ],
        'ЦП' => [
            'count' => 2,
            'addRole' => ['ОП', 'АП'],
            'dataRange' => [
                'speed' => [20, 60],
                'acceleration' => [20, 50],
                'coordination' => [20, 60],
                'power' => [20, 60],
                'accuracy' => [20, 60],
                'vision' => [20, 60],
                'reaction' => [20, 50],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 500,
                    'y' => 375,
                ],
                [
                    'x' => 500,
                    'y' => 225,
                ],
            ],
        ],
        'КПП' => [
            'count' => 1,
            'addRole' => ['ЦП', 'КЗП', 'КПЛ'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [30, 60],
                'coordination' => [30, 60],
                'power' => [10, 50],
                'accuracy' => [10, 50],
                'vision' => [10, 50],
                'reaction' => [30, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 500,
                    'y' => 75,
                ],
            ],
        ],
        'АП' => [
            'count' => 1,
            'addRole' => ['ЦП', 'ОФ'],
            'dataRange' => [
                'speed' => [20, 60],
                'acceleration' => [20, 60],
                'coordination' => [20, 60],
                'power' => [20, 50],
                'accuracy' => [20, 60],
                'vision' => [30, 60],
                'reaction' => [20, 50],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
        ],
        'ОФ' => [
            'count' => 1,
            'addRole' => ['ЦФ', 'АП'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [30, 60],
                'coordination' => [30, 60],
                'power' => [20, 50],
                'accuracy' => [20, 60],
                'vision' => [20, 50],
                'reaction' => [20, 50],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 786,
                    'y' => 300,
                ],
            ],
        ],
        'КФЛ' => [
            'count' => 1,
            'addRole' => ['ЦФ', 'КПЛ', 'КФП'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [30, 60],
                'coordination' => [30, 60],
                'power' => [10, 50],
                'accuracy' => [20, 60],
                'vision' => [10, 50],
                'reaction' => [20, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
        ],
        'ЦФ' => [
            'count' => 1,
            'addRole' => ['ОФ'],
            'dataRange' => [
                'speed' => [20, 60],
                'acceleration' => [20, 60],
                'coordination' => [20, 60],
                'power' => [30, 60],
                'accuracy' => [30, 60],
                'vision' => [20, 50],
                'reaction' => [30, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
            'defaultPos' => [
                [
                    'x' => 929,
                    'y' => 300,
                ],
            ],
        ],
        'КФП' => [
            'count' => 1,
            'addRole' => ['ЦФ', 'КПП', 'КФЛ'],
            'dataRange' => [
                'speed' => [30, 60],
                'acceleration' => [30, 60],
                'coordination' => [30, 60],
                'power' => [10, 50],
                'accuracy' => [20, 60],
                'vision' => [10, 50],
                'reaction' => [20, 60],
                'in_gate' => [1, 10],
                'on_out' => [1, 10],
            ],
        ],
    ];

    public static function createTeam($user_id)
    {
        // user's default setting
        $setting_id = DB::table('settings')->where(['user_id' => $user_id])->first()->id;

        $arr = DB::table('roles')->get();
        $roles = [];
        foreach ($arr as $item) {
            $roles[$item->name] = $item->id;
        }

        $names = DB::table('names')->get();
        $surnames = DB::table('surnames')->get();

        $reserveIndex = 0;

        foreach (self::$roles as $key => $val) {

            $defaultPosCount = 0;

            for ($i = 0; $i < $val['count']; $i++) {
                $createArr = [
                    'user_id' => $user_id,
                    'name' => $names[array_rand($names)]->name . ' '
                        . $surnames[array_rand($surnames)]->surname,
                ];
                foreach ($val['dataRange'] as $key1 => $val1) {
                    $createArr[$key1] = rand($val1[0], $val1[1]);
                }
                $player = self::create($createArr);

                // roles
                $playersRolesCreateArr = [
                    [
                        'player_id' => $player->id,
                        'role_id' => $roles[$key],
                    ],
                ];

                $addRoleCount = rand(0, self::$addRoleMaxCount);
                $addRole = $val['addRole'];
                while ($addRoleCount && count($addRole)) {
                    $addRoleKey = array_rand($addRole);
                    $playersRolesCreateArr[] = [
                        'player_id' => $player->id,
                        'role_id' => $roles[$addRole[$addRoleKey]],
                    ];
                    unset($addRole[$addRoleKey]);
                    $addRoleCount--;
                }

                DB::table('players_roles')->insert($playersRolesCreateArr);

                // default settings
                if (isset($val['defaultPos']) && $defaultPosCount < count($val['defaultPos'])) {
                    $defSet = [
                        'position' => $val['defaultPos'][$defaultPosCount],
                        'reserveIndex' => NULL,
                    ];
                    $defaultPosCount++;
                } else {
                    $defSet = [
                        'position' => NULL,
                        'reserveIndex' => $reserveIndex,
                    ];
                    $reserveIndex++;
                }

                PlayersSettingsModel::create([
                    'player_id' => $player->id,
                    'setting_id' => $setting_id,
                    'text' => json_encode($defSet),
                ]);
            }
        }
    }
}