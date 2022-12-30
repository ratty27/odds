@php
use Illuminate\Support\Facades\DB;

// User
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->select('id', 'name', 'points')->first();
// Game
$game = App\Models\Game::findOrFail($game_id);
$game->update_odds_if_needs();
// Canddates
$candidates = App\Models\Candidate::where('game_id', $game_id)
  ->orderBy('disp_order', 'asc')
  ->select('id', 'name', 'disp_order', 'result_rank')
  ->get();
// Odds for win
$odds0 = App\Models\Odd::where('game_id', $game_id)->where('type', 0)
  ->select('candidate_id0', 'odds', 'favorite')
  ->get();
// Bets
$bets = App\Models\Bet::where('game_id', $game_id)->where('user_id', $user->id)
  ->orderBy('type', 'asc')->orderBy('candidate_id0', 'asc')->orderBy('candidate_id1', 'asc')->orderBy('candidate_id2', 'asc')
  ->select('id', 'type', 'candidate_id0', 'candidate_id1', 'candidate_id2', 'points', 'payed')
  ->get();

@endphp
<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
</head>
<div class="container">
  @include('parts.header')
  <div style="width: 100%;">
    <div style="float: left"><a href="/" class="btn btn-info">{{ __('odds.game_list') }}</a></div>
    @php
    if( $game->status == 0 )
    {
    @endphp
      <div style="float: right;"><a href="/bet/{{ $game->id }}" class="btn btn-info">{{ __('odds.game_bet') }}</a></div>
    @php
    }
    else
    {
    @endphp
      <div class="btn btn-dark" style="float: right;">{{ __('odds.game_info_closed') }}</div>
    @php
    }
    @endphp
  </div>
  <div style="clear: left;"></div>

  <hr>

  @php
  if( $game->status == 0 )
  {
  @endphp
    <div class="text-end text-info">{!! __("odds.info_odds") !!}</div>
  @php
  }
  @endphp

  <div class="table-responsive">
  	<h3>{{ $game->name }}</h3>

    @php
    if( count($bets) > 0 )
    {
    @endphp
      <h4>{{ __('odds.user_tickers') }}</h4>
      <div class='d-flex flex-row flex-wrap bd-highlight'>
      @foreach($bets as $bet)
        <div class='border shadow-sm odds_ticket' id='bet_ticket_{{ $bet->id }}'></div>
      @endforeach
      </div>
      <hr>
    @php
    }
    @endphp

    <h4>{{ __('odds.bet_win') }}</h4>
    <table class="table table-striped table-bordered">
      <tr>
        <th class="text-center col-md-1">{{ __('odds.candidate_order') }}</th>
        <th class="text-center col-md-6">{{ __('odds.candidate_name') }}</th>
        <th class="text-center col-md-1">{{ __('odds.candidate_odds') }}</th>
        <th class="text-center col-md-1">{{ __('odds.candidate_favorite') }}</th>
        <th class="text-center col-md-1">{{ __('odds.candidate_result') }}</th>
      </tr>
    @foreach($candidates as $candidate)
      <tr>
        <td class="text-center align-middle">{{ $candidate->disp_order+1 }}</td>
        <td class="text-left align-middle" style="padding-left: 20px; padding-right: 20px;">{{ $candidate->name }}</td>
        <td class="text-center align-middle" id="odds_win_{{ $candidate->id }}"></td>
        <td class="text-center align-middle" id="favo_win_{{ $candidate->id }}"></td>
        @php
          if( $candidate->result_rank < 0 )
          {
        @endphp
            <td class="text-center">-</td>
        @php
          }
          else
          {
        @endphp
            <td class="text-center">{{ $candidate->result_rank }}</td>
        @php
          }
        @endphp
        </td>
      </tr>
    @endforeach
    </table>

  </div>

  <hr>

</div>

<script type="text/javascript">
var candidates = <?php echo json_encode($candidates); ?>;
var odds0 = <?php echo json_encode($odds0); ?>;
var bets = <?php echo json_encode($bets); ?>;

function searchCadidate(canid)
{
  for( let i = 0; i < candidates.length; ++i )
  {
    if(candidates[i].id == canid )
    {
      return candidates[i];
    }
  }
  return null;
}

function initValues()
{
  // Odds & Favorites
  for( let i = 0; i < odds0.length; ++i )
  {
    let elem = document.getElementById('odds_win_' + odds0[i].candidate_id0);
    if( elem )
    {
      elem.innerHTML = '' + odds0[i].odds;
    }

    elem = document.getElementById('favo_win_' + odds0[i].candidate_id0);
    if( elem )
    {
      elem.innerHTML = '' + odds0[i].favorite;
    }
  }

  // Bets
  for( let i = 0; i < bets.length; ++i )
  {
    let elem = document.getElementById('bet_ticket_' + bets[i].id);
    if( elem )
    {
      if( bets[i].type == 0 )
      {
        let can0 = searchCadidate( bets[i].candidate_id0 );
        if( can0 != null )
        {
//          elem.innerHTML = '<div class="border border-dark">{{ __("odds.bet_win") }}</div>'
//                         + '[' + (can0.disp_order+1) + '] ' + can0.name + '<br>'
//                         + bets[i].points + 'pt';
          elem.innerHTML = '<table>'
                         +   '<tr>'
                         +     '<td class="border border-dark" rowspan="3"><div style="writing-mode: vertical-rl;">{{ __("odds.bet_win") }}</div></td>'
                         +     '<td colspan="3"></td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td></td>'
                         +     '<td class="border border-dark">' + (can0.disp_order+1) + '</td>'
                         +     '<td>' + can0.name + '</td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td colspan="3"><div class="text-end">' + bets[i].points + 'pt</div></td>'
                         +   '</tr>';
          if( bets[i].payed == 1 && can0.result_rank == 1 )
          {
            elem.classList.remove('odds_ticket');
            elem.classList.add('odds_win_ticket');
          }
        }
      }
    }
  }
}
window.onload = initValues;

</script>
