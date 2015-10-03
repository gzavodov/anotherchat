<?php namespace App\Server;

use Symfony\Component\Console\Output\ConsoleOutput;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Client;
use App\User;
use App\Message;
use Log;

class ChatServer implements MessageComponentInterface
{
  protected $clients;
  public function __construct() 
  {
	$this->clients = new \SplObjectStorage();
  }

  public function console($message, $type = 'info')
  {
	$output = new ConsoleOutput();
	$output->writeln("<{$type}>{$message}</{$type}>");
  }

  public function onOpen(ConnectionInterface $socket) 
  {
	$client = new Client($socket);
	$this->clients->attach($client);
	$this->console("onOpen ({$socket->resourceId})");
	$this->sendClientUsersList();
  }

public function onMessage(ConnectionInterface $socket, $data) 
{
	$this->console("onMessage", "comment");
	try
	{
		$sender = $this->findClientByConnection($socket);
		$msg = json_decode($data);
		if(!$msg)
		{ 
			return;
		}
		
		if(!isset($msg->topic))
		{
			return;
		}
		
		switch ($msg->topic) 
		{
			case 'login':
				$this->console('Login', 'comment');
				$user = User::findOrFail($msg->data->user_id);
				$sender->user = $user;
				$sender->user->setOnline();
				$this->console('User {$user->name}');

				$this->sendClientUsersList();
				break;
			case 'new_message':
			{
				$addresseeId = $msg->data->to_id;
				$addressee = $this->findClientByUserId($addresseeId);
				$message = Message::create(
					[
						'from_id' => $sender->user->id,
						'to_id'   => $addresseeId,
						'message' => $msg->data->message
					]
				);				
				
				$sender->send([ 'topic' => 'messages', 'data' => [ $message ] ]);
				if($addressee)
				{
					$addressee->send([ 'topic' => 'messages', 'data' => [$message] ]);		
				}
				break;
			}
			case 'request':
			{
				$this->console('Request', 'comment');
				
				$method = property_exists($msg->data, 'method') ? $msg->data->method : "";
				if($method !== '')
				{					
					$messageId = property_exists($msg->data, 'id') ? (int)$msg->data->id : 0;
					if($method === 'delete' && $messageId > 0)
					{
						$message = Message::find($messageId);
						if($message !== null)
						{
							$message->delete();
							$sender->send([ 'topic' => 'deletion', 'data' => [ 'id' => $messageId ] ]);					
						}
					}
					return;
				}
				
				$scope = 'personal';
				$targetUserId = 0;
				
				if(property_exists($msg->data, 'filter') && is_object($msg->data->filter))
				{
					$requestFilter = $msg->data->filter;
					if(property_exists($requestFilter, 'scope'))
					{
						$scope = $requestFilter->scope;
					}
					
					if(property_exists($requestFilter, 'target_user_id'))
					{
						$targetUserId = (int)$requestFilter->target_user_id;
					}
				}
				
				if($scope === 'global' && $sender->user->isAdmin())
				{
					$filter = array('scopeUserId' => $targetUserId);
				}
				else //if($scope === 'personal')
				{
					$filter = array('scopeUserId' => $sender->user->id, 'targetUserId' => $targetUserId);
				}
				$this->sendClientMessageLog($sender, $filter);
				break;
			}		
		}
	} 
	catch (\Exception $e) 
	{
	  $this->console('Error: '.$e->getMessage(), 'error');
	  Log::info($e);
	}
}

  public function onClose(ConnectionInterface $socket) 
  {
	$client = $this->findClientByConnection($socket);
	if ($client) 
	{
	  if ($client->isLoggedIn())
		$client->user->setOffline();

	  $this->clients->detach($client);
	  $this->console("onClose {$socket->resourceId}", "error");
	}
	$this->sendClientUsersList();
  }

  public function onError(ConnectionInterface $socket, \Exception $e) 
  {
	Log::error( $e );
	$this->console("An error has occurred: {$e->getMessage()}", "error");
	$socket->close();
  }

  public function findClientByConnection(ConnectionInterface $socket)
  {
	foreach ($this->clients as $client)
	{
	  if ($client->socket == $socket)
	  {
		return $client;
	  }
	}
	return null;
  }
  
  public function findClientByUserId( $user_id )
  {
	foreach ($this->clients as $client)
	{
		if ($client->isLoggedIn() && $client->user->id == $user_id)
		{
			return $client;
		}
	}
	return null;
  }
  
  public function sendClientUsersList()
  {
	$users = User::orderBy('status', 'desc')->orderBy('name', 'asc')->get();
	$message['topic'] = 'users';
	$message['data']['users'] = $users;

	foreach ($this->clients as $client)
	{
	  if($client->isLoggedIn())
	  {
		$client->send($message);  
	  }
	}
  }

  public function sendClientMessageLog($client, array $filter)
  {	
	$scopeUserId = isset($filter['scopeUserId']) ? $filter['scopeUserId'] : 0;
	if($scopeUserId <= 0)
	{
		$scopeUserId = $client->user->id;
	}
	
	$targetUserId = isset($filter['targetUserId']) ? $filter['targetUserId'] : 0;
	if($targetUserId <= 0)
	{
		$messages = Message::where('to_id', '=', $scopeUserId)
			->orWhere('from_id', '=', $scopeUserId)
			->orderBy('id', 'asc')->get()->toArray();		
	}
	else
	{
		$messages = Message::where(
			function($query) use($scopeUserId, $targetUserId)
			{
				$query->where('to_id', '=', $scopeUserId)->where('from_id', '=', $targetUserId);
			}
		)->orWhere(
			function($query) use($scopeUserId, $targetUserId)
			{
				$query->where('to_id', '=', $targetUserId)->where('from_id', '=', $scopeUserId);
			}		
		)->orderBy('id', 'asc')->get()->toArray();		
	}

	
	$message['topic'] = 'messages';
	$message['data'] = $messages;

	$client->send($message);
  }
}