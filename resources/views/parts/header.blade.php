<link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
<nav class="navbar odds_header">
	<div class="col-md-2"><a href="/">{{ __('odds.title') }}</a></div>
	<div class="col-md-2">
		<table>
			<tr>
				<td style="color:#fff;">所持ポイント:</td>
				<td style="color:#fff;" id="my_points">{{ $user->get_current_points() }}</td>
			</tr>
		</table>
	</div>
</nav>