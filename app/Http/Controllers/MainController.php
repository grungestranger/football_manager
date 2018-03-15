<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Predis;
use JWTAuth;
use Illuminate\Database\QueryException;
use App\Jobs\Match;

class MainController extends Controller
{
    /**
     * Main page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!($user = auth()->user())) {
            return view('auth.login');
        } else {
        	$users = User::getList();
            foreach ($users as $i) {
                $i->challenge = $i->id != $user->id
                && count(
                    $user->challengesFrom->where('user_to', $i->id)
                ) == 0
                && count(
                    $user->challengesTo->where('user_from', $i->id)
                ) == 0;
            }
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
            'user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors[] = trans('common.wrongData');
        } elseif ($userFrom->id == intval($request->input('user_id'))) {
            $errors[] = trans('common.notToYourSelf');
        } elseif (!($userTo = User::findConfirmed($request->input('user_id')))) {
            $errors[] = trans('common.userNotExists');
        // TODO need block !!!
        } elseif ($userFrom->challengesTo()->where(['user_from' => $userTo->id])->count()) {
            $errors[] = trans('common.challengeForYouAlreadyExists');
        } else {
            $success = TRUE;
            try {
                $userFrom->challengesFrom()->create(['user_to' => $userTo->id]);
                if ($userTo->type == 'man' && $userTo->online) {
                    $data = [
                        'action' => 'challengeAdd',
                        'user' => [
                            'id' => $userFrom->id,
                            'name' => $userFrom->name,
                            'match' => $userFrom->match ? TRUE : FALSE,
                        ],
                    ];
                    Predis::publish('user:' . $userTo->id, json_encode($data));
                }
            } catch (QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $success = FALSE;
                    $errors[] = trans('common.challengeAlreadyExists');
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

    /**
     * To remove a from challenge.
     *
     * @return \Illuminate\Http\Response
     */
    public function fromChallengeRemove(Request $request)
    {
        return $this->challengeRemove($request, 'from');
    }

    /**
     * To remove a to challenge.
     *
     * @return \Illuminate\Http\Response
     */
    public function toChallengeRemove(Request $request)
    {
        return $this->challengeRemove($request, 'to');
    }

    /**
     * To remove a challenge.
     *
     * @return \Illuminate\Http\Response
     */
    protected function challengeRemove(Request $request, $side)
    {
        $user1 = auth()->user();

        $success = FALSE;
        $errors = [];
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors[] = trans('common.wrongData');
        } else {
            $success = TRUE;
            $userId = $request->input('user_id');
            if ($side == 'from') {
                $user1->challengesFrom()->where(['user_to' => $userId])->delete();
            } else {
                $user1->challengesTo()->where(['user_from' => $userId])->delete();
            }
            if (
                ($user2 = User::findConfirmed($userId))
                && $user2->type == 'man'
                && $user2->online
            ) {
                $data = [
                    'action' => $side . 'ChallengeRemove',
                    'user' => [
                        'id' => $user1->id,
                    ],
                ];
                Predis::publish('user:' . $user2->id, json_encode($data));
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

    /**
     * To agree to the game.
     *
     * @return \Illuminate\Http\Response
     */
    protected function play(Request $request) // TODO need block tables !!!
    {
        $user1 = auth()->user();

        $success = FALSE;
        $errors = [];
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors[] = trans('common.wrongData');
        } elseif ($user1->match) {
            $errors[] = trans('common.youPlaying');
        } elseif (
            !(
                $challenge = $user1->challengesTo()
                    ->where(['user_from' => $request->input('user_id')])
                    ->first()
            )
        ) {
            $errors[] = trans('common.challengeNotExists');
        } elseif (!($user2 = $challenge->userFrom)) {
            $errors[] = trans('common.userNotExists');
        } elseif (!$user2->online) {
            $errors[] = trans('common.userNotOnline');
        } elseif ($user2->match) {
            $errors[] = trans('common.userPlaying');
        } else {
            $success = TRUE;
            $challenge->delete();
            $match = $user1->match1()->create(['user2_id' => $user2->id]);
            $this->dispatch((new Match($match))->delay(60));
            if ($user2->type == 'man') {
                Predis::publish('user:' . $user2->id, json_encode([
                    'action' => 'startMatch',
                    'user' => [
                        'id' => $user1->id,
                        'name' => $user1->name,
                    ],
                ]));
            }
            Predis::publish('all', json_encode([
                'action' => 'usersStartMatch',
                'users' => [$user1->id, $user2->id],
            ]));
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
