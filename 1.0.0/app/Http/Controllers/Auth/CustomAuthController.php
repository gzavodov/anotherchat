<?php

namespace App\Http\Controllers\Auth;

use Auth;
use Validator;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class CustomAuthController extends Controller
{	
	private $user = null;
    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'getLogout']);
    }

	public function getUserId()
	{
		return Auth::id();
	}

	public function getUser()
	{
		if($this->user === null)
		{
			$this->user = User::find(Auth::id());
		}
		return $this->user;
	}
	
	/**
	 * Show the application login form.
	 *
	 * @return Response
	 */	
	public function getLoginRegister($action = 'login')
	{		
		$form = strtolower($action);
		if($form !== 'login' && $form !== 'register')
		{
			$form = 'login';
		}
		return view('auth.loginregister')->with('formName', $form);
	}
	
	/**
	 * LogOut
	 *
	 * @return Response
	 */		
	public function getLogout()
	{
		Auth::logout();
		return redirect($this->loginPath());
	}	
	
	/**
	 * Handle a login request to the application.
	 *
	 * @param Request  $request
	 * @return Response
	 */
	public function postLogin(Request $request)
	{
		$data = $request->only('login', 'password');
		$validator = Validator::make(
			$data, 
			[
				'login' => 'required', 
				'password' => 'required',
			]
		);		
		
		if($validator->fails())
		{
			return redirect($this->loginPath())
				->withInput($request->only('login', 'password'))
				->withErrors($validator->errors()->getMessages());
		}
		
		$login = $request['login'];
		$loginKey = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
		$credentials = [ $loginKey => $login, 'password' => $request['password'] ];
		
		return Auth::attempt($credentials, $request->has('remember')) 
			? redirect()->intended($this->redirectPath())
			: redirect($this->loginPath())
				->withInput($request->only('login', 'password', 'remember'))
				->withErrors([ 'login_failed' => $this->getFailedLoginMessage() ]);		
	}	

	/**
	 * Handle a registration request for the application.
	 *
	 * @param Request  $request
	 * @return Response
	 */
	public function postRegister(Request $request)
	{
		$data = $request->only('name', 'email', 'password', 'password_confirmation');
		$validator = Validator::make(
			$data, 
			[
				'name' => 'required|alpha_num|min:3|max:32', 
				'email' => 'required|email|max:255', 
				'password' => 'required|min:3|confirmed', 
				'password_confirmation' => 'required|min:3', 
			]
		);
		
		if($validator->fails())
		{
			return redirect($this->registerPath())
				->withInput($request->only('name', 'email', 'password'))
				->withErrors($validator->errors()->getMessages());
		}
		
		$fields = $request->only('name', 'email', 'password');		
		//For test only - it's not concurrency safe check 
		$user = User::where('email', '=', $fields['email'])->first();
		if ($user !== null) 
		{
			return redirect($this->registerPath())
				->withInput($request->only('name', 'email', 'password'))
				->withErrors(['email' => 'User with same email address already exists in the database.']);
		}
		
		$user = User::where('name', '=', $fields['name'])->first();
		if ($user !== null) 
		{
			return redirect($this->registerPath())
				->withInput($request->only('name', 'email', 'password'))
				->withErrors(['name' => 'User with same name already exists in the database.']);
		}
	
		Auth::login($this->create($fields));
		return redirect($this->redirectPath());
	}
	
	/**
	 * Get the post login path.
	 *
	 * @return string
	 */	
	protected function loginPath()
	{
		return 'auth/login';
	}
	
	/**
	 * Get the post register path.
	 *
	 * @return string
	 */	
	protected function registerPath()
	{
		return 'auth/register';
	}	
	
	/**
	 * Get the post register / login redirect path.
	 *
	 * @return string
	 */
	public function redirectPath()
	{
		$user = $this->getUser();
		return $user !== null && $user->isAdmin() ? 'chat/admin' : 'chat/user';
	}
	
	/**
	 * Get the login error message.
	 *
	 * @return string
	 */	
	protected function getFailedLoginMessage()
	{
		return 'These credentials do not match our records.';
	}
	
    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
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
        return User::create(
			[
				'name' => $data['name'],
				'email' => $data['email'],
				'is_admin' => false,
				'password' => bcrypt($data['password']),
			]
		);
    }
}
