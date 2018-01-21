<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

use Illuminate\Http\Request;
use Password;
use Lang;

class PasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after reset.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new password controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateSendResetLinkEmail($request);

        $broker = $this->getBroker();
        
        $password = Password::broker($broker);
        $credentials = $this->getSendResetLinkEmailCredentials($request);
        $user = $password->getUser($credentials);

        if ($user && !$user->confirmed) {
            return $this->getSendResetLinkEmailFailureNotConfirmedResponse($request);
        }

        $response = $password->sendResetLink(
            $credentials,
            $this->resetEmailBuilder()
        );

        switch ($response) {
            case Password::RESET_LINK_SENT:
                return $this->getSendResetLinkEmailSuccessResponse($response);
            case Password::INVALID_USER:
            default:
                return $this->getSendResetLinkEmailFailureResponse($response);
        }
    }
    
    protected function getSendResetLinkEmailFailureNotConfirmedResponse(Request $request)
    {
        $message = Lang::has('auth.not_confirmed_email')
                ? Lang::get('auth.not_confirmed_email')
                : 'Please, confirm your email.';
        return redirect()->back()->withInput($request->input())->withErrors(['email' => $message]);
    }

     // TODO Возможно, что-то еще нужно в этом контроллере.
}
