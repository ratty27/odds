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
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
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
  <br>

  @php
  if( $user->admin )
  {
    if( $app_exists )
    {
      echo "<a class='btn btn-info' href='" . url("/admin_app") . "'>" . __("odds.admin_applications") . "</a>";
      echo "<br>";
    }
    echo "<a class='btn btn-info' href='" . url("/admin_edit_info") . "'>" . __("odds.admin_edit_info") . "</a>";
    echo "<br>";
    echo "<br>";
  }
  @endphp

  <!-- Informations -->
  @php
  if( count($infos) > 0 )
  {
    echo "<h3>" . __("odds.info_infos") . "</h3><ul>";
    foreach( $infos as $info )
    {
      echo "<li>" . $info->message . "</li>";
    }
    echo "</ul>";
    echo "<br>";
  }
  @endphp

  <!-- About this site -->
  <h3>{{ __("odds.info_about_title") }}</h3>
  <div>{!! __("odds.info_about_desc") !!}</div>
  @php
  if( $user->authorized < 3 )
  {
    echo "<a class='btn btn-primary' href='" . url("/user_info") . "'>" . __("odds.user_register") . "</a>　";
    echo "<a class='btn btn-primary' href='" . url("/user_signin") . "'>" . __("odds.user_signin") . "</a>";
  }
  else
  {
    echo "<a class='btn btn-primary' href='" . url("/user_info") . "'>" . __("odds.user_info") . "</a>　";
  }
  @endphp
  <br>
  <br>

  <!-- Betted games -->
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

  <!-- Past games -->
  @php
  if( count($games_mypast) > 0 )
  {
  @endphp
    <h3>{{ __("odds.game_past") }}</h3>
    <ul>
      @foreach( $games_mypast as $game )
        <li><a href='{{ url("/game/$game->id") }}'>{{ $game->name }}</a></li>
      @endforeach
    </ul>
  @php
  }
  @endphp

  <!-- Featured games -->
  @php
  if( count($games_favorite) > 0 )
  {
  @endphp
    <h3>{{ __("odds.user_favorite_games") }}</h3>
    <ul>
      @foreach( $games_favorite as $game )
        <li><a href='{{ url("/game/$game->id") }}'>{{ $game->name }}</a></li>
      @endforeach
    </ul>
  @php
  }
  @endphp

  <!-- Behinds of footer -->
  <br>
  <br>
  <br>
  <br>

  <!-- Footer -->
  @include('parts.footer')
</div>
