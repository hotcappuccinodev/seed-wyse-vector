@layout('layouts/main')

@section('content')
	<div class="hero-unit clearfix">
		<div class="pull-left span5">
			<h1>Vectorwyse Project Seed</h1>
			<p>Use this template as a way to quick start any new project.</p>
		</div>
		<div class="pull-right">
			<form class="well" method="POST" action="user/login">
				<label>Email</label>
				<input name="email" type="text" class="span3" placeholder="Type your email">
				<label>Password</label>
				<input name="password" type="password" class="span3" placeholder="Type your password">
				<label class="checkbox">
				<input name="remember" type="checkbox">Remember me</label>
				<input class="btn btn-primary" type="submit" value="Sign In" />
			</form>
		</div>
	</div>

@endsection