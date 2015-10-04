@extends('layouts.master')
@section('title', "Chat / ".e($userName));
@section('navigation')	
	<ul class="nav navbar-nav">
	<li class="active"><a href="user">Chat</a></li>
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
			<div id="message_send" class="send-wrap">
				<textarea id="message_input" class="form-control send-message" rows="3" placeholder="Write a message..."></textarea>
			</div>
			<div id="button_panel" class="btn-panel">
				<a id="send_button" href="#" class="col-lg-4 text-right btn send-message-btn pull-right" role="button">
					<i class="fa fa-plus"></i>
					Send Message (crtl + enter)
				</a>
			</div>
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
					scope: UserChatScope.personal,
					enableWrite: true,
					enableDelete: false,
					userId: {{ $userId }}, 
					userWrapperId: "user_wrapper",
					userListId: "user_list", 
					messageWrapperId: "message_wrapper",
					messageListId: "message_list", 
					messageInputId: "message_input",
					sendButtonId: "send_button",
					errorWrapperId: "error_wrapper"
				}
			);
		}
	);
</script>
@stop
