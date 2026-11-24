@php
$games_mybets = $user->get_betted_games(true, 10);
$games_mypast = $user->get_betted_games(false, 5);
$games_favorite = App\Models\Game::get_favorite_games(10);

$infos = App\Models\Info::orderBy('created_at', 'desc')->take(3)->select('message')->get();

$app_exists = false;
if( $user->admin )
{
  $app_exists = App\Models\Game::where('is_public', 1)->exists();
}

@endphp

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  @include('parts.theme_boot')
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
</head>
<div class="container odds-portal">
  @include('parts.header')

  @if( $user->CanEditGame() || $user->admin )
  <div class="odds-portal-actions">
    @if( $user->CanEditGame() )
      <a href="/mygames" class="btn btn-info">{{ __('odds.user_mygames') }}</a>
    @endif
    @if( $user->admin )
      @if( $app_exists )
        <a class="btn btn-info" href="{{ url('/admin_app') }}">{{ __('odds.admin_applications') }}</a>
      @endif
      <a class="btn btn-info" href="{{ url('/admin_edit_info') }}">{{ __('odds.admin_edit_info') }}</a>
    @endif
  </div>
  @endif

  @if( count($infos) > 0 )
  <section class="odds-portal-section">
    <h3>{{ __('odds.info_infos') }}</h3>
    <ul class="odds-portal-info-list">
      @foreach( $infos as $info )
        <li>{{ $info->message }}</li>
      @endforeach
    </ul>
  </section>
  @endif

  <section class="odds-portal-section odds-portal-about">
    <h3>{{ __('odds.info_about_title') }}</h3>
    <div class="odds-portal-about-body">{!! __('odds.info_about_desc') !!}</div>
    <div class="odds-portal-cta">
      @if( $user->authorized < 3 )
        <a class="btn btn-primary" href="{{ url('/user_info') }}">{{ __('odds.user_register') }}</a>
        <a class="btn btn-primary" href="{{ url('/user_signin') }}">{{ __('odds.user_signin') }}</a>
      @else
        <a class="btn btn-primary" href="{{ url('/user_info') }}">{{ __('odds.user_info') }}</a>
      @endif
    </div>
  </section>

  @if( count($games_mybets) > 0 )
  <section class="odds-portal-section">
    <h3>{{ __('odds.user_my_betted') }}</h3>
    <ul class="odds-portal-game-list">
      @foreach( $games_mybets as $game )
        <li>
          <a href="{{ url("/game/$game->id") }}">{{ $game->name }}</a>
        </li>
      @endforeach
    </ul>
  </section>
  @endif

  @if( count($games_mypast) > 0 )
  <section class="odds-portal-section">
    <h3>{{ __('odds.game_past') }}</h3>
    <ul class="odds-portal-game-list">
      @foreach( $games_mypast as $game )
        <li>
          <a href="{{ url("/game/$game->id") }}">{{ $game->name }}</a>
        </li>
      @endforeach
    </ul>
  </section>
  @endif

  @if( count($games_favorite) > 0 )
  <section class="odds-portal-section">
    <h3>{{ __('odds.user_favorite_games') }}</h3>
    <ul class="odds-portal-game-list">
      @foreach( $games_favorite as $game )
        <li>
          <a href="{{ url("/game/$game->id") }}">{{ $game->name }}</a>
        </li>
      @endforeach
    </ul>
  </section>
  @endif

  <div class="odds-portal-footer-spacer"></div>
  @include('parts.footer')
</div>
