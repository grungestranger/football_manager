<?php

namespace App\Http\Controllers;

use App\User;

class MainController extends Controller
{
    public function index()
    {
        if (!$this->user) {
            return view('auth.login');
        } else {
        	$users = User::getList();
        	return view('main', ['users' => $users]);
        }
    }
}
