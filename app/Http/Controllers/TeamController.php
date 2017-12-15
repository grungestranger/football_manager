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
        $settings = $this->user->settings;
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
        // Save global settings
        // Save settings for each user
    }
}
