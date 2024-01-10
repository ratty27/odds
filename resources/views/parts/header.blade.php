<nav class="navbar fixed-top odds_header">
	<div class="col-sm-3 col-md-8 fst-italic odds_left_padding32"><a href="/">{{ __('odds.title') }}</a></div>
	<div class="col-sm-4 col-md-3 text-end" style="color:#fff;">
		<div style="display: inline-block;">{{ __('odds.bet_points') }}:</div>
		<div style="display: inline-block; color:#fff;" id="my_points">{{ $user->get_current_points() }}</div>
	</div>
	<div class="col-sm-1 col-md-1 text-end odds_right_padding32">
		<a style='cursor: pointer;' data-bs-toggle='modal' data-bs-target='#UserSettings'><img src='{{ asset("/img/gear-fill.svg") }}'  alt='{{ __("odds.user_settings") }}'></a>
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

<script src="{{ asset('/js/bootstrap.js') }}"></script>
<script type='text/javascript'>
function onResetUserPoint()
{
	if( confirm('{{ __("odds.user_reset_confirm") }}') )
	{
		location.href = "/reset_user";
	}
}
</script>