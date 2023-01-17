<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
</head>
<div class="container">
  @include('parts.header')

  <!-- Info -->
  <center><div class="col-md-6 text-start text-danger odds_tips">{!! __('odds.info_top') !!}</div></center>

  <!-- New Race -->
  <div class="col-md-10 text-end">
    <a href="{{ url('/edit/new') }}" class="btn btn-success">{{ __('odds.game_register') }}</a>
  </div>

</div>
