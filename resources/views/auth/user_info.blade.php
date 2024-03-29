@php
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->first();
@endphp

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
      @php
      if( $message === "" )
      {
        echo __("odds.info_user_register");
      }
      else
      {
        echo $message;
      }
      @endphp
    </div>
    <br>
    <div class="col-md-8 shadow rounded" style="padding: 16px;">
        @php
        if( $user->authorized > 0
         || (!is_null($user->name) && !is_null($user->email)) )
        {
          echo "<form action='" . asset("/update_user") . "' method='POST'>";
          echo "<div class='mb-3 text-start'>";
          echo "<label for='nickname' class='form-label'>" . __("odds.user_nickname") . "</label>";
          echo "<input type='text' class='form-control' id='nickname' name='info_name' value='" . $user->name . "'>";
          echo "</div>";
          echo "<div class='mb-3 text-start'>";
          echo "<label for='email' class='form-label'>" . __("odds.user_email") . "</label>";
          echo "<input type='email' class='form-control' id='email' name='info_email' value='" . $user->email . "'>";
          if( $user->authorized < 3 )
          {
            echo "(" . __('odds.user_not_authorize') . ")";
          }
          echo "</div>";
          echo "<div style='display: flex; justify-content: space-between;'>";
          echo "<div class='text-start'>";
          echo "<input type='button' class='btn btn-info' onclick='if(is_valid_infos()) submit();' value='" . __("odds.user_update") . "'>";
          echo "</div>";
          echo "<div class=''>";
          echo "<input type='button' class='btn btn-info' onclick='change_password();' value='" . __("odds.user_change_password") . "'>";
          echo "</div>";
          echo "<div class='text-end'>";
          echo "<input type='button' class='btn btn-danger' onclick='delete_user();' value='" . __("odds.user_delete") . "'>";
          echo "</div>";
          echo "</div>";
          echo csrf_field();
          echo "</form>";
        }
        else
        {
          echo "<form action='" . asset("/register_user") ."' method='POST'>";
          echo "<div class='mb-3 text-start'>";
          echo "<label for='nickname' class='form-label'>" . __("odds.user_nickname") . "</label>";
          echo "<input type='text' class='form-control' id='nickname' name='info_name'>";
          echo "</div>";
          echo "<div class='mb-3 text-start'>";
          echo "<label for='email' class='form-label'>" . __("odds.user_email") . "</label>";
          echo "<input type='email' class='form-control' id='email' name='info_email'>";
          echo "</div>";
          echo "<div class='mb-3 text-start'>";
          echo "<label for='password' class='form-label'>" . __("odds.user_password") . "</label>";
          echo "<input type='password' class='form-control' id='password' name='info_pass'>";
          echo "</div>";
          echo "<div class='mb-3 text-start'>";
          echo "<label for='confirm' class='form-label'>" . __("odds.user_password") . __("odds.user_password_confirm") . "</label>";
          echo "<input type='password' class='form-control' id='confirm'>";
          echo "</div>";

          echo "<div style='display: flex; justify-content: space-between;'>";
          echo "<div class='text-start'>";
          echo "<input type='button' class='btn btn-info' onclick='if(is_valid_registration()) submit();' value='" . __("odds.user_register") . "'>";
          echo "</div>";
          echo "<div class='text-end'>";
          echo "<input type='button' class='btn btn-info' onclick='signin();' value='" . __("odds.user_signin") . "'>";
          echo "</div>";
          echo "</div>";
          echo csrf_field();
          echo "</form>";
        }
        @endphp
    </div>
    <br>
    <a href='{{ asset("/") }}'>{{ __("odds.back_to_top") }}</a>
  </center>
</div>

<script type="text/javascript">
/**
 *  Check whether the password is valid.
 */
function is_valid_registration()
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

    let elem2 = document.getElementById('email');
    if( !elem2.value.match(/.+@.+\..+/) )
    {
        alert('{{ __("odds.info_incorrect_email") }}'); 
        return false;
    }

    return true;
}

/**
 * Check whether the form has valid inputs.
 */
function is_valid_infos()
{
    let elem2 = document.getElementById('email');
    if( !elem2.value.match(/.+@.+\..+/) )
    {
        alert('{{ __("odds.info_incorrect_email") }}'); 
        return false;
    }

    return true;
}

/**
 * Go to change password
 */
function change_password()
{
  location.href = '{{ asset("/change_password") }}';
}

/**
 *  Delete user
 */
function delete_user()
{
  if( confirm('{{ __("odds.user_confirm_delete") }}') )
  {
    location.href = '{{ asset("/delete_user_info") }}';
  }
}

/**
 *  Signin
 */
function signin()
{
  location.href = '{{ asset("/user_signin") }}';
}

</script>
