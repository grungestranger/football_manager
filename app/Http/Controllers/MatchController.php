<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\TeamMatchTrait;
use App\MatchHandler;
use App\Models\Player;
use App\Models\Settings;

class MatchController extends Controller
{
    use TeamMatchTrait;

    /**
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();
/*
$matchHandler = new MatchHandler($user->match);
$matchHandler->create();
*/
        if ($match = $user->match) {
            $matchHandler = new MatchHandler($match);

            $mhData = $matchHandler->getData($user->id);

            $data = [
                'settings' => $mhData->settings,
                'players' => $mhData->players,
                'allSettings' => $user->settings,
                'options' => Settings::getOptions(),
                'rolesAreas' => Player::getRolesAreas(),
                'isMatch' => TRUE,
                'actions' => $mhData->actions,
                'time' => $mhData->time,
                'teams' => $mhData->teams,

                'action' => json_encode($matchHandler->exec()),
            ];

            return view('team', $data);
        } else {
            return redirect('/');
        }
    }

    /**
     * Save settings
     *
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $user = auth()->user();

        $errors = [];
        if (
            !is_string($settings_id = $request->input('settings_id'))
            || (
                $settings_id = $settings_id == 'NULL' ? NULL
                    : ($user->settings()->find($settings_id) ? intval($settings_id) : FALSE)
            ) === FALSE
            || !$this->validator($request, $errors)
            // TODO проверка на количество замен и удаленных игроков
        ) {
            $success = FALSE;
        } else {
            $success = TRUE;

            $players = $request->input('players');
            foreach ($players as &$item) {
                $item = $this->playerSettings($item, FALSE);
            }
            unset($item);

            $matchHandler = new MatchHandler($user->match);
            $matchHandler->saveTeam($user->id, $players, $request->input('settings'), $settings_id);
        }

        return response()->json($this->resultData($success, $errors));
    }
}
