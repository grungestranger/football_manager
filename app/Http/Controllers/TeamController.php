<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PlayerModel;
use App\SettingsModel;
use App\PlayersSettingsModel;
use Validator;
use Illuminate\Database\QueryException;

class TeamController extends Controller
{
    /**
     * Show team controls dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('settings_id')) {
            if (
                !is_string($request->input('settings_id'))
                || !($settings = auth()->user()->settings()->find($request->input('settings_id')))
            ) {
                abort(404);
            }
        } else {
            $settings = auth()->user()->settings[0];
        }

        $settings->settings = json_decode($settings->text);

        $players = PlayerModel::getTeam($settings->id);

        $data = [
            'settings' => $settings,
            'players' => $players,
        ];
        if (!$request->ajax()) {
            $data['allSettings'] = auth()->user()->settings;
            $data['options'] = SettingsModel::getOptions();
            $data['rolesAreas'] = PlayerModel::getRolesAreas();
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
                PlayersSettingsModel::where([
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
        $errors = [];
        $validator = Validator::make($request->all(), [
            'settings_name' => 'required|max:255|unique:settings,name,NULL,id,user_id,' . auth()->user()->id,
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
                $settings = SettingsModel::create([
                    'user_id' => auth()->user()->id,
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
                PlayersSettingsModel::insert($playersSettingsCreateArr);
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

    protected function validator(Request $request, &$errors = [])
    {
        $playersErrors = [];
        if (
            !SettingsModel::validateSettings($request->input('settings'))
            || !PlayerModel::validatePlayers($request->input('players'), auth()->user()->id, $playersErrors)
        ) {
            foreach ($playersErrors as $item) {
                $errors[] = trans('team.' . $item);
            }
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

    /**
     * Result data
     *
     * @return array
     */
    protected function resultData($success, $errors = [], $result = [])
    {
        $result['success'] = $success;
        if ($success) {
            $result['message'] = trans('common.success');
        } else {
            if (!$errors) {
                $result['error'] = trans('common.wrongData') . ' ' .
                    trans('common.reloadPage');
            } else {
                $result['error'] = implode(' ', $errors);
            }
        }
        return $result;
    }
}
