<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PlayerModel;
use App\SettingsModel;

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

        return view('team', $data);
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
            || !is_array($request->input('settings'))
            || !SettingsModel::validateSettings($request->input('settings'))
        ) {
            $errors[] = ['code' => 0, 'message' => 'Wrong data'];
        }

        if (!$errors) {
            echo 'good';
        } else {
            echo 'bad';
        }

        
        // Save global settings
        // Save settings for each user
    }
}
