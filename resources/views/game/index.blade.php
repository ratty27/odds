@php
$games = App\Models\Game::where('status', '<', 2)->where('user_id', $game_user)->orderBy('id', 'asc')->select('id', 'name', 'limit', 'status')->get();
$past_games = App\Models\Game::where('status', 2)->where('user_id', $game_user)->orderBy('id', 'desc')->limit(config('odds.past_game_count'))->select('id', 'name')->get();
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

  @php
  if( $user->id == $game_user )
  {
  @endphp
  <div class='text-start'>
    {{ __("odds.user_url") }}
    <input type='text' class='col-md-4' id='url_text' value='{{ url("/usergames/$user->id") }}' readonly>
    <button onclick='copy_url()'>{{ __("odds.user_copy") }}</button>
  </div>
  @php
  }
  @endphp
  <br>

  <!-- Future games -->
  <h3>{{ __('odds.game_future') }}</h3>
  <table class="table text-center table-striped table-bordered">
    <tr>
      <th class="text-center col-md-1">{{ __('odds.game_id') }}</th>
      <th class="text-center col-md-6">{{ __('odds.game_name') }}</th>
      <th class="text-center col-md-1">{{ __('odds.game_limit') }}</th>
      @php
      if( $user->id == $game_user )
      {
      @endphp
        <th class='text-center col-md-2'>{{ __('odds.admin') }}</th>
      @php
      }
      @endphp
    </tr>
    @foreach($games as $game)
      <tr>
        <td class="align-middle">{{ $game->id }}</td>
        <td class="align-middle">
          <a href="/game/{{ $game->id }}">{{ $game->name }}</a>
        </td>
        <td class="align-middle">{{ $game->status == 0 ? $game->limit : __('odds.game_limit_close') }}</td>
        @php
        if( $user->id == $game_user )
        {
        @endphp
          <td class='text-center align-middle'>
            <!-- Edit button -->
            <a class='btn btn-info' href='/edit/{{ $game->id }}'>{{ __('odds.admin_edit') }}</a>
            <!-- Close/Reopen button -->
            @php
              if( $game->status == 0 )
              {
                echo "<a class='btn btn-info' href='/close/" . $game->id . "'>" . __('odds.admin_close') . "</a> ";
              }
              else if( $game->status == 1 )
              {
                echo "<a class='btn btn-info' href='/reopen/" . $game->id . "'>" . __('odds.admin_reopen') . "</a> ";
              }
            @endphp
            <!-- Result button -->
            <a class='btn btn-info' href='/result/{{ $game->id }}'>{{ __('odds.admin_result') }}</a>
          </td>
        @php
        }
        @endphp
      </tr>
    @endforeach

    @php
    if( $user->id == $game_user )
    {
    @endphp
      <tr>
        <td class='text-start' colspan='4'><a href='/edit/new' class='btn btn-info'>{{ __('odds.game_register') }}</a></td>
      </tr>
    @php
    }
    @endphp
  </table>

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
        <td class="align-middle">
          <a href="/game/{{ $game->id }}">{{ $game->name }}</a>
        </td>
        <td class="align-middle">{{ __('odds.game_limit_close') }}</td>
      </tr>
    @endforeach
  </table>

</div>

<script type="text/javascript">
function copy_url()
{
  let elem = document.getElementById('url_text');
  if( elem )
  {
    elem.select();
    document.execCommand('Copy');
  }
}

</script>
