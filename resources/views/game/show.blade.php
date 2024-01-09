@php
use Illuminate\Support\Facades\DB;

// User
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->first();
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
// Odds for quinella
$odds1 = App\Models\Odd::where('game_id', $game_id)->where('type', 1)
  ->select('candidate_id0', 'candidate_id1', 'odds')
  ->get();
// Odds for exacta
$odds2 = App\Models\Odd::where('game_id', $game_id)->where('type', 2)
  ->select('candidate_id0', 'candidate_id1', 'odds')
  ->get();
// Bets
$bets = App\Models\Bet::where('game_id', $game_id)->where('user_id', $user->id)
  ->orderBy('type', 'asc')->orderBy('candidate_id0', 'asc')->orderBy('candidate_id1', 'asc')->orderBy('candidate_id2', 'asc')
  ->select('id', 'type', 'candidate_id0', 'candidate_id1', 'candidate_id2', 'points', 'payed')
  ->get();

@endphp
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
</head>
<div class="container">
  @include('parts.header')
  <div style="width: 100%;">
    <div class="position-fixed top-10 start-0">
      <div style="float: left">
        @php
        if( $game->user_id == $user->id )
        {
          echo "<a href='/mygames' class='btn btn-info'>" . __('odds.game_list') . "</a>";
        }
        else
        {
          echo "<a href='/usergames/$game->user_id' class='btn btn-info'>" . __('odds.game_list') . "</a>";
        }
        @endphp
      </div>
    </div>
    <div class="position-fixed top-10 end-0">
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
  </div>
  <div style="clear: left;"></div>

  <hr>

  @php
  if( $game->status == 0 )
  {
  @endphp
    <div class="text-end text-info odds_tips">{!! __("odds.info_odds") !!}</div>
  @php
  }
  @endphp

  	<h3>{{ $game->name }}</h3>
    <div class="text-end">{!! str_replace("\n", "<br>", $game->comment) !!}</div>

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

    <div id="caption_win"></div>
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
        <th class="text-center align-middle odds_number">{{ $candidate->disp_order+1 }}</th>
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

    <hr>

    @php
    if( $game->is_enabled(1) )
    {
      echo '<div id="caption_quinella"></div>';
      echo '<div id="odds_quinella"></div>';
    }

    if( $game->is_enabled(2) )
    {
      echo '<div id="caption_exacta"></div>';
      echo '<div id="odds_exacta"></div>';
    }
    @endphp

  <hr>

</div>

<script src="{{ asset('/js/odds_util.js') }}"></script>
<script type="text/javascript">
const TXT_POINTS = '{{ __("odds.bet_points") }}';
var candidates = <?php echo json_encode($candidates); ?>;
var odds0 = <?php echo json_encode($odds0); ?>;
var odds1 = <?php echo json_encode($odds1); ?>;
var odds2 = <?php echo json_encode($odds2); ?>;
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
  // Odds layout
  layout_odds_caption( "caption_win", "{{ __('odds.bet_win') }}", "{{ __('odds.info_win') }}" );
  @php
  if( $game->is_enabled(1) )
  {
  @endphp
    layout_odds_caption( "caption_quinella", "{{ __('odds.bet_quinella') }}", "{{ __('odds.info_quinella') }}" );
    layout_quinella("odds_quinella", candidates, false);
  @php
  }
  @endphp
  @php
  if( $game->is_enabled(2) )
  {
  @endphp
    layout_odds_caption( "caption_exacta", "{{ __('odds.bet_exacta') }}", "{{ __('odds.info_exacta') }}" );
    layout_exacta("odds_exacta", candidates, false);
  @php
  }
  @endphp

  // Odds & Favorites
  // win
  for( let i = 0; i < odds0.length; ++i )
  {
    let elem = document.getElementById('odds_win_' + odds0[i].candidate_id0);
    if( elem )
    {
      elem.innerHTML = get_disp_odds( odds0[i].odds );
    }

    elem = document.getElementById('favo_win_' + odds0[i].candidate_id0);
    if( elem )
    {
      elem.innerHTML = '' + odds0[i].favorite;
    }
  }
  // quinella
  @php
  if( $game->is_enabled(1) )
  {
  @endphp
    for( let i = 0; i < odds1.length; ++i )
    {
      let elem = document.getElementById('odds_quinella_' + odds1[i].candidate_id0 + '_' + odds1[i].candidate_id1);
      if( elem )
      {
        elem.innerHTML = get_disp_odds( odds1[i].odds );
      }
    }
  @php
  }
  @endphp
  // exacta
  @php
  if( $game->is_enabled(2) )
  {
  @endphp
    for( let i = 0; i < odds2.length; ++i )
    {
      let elem = document.getElementById('odds_exacta_' + odds2[i].candidate_id0 + '_' + odds2[i].candidate_id1);
      if( elem )
      {
        elem.innerHTML = get_disp_odds( odds2[i].odds );
      }
    }
  @php
  }
  @endphp

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
          elem.innerHTML = '<table>'
                         +   '<tr>'
                         +     '<td class="border border-dark" rowspan="4"><div class="odds_vertical_rect">{{ __("odds.bet_win") }}</div></td>'
                         +     '<td colspan="3"></td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td></td>'
                         +     '<td class="border border-dark fw-bold text-center">' + (can0.disp_order+1) + '</td>'
                         +     '<td>' + can0.name + '</td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td>　</td>'
                         +     '<td>　</td>'
                         +     '<td>　</td>'
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
      else if( bets[i].type == 1 )
      {
        let can0 = searchCadidate( bets[i].candidate_id0 );
        let can1 = searchCadidate( bets[i].candidate_id1 );
        if( can0 != null && can1 != null )
        {
          if( can0.disp_order > can1.disp_order )
          {
            let tmp = can0;
            can0 = can1;
            can1 = tmp;
          } 
          elem.innerHTML = '<table>'
                         +   '<tr>'
                         +     '<td class="border border-dark" rowspan="4"><div class="odds_vertical_rect">{{ __("odds.bet_quinella") }}</div></td>'
                         +     '<td colspan="3"></td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td>　</td>'
                         +     '<td class="border border-dark fw-bold text-center">' + (can0.disp_order+1) + '</td>'
                         +     '<td>' + can0.name + '</td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td>　</td>'
                         +     '<td class="border border-dark fw-bold text-center">' + (can1.disp_order+1) + '</td>'
                         +     '<td>' + can1.name + '</td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td colspan="3"><div class="text-end">' + bets[i].points + 'pt</div></td>'
                         +   '</tr>';
          if( bets[i].payed == 1
           && ( (can0.result_rank == 1 && can1.result_rank == 2)
             || (can0.result_rank == 2 && can1.result_rank == 1)
             || (can0.result_rank == 1 && can1.result_rank == 1) ) )  // arrive at the same time
          {
            elem.classList.remove('odds_ticket');
            elem.classList.add('odds_win_ticket');
          }
        }
      }
      else if( bets[i].type == 2 )
      {
        let can0 = searchCadidate( bets[i].candidate_id0 );
        let can1 = searchCadidate( bets[i].candidate_id1 );
        if( can0 != null && can1 != null )
        {
          elem.innerHTML = '<table>'
                         +   '<tr>'
                         +     '<td class="border border-dark" rowspan="4"><div class="odds_vertical_rect">{{ __("odds.bet_exacta") }}</div></td>'
                         +     '<td colspan="3"></td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td>　</td>'
                         +     '<td class="border border-dark fw-bold text-center">' + (can0.disp_order+1) + '</td>'
                         +     '<td>' + can0.name + '</td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td>　</td>'
                         +     '<td class="border border-dark fw-bold text-center">' + (can1.disp_order+1) + '</td>'
                         +     '<td>' + can1.name + '</td>'
                         +   '</tr>'
                         +   '<tr>'
                         +     '<td colspan="3"><div class="text-end">' + bets[i].points + 'pt</div></td>'
                         +   '</tr>';
          if( bets[i].payed == 1
           && ( (can0.result_rank == 1 && can1.result_rank == 2)
             || (can0.result_rank == 1 && can1.result_rank == 1) ) )  // arrive at the same time
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
