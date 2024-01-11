@php
$past_games = App\Models\Game::where('status', 2)->where('user_id', $game_user)->orderBy('id', 'desc')->select('id', 'name')->get();
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

  <!-- Back button -->
  <div style="width: 100%;">
    <div class="position-fixed top-10 start-0">
      <div style="float: left">
        @php
        if( $game_user == $user->id )
        {
          echo "<a href='/mygames' class='btn btn-info'>" . __('odds.game_list') . "</a>";
        }
        else
        {
          echo "<a href='/usergames/$game_user' class='btn btn-info'>" . __('odds.game_list') . "</a>";
        }
        @endphp
      </div>
    </div>
  </div>
  <div style="clear: left;"></div>

  <br>
  <br>

  <!-- Past games -->
  <h3>{{ __('odds.game_past') }}</h3>
  <table class="table text-center table-striped table-bordered">
    <tr>
      <th class="text-center col-md-1">{{ __('odds.game_id') }}</th>
      <th class="text-center col-md-6">{{ __('odds.game_name') }}</th>
      <th class="text-center col-md-1">{{ __('odds.game_limit') }}</th>
    </tr>
    @foreach($past_games as $game)
      <tr>
        <td class="align-middle">{{ $game->id }}</td>
        <td class="align-middle text-start">
          <a href="/game/{{ $game->id }}" class="odds_link widelink">{{ $game->name }}</a>
        </td>
        <td class="align-middle">{{ __('odds.game_limit_close') }}</td>
      </tr>
    @endforeach
  </table>
</div>
