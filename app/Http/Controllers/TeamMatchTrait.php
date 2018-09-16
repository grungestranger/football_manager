<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Player;
use App\Models\Settings;

trait TeamMatchTrait
{
    protected function validator(Request $request, &$errors = [])
    {
        $playersErrors = [];
        if (
            !Settings::validateSettings($request->input('settings'))
            || !Player::validatePlayers($request->input('players'), auth()->user()->id, $playersErrors)
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
     * @return mixed
     */
    protected function playerSettings($data, $toJson = TRUE)
    {
        if ($data['reserveIndex'] == 'NULL') {
            $data['reserveIndex'] = NULL;
            $data['position'] = json_decode($data['position']);
        } else {
            $data['reserveIndex'] = intval($data['reserveIndex']);
            $data['position'] = NULL;
        }
        return $toJson ? json_encode($data) : $data;
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
