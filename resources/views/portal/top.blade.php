@php
$games_mybets = $user->get_betted_games();
@endphp

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

  @php
  if( count($games_mybets) > 0 )
  {
  @endphp
    <h3>{{ __("odds.user_my_betted") }}</h3>
    <ul>
      @foreach( $games_mybets as $game )
        <li><a href='{{ url("/game/$game->id") }}'>{{ $game->name }}</a></li>
      @endforeach
    </ul>
  @php
  }
  @endphp

  @include('parts.footer')
</div>
