<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Server\ChatServer;
use App\User;
use DB;

class ChatStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start chat.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		DB::connection()->enableQueryLog();
		User::where('status', 1)->update(['status'=>0]);

		$server = IoServer::factory(
			new HttpServer(new WsServer(new ChatServer())),
			env('WS_PORT', 8080)
		);
		$server->run();
    }
}
