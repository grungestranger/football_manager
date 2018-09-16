<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Player;
use App\Models\Settings;
use App\Models\PlayersSettings;
use Validator;
use Illuminate\Database\QueryException;

class TeamController extends Controller
{
    use TeamMatchTrait;

    /**
     * Show team controls dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request->ajax()) {
            if (
                !is_string($request->input('settings_id'))
                || !($settings = $user->settings()->find($request->input('settings_id')))
            ) {
                abort(404);
            }
            $user->cur_setting = $settings->id;
            $user->save();
        } else {
            $settings = $user->setting;
        }

        $settings->settings = json_decode($settings->text);

        $players = Player::getTeam($settings->id);

        $data = [
            'settings' => $settings,
            'players' => $players,
            'isMatch' => FALSE,
        ];
        if (!$request->ajax()) {
            $data['allSettings'] = $user->settings;
            $data['options'] = config('settings.options');
            $data['rolesAreas'] = Player::getRolesAreas();
        }

        return $request->ajax() ? response()->json($data) : view('team', $data);
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
            || !($settings = auth()->user()->settings()->find($request->input('settings_id')))
            || !$this->validator($request, $errors)
        ) {
            $success = FALSE;
        } else {
            $success = TRUE;

            $settings->text = json_encode($request->input('settings'));
            $settings->save();

            foreach ($request->input('players') as $k => $v) {
                PlayersSettings::where([
                    ['setting_id', $settings->id],
                    ['player_id', $k],
                ])->update(['text' => $this->playerSettings($v)]);
            }
        }

        return response()->json($this->resultData($success, $errors));
    }

    /**
     * Save new settings
     *
     * @return \Illuminate\Http\Response
     */
    public function saveAs(Request $request)
    {
        $user = auth()->user();

        $errors = [];
        $validator = Validator::make($request->all(), [
            'settings_name' => 'required|max:255|unique:settings,name,NULL,id,user_id,' . $user->id,
        ]);
        if (
            $validator->fails()
            || !$this->validator($request, $errors)
        ) {
            $success = FALSE;

            if ($validator->fails()) {
                $errors = $validator->errors()->all(':message');
            }
        } else {
            $success = TRUE;

            // For unique key [name, user_id] in settings table
            try {
                $settings = Settings::create([
                    'user_id' => $user->id,
                    'name' => $request->input('settings_name'),
                    'text' => json_encode($request->input('settings')),
                ]);

                $playersSettingsCreateArr = [];
                foreach ($request->input('players') as $k => $v) {
                    $playersSettingsCreateArr[] = [
                        'setting_id' => $settings->id,
                        'player_id' => $k,
                        'text' => $this->playerSettings($v),
                    ];
                }
                PlayersSettings::insert($playersSettingsCreateArr);

                $user->cur_setting = $settings->id;
                $user->save();
            } catch (QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $success = FALSE;

                    $errors[] = trans('validation.unique', ['attribute' => trans('common.name1')]);
                } else {
                    throw $e;
                }
            }
        }

        $result = $success ? ['settings' => $settings] : [];

        return response()->json($this->resultData($success, $errors, $result));
    }

    /**
     * Remove settings
     *
     * @return \Illuminate\Http\Response
     */
    public function remove(Request $request)
    {
        if (
            !is_string($request->input('settings_id'))
            || !($settings = auth()->user()->settings()->find($request->input('settings_id')))
            || auth()->user()->settings()->count() < 2
        ) {
            $success = FALSE;
        } else {
            $success = TRUE;

            $settings->playersSettings()->delete();
            $settings->delete();
        }

        return response()->json($this->resultData($success));
    }
}
