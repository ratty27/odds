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
// Past race results for candidates (same organizer, finished games)
$candidate_history = App\Models\Candidate::get_past_results(
  $game->user_id,
  $candidates->pluck('name')->all(),
  $game->id
);
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
  @include('parts.theme_boot')
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
</head>
<div class="container odds-game">
  @include('parts.header')

  <div class="odds-game-actions">
    @if( $game->user_id == $user->id )
      <a href="/mygames" class="btn btn-info">{{ __('odds.game_list') }}</a>
    @else
      <a href="/usergames/{{ $game->user_id }}" class="btn btn-info">{{ __('odds.game_list') }}</a>
    @endif

    @if( $game->status == 0 )
      <a href="/bet/{{ $game->id }}" class="btn btn-info">{{ __('odds.game_bet') }}</a>
    @else
      <span class="odds-game-status">{{ __('odds.game_info_closed') }}</span>
    @endif
  </div>

  @if( $game->status == 0 )
    <div class="odds-game-tip">{!! __('odds.info_odds') !!}</div>
  @endif

  <section class="odds-game-section">
    <h3 class="odds-game-title">{{ $game->name }}</h3>
    @if( strlen(trim($game->comment)) > 0 )
      <div class="odds-game-comment">{!! str_replace("\n", "<br>", $game->comment) !!}</div>
    @endif
  </section>

  @if( count($bets) > 0 )
  <section class="odds-game-section">
    <h4>{{ __('odds.user_tickers') }}</h4>
    <div class="odds-game-tickets d-flex flex-row flex-wrap">
      @foreach($bets as $bet)
        <div class="border shadow-sm odds_ticket" id="bet_ticket_{{ $bet->id }}"></div>
      @endforeach
    </div>
  </section>
  @endif

  <section class="odds-game-section">
    <div id="caption_win"></div>
    <div class="odds-game-table-wrap">
      <table class="table table-striped table-bordered odds-game-table mb-0">
        <tr>
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_order') }}</th>
          <th class="text-center col-md-6 odds_textcolor">{{ __('odds.candidate_name') }}</th>
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_odds') }}</th>
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_favorite') }}</th>
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_result') }}</th>
        </tr>
        @foreach($candidates as $candidate)
        <tr>
          <th class="text-center align-middle odds_number">{{ $candidate->disp_order+1 }}</th>
          <td class="align-middle odds_textcolor odds-game-candidate-name">
            <button type="button" class="odds-candidate-history-link" data-name="{{ $candidate->name }}">{{ $candidate->name }}</button>
          </td>
          <td class="text-center align-middle odds_textcolor" id="odds_win_{{ $candidate->id }}"></td>
          <td class="text-center align-middle odds_textcolor" id="favo_win_{{ $candidate->id }}"></td>
          @if( $candidate->result_rank < 0 )
            <td class="text-center align-middle odds_textcolor">-</td>
          @else
            <td class="text-center align-middle odds_textcolor">{{ $candidate->result_rank }}</td>
          @endif
        </tr>
        @endforeach
      </table>
    </div>
  </section>

  @if( $game->is_enabled(1) )
  <section class="odds-game-section">
    <div id="caption_quinella"></div>
    <div id="odds_quinella" class="odds-game-matrix-host"></div>
  </section>
  @endif

  @if( $game->is_enabled(2) )
  <section class="odds-game-section">
    <div id="caption_exacta"></div>
    <div id="odds_exacta" class="odds-game-matrix-host"></div>
  </section>
  @endif

</div>

@include('parts.candidate_history')

<script src="{{ asset('/js/odds_util.js?v=' . __('odds.css_ver')) }}"></script>
<script type="text/javascript">
const TXT_POINTS = '{{ __("odds.bet_points") }}';
const TXT_CANDIDATE_HISTORY = '{{ __("odds.candidate_history_title") }}';
var candidates = <?php echo json_encode($candidates); ?>;
var odds0 = <?php echo json_encode($odds0); ?>;
var odds1 = <?php echo json_encode($odds1); ?>;
var odds2 = <?php echo json_encode($odds2); ?>;
var bets = <?php echo json_encode($bets); ?>;
var candidateHistory = <?php echo json_encode($candidate_history); ?>;

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
  bindCandidateHistoryLinks(candidateHistory, TXT_CANDIDATE_HISTORY);

  // Odds layout
  layout_odds_caption( "caption_win", "{{ __('odds.bet_win') }}", "{{ __('odds.info_win') }}" );
  @if( $game->is_enabled(1) )
    layout_odds_caption( "caption_quinella", "{{ __('odds.bet_quinella') }}", "{{ __('odds.info_quinella') }}" );
    layout_quinella("odds_quinella", candidates, false);
  @endif
  @if( $game->is_enabled(2) )
    layout_odds_caption( "caption_exacta", "{{ __('odds.bet_exacta') }}", "{{ __('odds.info_exacta') }}" );
    layout_exacta("odds_exacta", candidates, false);
  @endif

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
  @if( $game->is_enabled(1) )
    for( let i = 0; i < odds1.length; ++i )
    {
      let elem = document.getElementById('odds_quinella_' + odds1[i].candidate_id0 + '_' + odds1[i].candidate_id1);
      if( elem )
      {
        elem.innerHTML = get_disp_odds( odds1[i].odds );
      }
    }
  @endif
  // exacta
  @if( $game->is_enabled(2) )
    for( let i = 0; i < odds2.length; ++i )
    {
      let elem = document.getElementById('odds_exacta_' + odds2[i].candidate_id0 + '_' + odds2[i].candidate_id1);
      if( elem )
      {
        elem.innerHTML = get_disp_odds( odds2[i].odds );
      }
    }
  @endif

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
