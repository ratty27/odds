<center>
	{{ __("odds.email_confirm_message") }}
	<form action='{{ asset("/authorize_email") }}' method='POST'>
		<input type="hidden" name="iden_token" value="{{ $token }}">
		<input type="hidden" name="temp" value="{{ $temp }}">
		{{ csrf_field() }}
		<input type='button' value='{{ __("odds.email_confirm") }}'>
	</form>
</center>