<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

use Illuminate\Http\Request;
use Auth;
use Lang;
use Mail;
use Illuminate\Http\JsonResponse;
use App\Models\Player;
use DB;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Token length
     *
     * @var int
     */
    protected $confirm_hash_length = 25;

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }
    
    // LOGIN
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        $throttles = $this->isUsingThrottlesLoginsTrait();

        if ($throttles && $lockedOut = $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            if ($request->ajax()) {
                $response = [
                    'success' => FALSE,
                    'errors' => 'tooManyLoginAttempts',
                ];
                return response()->json($response);
            } else {
                return $this->sendLockoutResponse($request);
            }
        }

        $credentials = $this->getCredentials($request);

        $not_confirmed = FALSE;
        $auth = Auth::guard($this->getGuard());
        if ($auth->attempt($credentials, false, false)) {
            $user = $auth->getLastAttempted();
            if ($user->confirmed) {
                $auth->login($user, $request->has('remember'));
                if ($request->ajax()) {
                    $response = ['success' => TRUE];
                    return response()->json($response);
                } else {
                    return $this->handleUserWasAuthenticated($request, $throttles);
                }
            } else {
                $not_confirmed = TRUE;
            }
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if ($throttles && ! $lockedOut) {
            $this->incrementLoginAttempts($request);
        }

        if ($request->ajax()) {
            $response = [
                'success' => FALSE,
                'errors' => $not_confirmed ? 'notConfirmedEmail' : 'wrongData',
            ];
            return response()->json($response);
        } else {
            return $this->sendFailedLoginResponse($request, $not_confirmed);
        }
    }
    
    protected function sendFailedLoginResponse(Request $request, $not_confirmed = FALSE)
    {
        if ($not_confirmed) {
            $message = $this->getNotConfirmedEmailMessage();
        } else {
            $message = $this->getFailedLoginMessage();
        }
        return redirect()->back()
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors([
                $this->loginUsername() => $message,
            ]);
    }
    
    protected function getNotConfirmedEmailMessage()
    {
        return Lang::has('auth.not_confirmed_email')
            ? Lang::get('auth.not_confirmed_email')
            : 'Please, confirm your email.';
    }

    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if (($request->ajax() && ! $request->pjax()) || $request->wantsJson()) {
            $response = [
                'success' => FALSE,
                'errors' => $errors,
            ];
            return new JsonResponse($response);
        }

        return redirect()->to($this->getRedirectUrl())
                        ->withInput($request->input())
                        ->withErrors($errors, $this->errorBag());
    }
    
    // REGISTER
    public function getRegister(Request $request)
    {
        if ($request->session()->has('wasSuccessRegister')) {
            $request->session()->forget('wasSuccessRegister');
            return view('auth.was_success_register');
        } else {
            return $this->showRegistrationForm();
        }
    }

    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $user = $this->create($request->all());

        $success = FALSE;
        while (!$success) {
            // For unique token
            try {
                $hash = str_random($this->confirm_hash_length);
                DB::table('tokens')->insert(
                    ['user_id' => $user->id, 'token' => $hash]
                );
                $success = TRUE;
            } catch (QueryException $e) {
                if ($e->errorInfo[1] != 1062) {
                    throw $e;
                }
            }
        }
        
        Mail::send('auth.emails.confirm_link', ['hash' => $hash], function ($m) use ($user) {
            $m->to($user->email, $user->name);
            $m->subject('Confirm your email');
        });

        $request->session()->put('wasSuccessRegister', TRUE);

        return redirect('/register');
    }
    
    // CONFIRM-EMAIL
    public function confirmEmail(Request $request)
    {
        $hash = $request->input('hash', NULL);
        $success = FALSE;

        if (is_string($hash) && mb_strlen($hash) == $this->confirm_hash_length) {
            $user_id = DB::table('tokens')->where('token', $hash)->value('user_id');
            if ($user_id && ($user = User::find($user_id))) {
                // User confirmed
                $user->confirmed = 1;
                $user->save();

                // Delete row from tokens
                DB::table('tokens')->where('user_id', $user_id)->delete();

                // Create team
                Player::createTeam($user->id);

                $success = TRUE;
                $message = Lang::has('auth.confirm_email_success')
                    ? Lang::get('auth.confirm_email_success', ['email' => $user->email])
                    : $user->email . ' was confirmed successfully. Now, you can login.';
            } else {
                $message = Lang::has('auth.confirm_email_not_success')
                    ? Lang::get('auth.confirm_email_not_success')
                    : 'The user is not found.';
            }
        } else {
            $message = Lang::has('notify.no_data')
                ? Lang::get('notify.no_data')
                : 'No data';
        }

        $data = [
            'success' => $success,
            'message' => $message,
        ];
        return view('auth.confirm_email', $data);
    }
}
