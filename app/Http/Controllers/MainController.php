<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Cache;
use Predis;

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
     * To make a challenge.
     *
     * @return \Illuminate\Http\Response
     */
    public function challenge(Request $request)
    {
        $data = ['message' => 'grgrgrgr', 'user' => 'wfwfwfwfw'];
        Predis::publish('user:1', json_encode($data));
        Predis::publish('user:3', json_encode(['message' => '111111', 'user' => '22222222']));
        return response()->json([]);

        $success = FALSE;
        $errors = [];
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $errors[] = trans('common.wrongData');
        } elseif (!($user = User::where(['confirmed' => 1])->find($request->input('user_id')))) {
            $errors[] = trans('userNotExists');
        } elseif (!$user->online) {
            $errors[] = trans('userNotOnline');
        } elseif (Cache::has('playing:' . $user->id)) {
            $errors[] = trans('userPlaying');
        } else {
            $success = TRUE;
        }

        if ($success) {
            $challenges = Cache::get('challenges:' . $user->id, []);
            if (!in_array(auth()->user()->id, $challenges)) {
                $challenges[] = auth()->user()->id;
            }
            Cache::put('challenges:' . $user->id, $challenges, 10);
            Cache::put('waiting:' . auth()->user()->id . ':' . $user->id, $challenges, 10);
            $request->session()->push('waiting', $user->id);
            return redirect('match');
        } else {
            return redirect()->back()->withErrors($errors);
        }

    }
}
