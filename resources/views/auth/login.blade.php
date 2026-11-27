@php
$turnstile_enabled = App\Odds\Turnstile::is_enabled();
$turnstile_site_key = config('odds.turnstile_site_key');
@endphp
<head>
  <title>{{ __('odds.title') }}</title>
  @include('parts.theme_boot')
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
<style type="text/css">
body {
  height: 100%
}

input[type=checkbox] {
  transform: scale(3);
  margin: 0 6px 0 0;
}

</style>
  @if( $turnstile_enabled )
  <script type="text/javascript">
  function onTurnstileSuccess()
  {
    document.getElementById('login_form').submit();
  }
  </script>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  @endif
</head>

<div class="container">

  <nav class="navbar fixed-top odds_header">
    <div class="col-md-9" style="padding-left: 32px"><a href="/">{{ __('odds.title') }}</a></div>
  </nav>

  <center>
  <div class="h-100 d-flex align-items-center justify-content-center">
    <div class="col-md-7 fs-3 border shadow" style="padding: 24px;">
      {{ __("odds.title") }}
      <hr>
      @if( !empty($message) )
      <div class="fs-5 text-danger mb-3">{{ $message }}</div>
      @endif
      @if( $turnstile_enabled )
      {{ __("odds.dialog_verifying_human") }}
      @else
      {{ __("odds.dialog_confirm_robot") }}
      @endif
      <br>
      <br>
      {{-- GET: production currently registers only GET/HEAD for /login/{token} --}}
      <form id="login_form" action="/login/{{ $token }}" method="GET" accept-charset="UTF-8">
        @if( $turnstile_enabled )
        <div class="d-flex justify-content-center">
          <div class="cf-turnstile" data-sitekey="{{ $turnstile_site_key }}" data-callback="onTurnstileSuccess"></div>
        </div>
        @else
        <input type="checkbox" class="option-input checkbox" id="_checkbox" onclick="document.getElementById('login_form').submit();">
        @endif
      </form>
      　
    </div>
  </div>
  </center>
</div>
