<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Match1;
use Carbon\Carbon;

class MatchController extends Controller
{
    /**
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        if ($match = $user->match) {

            // if match doesn't start yet
            if (
                Carbon::parse($match->created_at)->timestamp
                - Carbon::now()->timestamp
                < config('match.preparation_time')
            ) {

            }
            $settings = $user->setting;
            $settings->settings = json_decode($settings->text);
            $players = Player::getTeam($settings->id);

            $data = [
                'settings' => $settings,
                'players' => $players,
            ];
            $data['allSettings'] = $user->settings;
            $data['options'] = Settings::getOptions();
            $data['rolesAreas'] = Player::getRolesAreas();

            $data['isMatch'] = TRUE;
            return view('team', $data);
        } else {
            return redirect('/');
        }
    }
}
