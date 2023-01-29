@php
$games_mybets = $user->get_betted_games(true);
$games_mypast = $user->get_betted_games(false);
$games_favorite = App\Models\Game::get_favorite_games(10);

$infos = App\Models\Info::orderBy('created_at', 'desc')->take(3)->select('message')->get();

$applications = array();
if( $user->admin )
{
  $applications = App\Models\Game::where('is_public', 1)->get();
}

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
  <br>

  @php
  if( $user->admin )
  {
    if( count($applications) > 0 )
    {
      echo "<a class='btn btn-info' href='" . url("/admin_app") . "'>" . __("odds.admin_applications") . "</a>";
      echo "<br>";
    }
    echo "<a class='btn btn-info' href='" . url("/admin_edit_info") . "'>" . __("odds.admin_edit_info") . "</a>";
    echo "<br>";
    echo "<br>";
  }
  @endphp

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

  <!-- Info -->
  <h3>{{ __("odds.info_about_title") }}</h3>
  <div>{!! __("odds.info_about_desc") !!}</div>
  <br>

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

  @include('parts.footer')
</div>


<script type="text/javascript">
const pasts = {{ $games_mypast }};
</script>