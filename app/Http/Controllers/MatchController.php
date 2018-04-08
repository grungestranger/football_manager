<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\MatchHandler;
use App\Models\Player;
use App\Models\Settings;

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
/*
$matchHandler = new MatchHandler($user->match);
$matchHandler->create();
*/
        if ($match = $user->match) {
            $matchHandler = new MatchHandler($match);

            $team = $matchHandler->getTeam($user);

            $data = [
                'settings' => $team->settings,
                'players' => $team->players,
                'allSettings' => $user->settings,
                'options' => Settings::getOptions(),
                'rolesAreas' => Player::getRolesAreas(),
                'isMatch' => TRUE,
            ];

            return view('team', $data);
        } else {
            return redirect('/');
        }
    }
}
