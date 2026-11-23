<nav class="navbar fixed-top odds_header">
	<div class="col-sm-3 col-md-8 fst-italic odds_left_padding32"><a href="/">{{ __('odds.title') }}</a></div>
	<div class="col-sm-4 col-md-3 text-end odds_header_points">
		<div style="display: inline-block;">{{ __('odds.bet_points') }}:</div>
		<div style="display: inline-block;" id="my_points">{{ $user->get_current_points() }}</div>
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
            <div class="modal-body">
                <div class="mb-1">{{ __('odds.user_theme') }}</div>
                <div class="d-flex align-items-center gap-2">
                    <span>{{ __('odds.theme_light') }}</span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="themeSwitch" aria-label="{{ __('odds.user_theme') }}">
                    </div>
                    <span>{{ __('odds.theme_dark') }}</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="onResetUserPoint();">{{ __('odds.user_reset') }}</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('/js/bootstrap.js') }}"></script>
<script type='text/javascript'>
function getOddsTheme()
{
	try {
		var theme = localStorage.getItem('odds_theme');
		if (theme === 'dark' || theme === 'light') {
			return theme;
		}
	} catch (e) {
	}
	return 'light';
}

function applyOddsTheme(theme)
{
	if (theme !== 'dark' && theme !== 'light') {
		theme = 'light';
	}
	document.documentElement.setAttribute('data-theme', theme);
	try {
		localStorage.setItem('odds_theme', theme);
	} catch (e) {
	}
	var themeSwitch = document.getElementById('themeSwitch');
	if (themeSwitch) {
		themeSwitch.checked = (theme === 'dark');
	}
}

function onResetUserPoint()
{
	if( confirm('{{ __("odds.user_reset_confirm") }}') )
	{
		location.href = "/reset_user";
	}
}

(function () {
	var themeSwitch = document.getElementById('themeSwitch');
	if (!themeSwitch) {
		return;
	}
	applyOddsTheme(getOddsTheme());
	themeSwitch.addEventListener('change', function () {
		applyOddsTheme(themeSwitch.checked ? 'dark' : 'light');
	});
})();
</script>
