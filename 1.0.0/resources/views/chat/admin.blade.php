@extends('layouts.master')
@section('title', "Administration / ".e($userName));
@section('navigation')	
	<ul class="nav navbar-nav">
	<li><a href="user">Chat</a></li>
	<li class="active"><a href="admin">Administration</a></li>
	<li><a href="../auth/logout">Logout</a></li>
	</ul>
@stop

@section('content')	
{!! Html::style('http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css') !!}
{!! Html::style('css/chat.css') !!}
{!! Html::script('js/chat.js') !!}
<div class="container">
	<div class="row">
		<div id="error_wrapper"></div>
		<div id="user_wrapper" class="user-wrap col-lg-12">
			<ul id="user_list" class="list-group"></ul>
		</div>
		<div id="message_wrapper" class="message-wrap col-lg-8" style="display:none;">
			<div id="message_list" class="msg-wrap"></div>
		</div>
	</div>
</div>	
<script type="text/javascript">
	$(document).ready(
		function() 
		{
			WebSocketClient.serviceUrl = "{{ $serviceUrl }}";
			UserChat.create(
				{ 
					scope: UserChatScope.global,
					enableWrite: false,
					enableDelete: true,
					userId: {{ $userId }}, 
					userWrapperId: "user_wrapper",
					userListId: "user_list", 
					messageWrapperId: "message_wrapper",
					messageListId: "message_list",
					errorWrapperId: "error_wrapper"
				}
			);
		}
	);
</script>
@stop
