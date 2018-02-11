<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Predis;
use JWTAuth;
use Illuminate\Database\QueryException;

class MainController extends Controller
{
    /**
     * Main page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()) {
            return view('auth.login');
        } else {
        	$users = User::getList();
        	return view('main', ['users' => $users]);
        }
    }

    /**
     * Get jwt
     *
     * @return \Illuminate\Http\Response
     */
    public function jwt()
    {
        return response()->json(['token' => JWTAuth::fromUser(auth()->user())]);
    }

    /**
     * To make a challenge.
     *
     * @return \Illuminate\Http\Response
     */
    public function challenge(Request $request)
    {
        $userFrom = auth()->user();

        $success = FALSE;
        $errors = [];
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $errors[] = trans('common.wrongData');
        } elseif (!($userTo = User::findConfirmed($request->input('user_id')))) {
            $errors[] = trans('userNotExists');
        } elseif ($userFrom->challengesFrom()->where(['user_to' => $userTo->id])->count()) {
            $errors[] = trans('challengeAlreadyExists');
        } else {
            $success = TRUE;
            try {
                $userFrom->challengesFrom()->create(['user_to' => $userTo->id]);
                if ($userTo->type == 'man' && $userTo->online) {
                    $data = [
                        'action' => 'challengeAdd',
                        'userFrom' => [
                            'id' => $userFrom->id,
                            'name' => $userFrom->name,
                        ],
                    ];
                    Predis::publish('user:' . $userTo->id, json_encode($data));
                }
            } catch (QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $success = FALSE;
                    $errors[] = trans('challengeAlreadyExists');
                } else {
                    throw $e;
                }
            }
        }

        $result = [
            'success' => $success,
        ];
        if (!$success) {
            $result['error'] = $errors;
        }

        return response()->json($result);
    }
}
