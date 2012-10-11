<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Laravel: A Framework For Web Artisans</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
	{{ HTML::style('bundles/bootstrap/css/bootstrap.min.css') }}
	{{ HTML::style('css/vectorwyse-seed.css') }}
	{{ HTML::style('bundles/bootstrap/css/bootstrap-responsive.min.css') }}
</head>
<body>
	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
				<a class="brand" href="#">Vectorwyse Seed</a>
				<div class="nav-collapse collapse">
					@if (Auth::check())
						@include('plugins.nav')
					@endif
					<ul class="nav pull-right">
						@if (Auth::check())
						<li>
							{{ HTML::link('logout', 'Logout') }}
						</li>
						@else
						<li class="dropdown">
							<a class="dropdown-toggle" data-toggle="dropbown" href="#">
								Sign in <strong class="caret"></strong>
							</a>
							<div class="dropdown-menu" style="padding: 15px; padding-bottom: 0px;">
								<form method="POST" action="user/login">
									<input name="email" type="text" class="span3" placeholder="Email">
									<input name="password" type="password" class="span3" placeholder="Password">
									<label class="checkbox">
									<input name="remember" type="checkbox">Remember me</label>
									<input class="btn btn-primary" type="submit" value="Sign In" />
								</form>
							</div>
						</li>
						@endif
					</ul>
				</div>
				<!--/.nav-collapse -->
			</div>
		</div>
	</div>
	<div class="container">
		@include('plugins.status')
		@yield('content')

		<hr>
		<footer class="pagination-centered">
			<p>&copy; Vectorwyse 2012</p>
		</footer>
	</div>
	<!-- /container -->

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="js/jquery-1.8.1.min.js"><\/script>')</script>
	{{ HTML::script('bundles/bootstrap/js/bootstrap.min.js') }}
	{{ HTML::script('js/vectorwyse-seed.js') }}
</body>
</html>