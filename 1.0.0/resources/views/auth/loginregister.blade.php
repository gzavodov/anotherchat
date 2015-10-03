@extends('layouts.master')
@section('title', 'Login/Register Form')

@section('content')
	
<div class="col-md-8"> 
	@if(count($errors) > 0)
		<div class="alert alert-danger">
			<strong>Whoops, looks like something went wrong</strong><br>
			<ul>
				@foreach ($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif	
	<ul id="form_tabs" class="nav nav-tabs" role="tablist">
		<li role="presentation" @if($formName === 'login') class="active" @endif>
			<a href="#login_form" aria-controls="login_form" role="tab" data-toggle="tab">Login</a>
		</li>
		<li role="presentation" @if($formName === 'register') class="active" @endif>
			<a href="#register_form" aria-controls="register_form" role="tab" data-toggle="tab">Register</a>
		</li>
	 </ul>
	<div class="tab-content">
		<div id="register_form" role="tabpanel"
			@if($formName === 'register') class="tab-pane active" @else class="tab-pane" @endif>
			<div class="panel-heading">Please specify registration information</div>
			<form class="form-horizontal" role="form" method="POST" action="register">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">

				<div class="form-group">
					<label class="col-md-4 control-label">Name</label>
					<div class="col-md-6">
						<input type="text" class="form-control" name="name" value="{{ old('name') }}">
					</div>
				</div>	
				<div class="form-group">
					<label class="col-md-4 control-label">E-Mail Address </label>
					<div class="col-md-6">
						<input type="email" class="form-control" name="email" value="{{ old('email') }}">
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-4 control-label">Password</label>
					<div class="col-md-6">
						<input type="password" class="form-control" name="password">
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-4 control-label">Confirm Password</label>
					<div class="col-md-6">
						<input type="password" class="form-control" name="password_confirmation">
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-6 col-md-offset-4">
						<button type="submit" class="btn btn-primary">Register</button>					
					</div>
				</div>
			</form>
		</div>
		<div id="login_form" role="tabpanel"
			@if($formName === 'login') class="tab-pane active" @else class="tab-pane" @endif>
			<div class="panel-heading">Please specify login information</div>
			<form class="form-horizontal" role="form" method="POST" action="login">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">

				<div class="form-group">
					<label class="col-md-4 control-label">Name / E-Mail Address</label>
					<div class="col-md-6">
						<input type="text" class="form-control" name="login" value="{{ old('login') }}">
					</div>
				</div>				
				<div class="form-group">
					<label class="col-md-4 control-label">Password </label>
					<div class="col-md-6">
						<input type="password" class="form-control" name="password">
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-6 col-md-offset-4">
						<div class="checkbox">
							<label> 
								<input type="checkbox" name="remember"> Remember Me
							</label>
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-6 col-md-offset-4">
						<button type="submit" class="btn btn-primary" style="margin-right: 15px;">Login</button>
					</div>
				</div>
			</form>
		</div>		
	</div>
</div>	
<script type="text/javascript">
	$(document).ready(
		function() 
		{
			$('#form_tabs a').click(
				function (e) 
				{
					$(this).tab('show');
					e.preventDefault();
				}
			);
		}
	);
</script>
@stop

