<!DOCTYPE html>
<html lang="en">
	<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@yield('title')</title>

	<!-- Referencing Bootstrap CSS that is hosted locally -->
	{!! Html::style('css/bootstrap.min.css') !!}
	{!! Html::style('css/bootstrap-select.min.css') !!}
	<!-- Referencing Bootstrap JS that is hosted locally -->
	{!! Html::script('js/jquery-1.11.3.min.js') !!}
	{!! Html::script('js/jquery.timeago.js') !!}
	{!! Html::script('js/bootstrap.min.js') !!}	
	{!! Html::script('js/bootstrap-select.min.js') !!}	
	{!! Html::script('js/moment.min.js') !!}	
	</head>
	<body>
		<nav class="navbar navbar-default">
		  <div class="container-fluid">
			<div class="navbar-header">
			  <span class="navbar-brand">Another Chat</span>
			</div>
			<div>
				 @yield('navigation')				 
			</div>
		  </div>
		</nav>	
		<div class="container-fluid">
			<div class="row-fluid">
				<h2>@yield('title')</h2>
				@yield('content')
			</div>
		</div>	
	</body>
</html>