<link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
<nav class="navbar odds_header">
	<div class="col-md-2"><a href="/">{{ __('odds.title') }}</a></div>
	<div class="col-md-2">所持ポイント: {{ $user->points }}</div>
</nav>