<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
</head>
<div class="container">

  <nav class="navbar fixed-top odds_header">
    <div class="col-md-9" style="padding-left: 32px"><a href="/">{{ __('odds.title') }}</a></div>
  </nav>

  <br>
  <br>
  <br>
  <center>
    <div class="col-md-8 text-start">{{ $message }}</div>
    <div class="col-md-8 text-start shadow rounded" style="padding: 16px;">
      <form action='{{ asset("/user_signin") }}' method='POST'>
        <div class='mb-3 text-start'>
          <label for='email' class='form-label'>{{ __("odds.user_email") }}</label>
          <input type='email' class='form-control' id='email' name='info_email'>
        </div>
        <div class='mb-3 text-start'>
          <label for='password' class='form-label'>{{ __("odds.user_password_current") }}</label>
          <input type='password' class='form-control' id='password' name='info_pass'>
        </div>
        <div class='text-start'>
          <input type='button' class='btn btn-info' onclick='submit();' value='{{ __("odds.user_signin") }}'>
        </div>
        {{ csrf_field() }}
      </form>
    </div>
    <br>
    <a href='{{ asset("/") }}'>{{ __("odds.back_to_top") }}</a>
  </center>
</div>
