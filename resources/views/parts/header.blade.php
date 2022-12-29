<link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
<nav class="navbar fixed-top odds_header">
	<div class="col-md-9" style="padding-left: 32px"><a href="/">{{ __('odds.title') }}</a></div>
	<div class="col-md-2">
		<table>
			<tr>
				<td style="color:#fff;">所持ポイント:</td>
				<td style="color:#fff;" id="my_points">{{ $user->get_current_points() }}</td>
			</tr>
		</table>
	</div>
	<div class="col-md-1 text-end" style="padding-right: 32px;">
		<a style='cursor: pointer;' data-bs-toggle='modal' data-bs-target='#UserSettings'><img src='/img/gear-fill.svg' alt='{{ __("odds.user_settings") }}'></a>
	</div>
</nav>
<br>
<br>

<!-- User settings -->
<div class="modal fade" id="UserSettings" tabindex="-1" aria-labelledby="UserSettingsLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ __('odds.user_settings') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="onResetUserPoint();">{{ __('odds.user_reset') }}</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<script type='text/javascript'>
function onResetUserPoint()
{
	if( confirm('{{ __("odds.user_reset_confirm") }}') )
	{
		location.href = "/reset_user";
	}
}
</script>