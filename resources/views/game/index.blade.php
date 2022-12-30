@php
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->take(1)->get()[0];
$games = App\Models\Game::where('status', '<', 2)->select('id', 'name', 'limit', 'status')->get();
$past_games = App\Models\Game::where('status', 2)->select('id', 'name')->get();
@endphp

<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
</head>
<div class="container">
  @include('parts.header')

  <!-- Info -->
  <center><div class="col-md-6 text-start text-danger" style="font-size: 0.6rem;">{!! __('odds.info_top') !!}</div></center>

  <!-- Future games -->
  <h3>{{ __('odds.game_future') }}</h3>
  <table class="table text-center table-striped table-bordered">
    <tr>
      <th class="text-center col-md-1">{{ __('odds.game_id') }}</th>
      <th class="text-center col-md-6">{{ __('odds.game_name') }}</th>
      <th class="text-center col-md-1">{{ __('odds.game_limit') }}</th>
      @php
      if( $user->admin )
      {
        echo "<th class='text-center col-md-2'>" . __('odds.admin') . "</th>";
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
        if( $user->admin )
        {
          echo "<td class='text-center align-middle'>";
            // Edit button
            echo "<a class='btn btn-info' href='/edit/$game->id'>" . __('odds.admin_edit') . "</a> ";

            // Close/Reopen button
            if( $game->status == 0 )
            {
              echo "<a class='btn btn-info' href='/close/$game->id'>" . __('odds.admin_close') . "</a> ";
            }
            else if( $game->status == 1 )
            {
              echo "<a class='btn btn-info' href='/reopen/$game->id'>" . __('odds.admin_reopen') . "</a> ";
            }

            // Result buttion
            echo "<a class='btn btn-info' href='/result/$game->id'>" . __('odds.admin_result') . "</a> ";
          echo "</td>";
        }
        @endphp
      </tr>
    @endforeach
    @php
    if( $user->admin )
    {
      echo "<tr>";
      echo "<td><a href='/edit/new' class='btn btn-info'>" . __('odds.admin_add') ."</a></td>";
      echo "</tr>";
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
@include('parts.footer')

