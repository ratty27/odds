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

  <div style="width: 100%;">
    @php
    if( $user->CanEditGame() )
    {
    @endphp
    <div class="position-fixed top-10 end-0">
      <div style="float: right;"><a href="/mygames" class="btn btn-info">{{ __('odds.user_mygames') }}</a></div>
    </div>
    @php
    }
    @endphp
  </div>
  <div style="clear: left;"></div>

  <!-- Info -->
  <center><div class="col-md-6 text-start text-danger odds_tips">{!! __('odds.info_top') !!}</div></center>

  @include('parts.footer')
</div>
