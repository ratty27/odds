<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
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
      <div class="text-center">{{ __("odds.user_input_new_password") }}</div>
      <form action='{{ asset("/input_password") }}' method='POST'>
        <div class='mb-3 text-start'>
          <label for='password' class='form-label'>{{ __("odds.user_password_new") }}</label>
          <input type='password' class='form-control' id='password' name='info_pass'>
        </div>
        <div class='mb-3 text-start'>
          <label for='confirm' class='form-label'>{{ __("odds.user_password") }}{{ __("odds.user_password_confirm") }}</label>
          <input type='password' class='form-control' id='confirm'>
        </div>
        <div class='text-start'>
          <input type='button' class='btn btn-info' onclick='if( is_valid_password() ) submit();' value='{{ __("odds.user_change_password") }}'>
        </div>
        <input type="hidden" name="token" value="{{ $user->temp }}">
        {{ csrf_field() }}
      </form>
    </div>
    <br>
    <a href='{{ asset("/") }}'>{{ __("odds.back_to_top") }}</a>
  </center>
</div>

<script type="text/javascript">
/**
 *  Check whether the password is valid.
 */
function is_valid_password()
{
    let elem0 = document.getElementById('password');
    let elem1 = document.getElementById('confirm');
    if( elem0.value != elem1.value )
    {
        alert('{{ __("odds.info_incorrect_password") }}');
        return false;
    }

    if( elem0.value.length < 8 || elem0.value.length > 16 )
    {
        alert('{{ __("odds.info_password_length") }}');
        return false;
    }

    return true;
}

</script>
