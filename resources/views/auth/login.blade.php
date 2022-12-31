<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
<style type="text/css">
body {
  height: 100%
}

input[type=checkbox] {
  transform: scale(3);
  margin: 0 6px 0 0;
}

</style>
</head>

<div class="container">

  <nav class="navbar fixed-top odds_header">
    <div class="col-md-9" style="padding-left: 32px"><a href="/">{{ __('odds.title') }}</a></div>
  </nav>

  <center>
  <div class="h-100 d-flex align-items-center justify-content-center">
    <div class="col-md-7 fs-3 border shadow">
      {{ __("odds.dialog_title_confirm") }}
      <hr>
      {{ __("odds.dialog_confirm_robot") }}
      <br>
      <br>
      <input type="checkbox" class="option-input checkbox" id="_checkbox" onclick="onClicked('{{ $token }}');">
      <br>
      　
    </div>
  </div>
  </center>
</div>

<script type="text/javascript">
function onClicked(token)
{
  location.href = "/login/" + token;
}
</script>

