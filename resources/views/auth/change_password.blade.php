<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
</head>
<div class="container">
  @include('parts.header')
  <br>
  <center>
    <div class="col-md-8 text-start">
      <form action='{{ asset("/change_password") }}' method='POST'>
        <div class='mb-3 text-start'>
          <label for='password' class='form-label'>{{ __("odds.user_password_current") }}</label>
          <input type='password' class='form-control' id='password' name='info_pass'>
        </div>
        <div class='mb-3 text-start'>
          <label for='password2' class='form-label'>{{ __("odds.user_password_new") }}</label>
          <input type='password' class='form-control' id='password2' name='info_new_pass'>
        </div>
        <div class='mb-3 text-start'>
          <label for='password3' class='form-label'>{{ __("odds.user_password_new") }}{{ __("odds.user_password_confirm") }}</label>
          <input type='password' class='form-control' id='password3'>
        </div>
        <div class='text-start'>
          <input type='button' class='btn btn-info' onclick='if(is_valid_pass()) submit();' value='{{ __("odds.user_change_password") }}'>
        </div>
        {{ csrf_field() }}
      </form>
    </div>
    <br>
    <a href='{{ asset("/") }}'>{{ __("odds.back_to_top") }}</a>
  </center>
</div>

<script type="text/javascript">

/**
 *  Check whether the password is valid
 */
function is_valid_pass()
{
    let elem0 = document.getElementById('password2');
    let elem1 = document.getElementById('password3');
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
