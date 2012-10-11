<?php

class User_Controller extends Base_Controller
{
	public function action_login()
	{
		$credentials = array(
			'username' => Input::get('email'),
			'password' => Input::get('password')
		);
		if( Auth::attempt($credentials) ){
			Session::flash('status_success', 'You are now logged in.');
			return Redirect::to('dashboard');
		} else {
			Session::flash('status_error', 'Your email or password is invalid - please try again.');
			return Redirect::to('/');
		}
	}

	public function action_logout()
	{
		Auth::logout();
		return Redirect::to('/');
	}
}