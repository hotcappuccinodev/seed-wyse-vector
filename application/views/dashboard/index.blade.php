@layout('layouts/main')

@section('content')
	<div class="hero-unit clearfix">
		<div class="pull-left span5">
			<h1>Dashboard</h1>
			<p>Use this template as a way to quick start any new project.</p>
		</div>
		<div class="pull-right">
			<p>Welcome {{ Auth::user()->firstname }}! <br/> You are logged in to the Dashboard</p>
		</div>
	</div>

@endsection