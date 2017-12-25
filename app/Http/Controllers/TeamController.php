<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PlayerModel;
use App\SettingsModel;
use App\PlayersSettingsModel;

class TeamController extends Controller
{
    /**
     * Show team controls dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // if $request->input('setting_id', 1) ...
        $settings = auth()->user()->settings;
        foreach ($settings as $item) {
            $item->settings = json_decode($item->text);
        }

        $players = PlayerModel::getTeam($settings[0]->id);

        $data = [
            'settings' => $settings,
            'players' => $players,
            'options' => SettingsModel::getOptions(),
        ];

        return $request->ajax() ? response()->json($data) : view('team', $data);
    }

    /**
     * Get roles areas
     *
     * @return \Illuminate\Http\Response
     */
    public function getRolesAreas()
    {
        return response()->json(PlayerModel::getRolesAreas());
    }

    /**
     * Save settings
     *
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $errors = [];
        if (
            !is_string($request->input('settings_id'))
            || !($settings = SettingsModel::where('user_id', auth()->user()->id)->find($request->input('settings_id')))
            || !$this->validator($request, $errors)
        ) {
            $success = FALSE;
        } else {
            $success = TRUE;

            $settings->text = json_encode($request->input('settings'));
            $settings->save();

            foreach ($request->input('players') as $k => $v) {
                PlayersSettingsModel::where([
                    ['setting_id', $settings->id],
                    ['player_id', $k],
                ])->update(['text' => $this->playerSettings($v)]);
            }
        }

        return response()->json($success);
    }

    /**
     * Save new settings
     *
     * @return \Illuminate\Http\Response
     */
    public function saveAs(Request $request)
    {

    }

    protected function validator(Request $request, &$errors = [])
    {
        if (
            !SettingsModel::validateSettings($request->input('settings'))
            || !PlayerModel::validatePlayers($request->input('players'), auth()->user()->id, $errors)
        ) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Player's settings input to json
     *
     * @return string (json)
     */
    protected function playerSettings($data)
    {
        if ($data['reserveIndex'] == 'NULL') {
            $data['reserveIndex'] = NULL;
            $data['position'] = json_decode($data['position']);
        } else {
            $data['reserveIndex'] = intval($data['reserveIndex']);
            $data['position'] = NULL;
        }
        return json_encode($data);
    }
}
