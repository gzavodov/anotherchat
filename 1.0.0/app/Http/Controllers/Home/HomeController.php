<?php
namespace App\Http\Controllers\Home;

use Auth;
use Request;
use App\User;
use App\Http\Controllers\Controller;
use App\Message;

class HomeController extends Controller
{
	private $user = null;
    public function __construct()
    {
        $this->middleware('auth');
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
	
	public function getServiceUrl()
	{
		$serverName = env('WS_SERVER', Request::server('SERVER_NAME'));
		$port = env('WS_PORT', 8080);
		
		return "ws://{$serverName}:{$port}";
	}
	
	/**
	 * Show the home page.
	 *
	 * @return \Illuminate\Http\Response
	 */	
	public function getHomePage()
	{
		$user = $this->getUser();
		return redirect($user->isAdmin() ? 'home/admin' : 'home/user');
	}	
	
	/**
	 * Show the user page.
	 *
	 * @return \Illuminate\Http\Response
	 */	
	public function getChatPage()
	{
		$user = $this->getUser();
		if($user->isAdmin())
		{
			return redirect('home/admin');
		}
		
		$user = $this->getUser();
		return view(
			'home/user', 
			[ 
				'userId' => $user->id, 
				'userName' => $user->name,
				'isAdmin' => $user->isAdmin(),
				'serviceUrl' => $this->getServiceUrl() 
			]
		);
	}
	/**
	 * Show the admin page.
	 *
	 * @return \Illuminate\Http\Response
	 */	
	public function getAdminPage()
	{		
		$user = $this->getUser();
		if(!$user->isAdmin())
		{
			return redirect('home/user');
		}
		
		return view(
			'home/admin', 
			[ 
				'userId' => $user->id, 
				'userName' => $user->name,
				'isAdmin' => true,
				'serviceUrl' => $this->getServiceUrl() 
			]
		);
	}	
}
