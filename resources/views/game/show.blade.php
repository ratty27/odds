@php
// User
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->first();
$current_points = $user->get_current_points();
// Game + shared public payload (file cache)
$game = App\Models\Game::findOrFail($game_id);
$game->update_odds_if_needs();
$public = $game->get_public_view_data();
$candidates = $public['candidates'];
$candidate_history = $public['candidate_history'];
$odds0 = $public['odds0'];
$odds1 = $public['odds1'];
$odds2 = $public['odds2'];
$starter_count = $public['starter_count'];
$participant_count = $public['participant_count'];
// Per-user bets (not cached)
$bets = App\Models\Bet::where('game_id', $game_id)->where('user_id', $user->id)
  ->orderBy('type', 'asc')->orderBy('candidate_id0', 'asc')->orderBy('candidate_id1', 'asc')->orderBy('candidate_id2', 'asc')
  ->select('id', 'type', 'candidate_id0', 'candidate_id1', 'candidate_id2', 'points', 'payed')
  ->get();
$win_bet_points = [];
foreach( $bets as $bet )
{
  if( $bet->type == 0 && $bet->payed == 0 )
  {
    $win_bet_points[$bet->candidate_id0] = $bet->points;
  }
}

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
      <a href="/bet/{{ $game->id }}" class="btn btn-info odds-game-bet-cta">
        <span class="odds-game-bet-cta__label">{{ __('odds.game_bet') }}</span>
        <span class="odds-game-bet-cta__arrow" aria-hidden="true">&gt;&gt;&gt;</span>
      </a>
    @else
      <span class="odds-game-status">{{ __('odds.game_info_closed') }}</span>
    @endif
  </div>

  @if( $game->status == 0 )
    <div class="odds-game-tip">{!! __('odds.info_odds') !!}</div>
  @endif

  <section class="odds-game-section">
    <h3 class="odds-game-title">{{ $game->name }}</h3>
    <div class="odds-game-meta">{{ __('odds.game_meta_starters', ['count' => $starter_count]) }} / {{ __('odds.game_meta_participants', ['count' => $participant_count]) }}</div>
    @if( strlen(trim($game->comment)) > 0 )
      <div class="odds-game-comment">{!! str_replace("\n", "<br>", $game->comment) !!}</div>
    @endif
  </section>

  @if( count($bets) > 0 )
  <section class="odds-game-section">
    <h4>{{ __('odds.user_tickers') }}</h4>
    <div class="odds-game-tickets d-flex flex-row flex-wrap">
      @foreach($bets as $bet)
        <div class="odds-ticket-item">
          <div class="border shadow-sm odds_ticket" id="bet_ticket_{{ $bet->id }}"></div>
          <div class="odds-ticket-payoff" id="bet_payoff_{{ $bet->id }}" hidden></div>
        </div>
      @endforeach
    </div>
  </section>
  @endif

  <section class="odds-game-section">
    <div id="caption_win"></div>
    <div class="odds-game-table-wrap">
      <table class="table table-striped table-bordered odds-game-table mb-0">
        <tr>
          @if( $game->status == 0 )
            <th class="text-center odds_textcolor odds-game-bet-col">{{ __('odds.bet_action') }}</th>
          @endif
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_order') }}</th>
          <th class="text-center col-md-5 odds_textcolor">{{ __('odds.candidate_name') }}</th>
          <th class="text-center odds_textcolor">{{ __('odds.candidate_mark') }}</th>
          <th class="text-center odds_textcolor">{{ __('odds.candidate_recent') }}</th>
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_odds') }}</th>
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_favorite') }}</th>
          <th class="text-center col-md-1 odds_textcolor">{{ __('odds.candidate_result') }}</th>
        </tr>
        @foreach($candidates as $candidate)
        @php
          $recent = $candidate_history[$candidate->name] ?? [];
          $ranks = [];
          for( $i = 0; $i < min(3, count($recent)); $i++ )
          {
            $ranks[] = $recent[$i]['result_rank'];
          }
          $recent_text = count($ranks) > 0 ? implode('-', $ranks) : '-';
          $current_win_points = $win_bet_points[$candidate->id] ?? 0;
        @endphp
        @php
          $frame_style = '';
          if( !empty($candidate->frame_color) )
          {
            $frame_style = 'background-color:' . $candidate->frame_color . ';color:' . App\Models\Candidate::contrast_text_color($candidate->frame_color) . ';';
          }
        @endphp
        <tr>
          @if( $game->status == 0 )
            <td class="text-center align-middle odds-game-bet-col">
              <button
                type="button"
                class="btn btn-info btn-sm odds-game-quick-bet"
                data-bet-type="0"
                data-candidate-id0="{{ $candidate->id }}"
                data-candidate-order0="{{ $candidate->disp_order + 1 }}"
                data-candidate-name0="{{ $candidate->name }}"
                data-points="{{ $current_win_points }}"
              >{{ __('odds.bet_action') }}</button>
            </td>
          @endif
          <th class="text-center align-middle odds_number {{ $frame_style !== '' ? 'odds-game-number--framed' : '' }}" @if($frame_style !== '') style="{{ $frame_style }}" @endif>{{ $candidate->disp_order+1 }}</th>
          <td class="align-middle odds_textcolor odds-game-candidate-name">
            <button type="button" class="odds-candidate-history-link" data-name="{{ $candidate->name }}">{{ $candidate->name }}</button>
          </td>
          <td class="text-center align-middle odds_textcolor odds-game-mark" id="mark_win_{{ $candidate->id }}"></td>
          <td class="text-center align-middle odds_textcolor odds-game-recent">{{ $recent_text }}</td>
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

@if( $game->status == 0 )
<div class="modal fade" id="QuickBet" tabindex="-1" aria-labelledby="QuickBetLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ asset('/bet/quick') }}" method="POST" id="quick_bet_form" onsubmit="return checkQuickBet();">
        {{ csrf_field() }}
        <input type="hidden" name="game_id" value="{{ $game->id }}">
        <input type="hidden" name="type" id="quick_bet_type" value="0">
        <input type="hidden" name="candidate_id0" id="quick_bet_candidate_id0" value="">
        <input type="hidden" name="candidate_id1" id="quick_bet_candidate_id1" value="0">
        <div class="modal-header">
          <h4 class="modal-title" id="QuickBetLabel">{{ __('odds.bet_quick_title_win') }}</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="odds-quick-bet-target mb-3" id="quick_bet_target"></div>
          <label for="quick_bet_points" class="form-label">{{ __('odds.bet_points') }}</label>
          <input type="text" inputmode="numeric" class="form-control" id="quick_bet_points" name="points" value="0" autocomplete="off">
          <div class="form-text">{{ __('odds.bet_quick_hint') }}</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('odds.bet_quick_cancel') }}</button>
          <button type="submit" class="btn btn-info">{{ __('odds.bet_quick_submit') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

<script src="{{ asset('/js/odds_util.js?v=' . __('odds.css_ver')) }}"></script>
<script type="text/javascript">
const TXT_POINTS = '{{ __("odds.bet_points") }}';
const TXT_BET_ACTION = '{{ __("odds.bet_action") }}';
const TXT_CANDIDATE_HISTORY = '{{ __("odds.candidate_history_title") }}';
const TXT_BET_QUICK_TARGET = '{{ __("odds.bet_quick_target") }}';
const TXT_BET_QUICK_TARGET_PAIR = '{{ __("odds.bet_quick_target_pair") }}';
const TXT_BET_PAYOFF = '{{ __("odds.bet_payoff") }}';
const TXT_BET_QUICK_TITLE = {
  0: '{{ __("odds.bet_quick_title_win") }}',
  1: '{{ __("odds.bet_quick_title_quinella") }}',
  2: '{{ __("odds.bet_quick_title_exacta") }}'
};
const TXT_USER_POINTS_ARE_MISSING = '{{ __("odds.user_points_are_missing") }}';
const TXT_USER_INVALID_POINTS = '{{ __("odds.user_invalid_points") }}';
const TXT_USER_OVER_LIMIT_POINTS = '{{ __("odds.user_over_limit_points") }}';
const INITIAL_POINTS = {{ $current_points }};
const LIMIT_BET_POINTS = {{ config('odds.limit_bet_points') }};
var candidates = <?php echo json_encode($candidates); ?>;
var odds0 = <?php echo json_encode($odds0); ?>;
var odds1 = <?php echo json_encode($odds1); ?>;
var odds2 = <?php echo json_encode($odds2); ?>;
var bets = <?php echo json_encode($bets); ?>;
var candidateHistory = <?php echo json_encode($candidate_history); ?>;
const QUICK_BET_ENABLED = {{ $game->status == 0 ? 'true' : 'false' }};
var quickBetExistingPoints = 0;

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

function findOdd(oddsList, candidateId0, candidateId1)
{
  for( let i = 0; i < oddsList.length; ++i )
  {
    if( oddsList[i].candidate_id0 != candidateId0 )
    {
      continue;
    }
    if( candidateId1 == null || oddsList[i].candidate_id1 == candidateId1 )
    {
      return oddsList[i];
    }
  }
  return null;
}

function calcBetPayoff(points, odd)
{
  if( !odd )
  {
    return 0;
  }
  return Math.floor(Number(points) * Number(odd.odds));
}

function markWinningTicket(ticketElem, bet, odd)
{
  ticketElem.classList.remove('odds_ticket');
  ticketElem.classList.add('odds_win_ticket');

  let payoffElem = document.getElementById('bet_payoff_' + bet.id);
  if( !payoffElem )
  {
    return;
  }
  let payoff = calcBetPayoff(bet.points, odd);
  payoffElem.textContent = TXT_BET_PAYOFF.replace(':points', String(payoff));
  payoffElem.hidden = false;
}

function formatQuickBetTarget(button)
{
  let type = parseInt(button.getAttribute('data-bet-type'), 10);
  let order0 = button.getAttribute('data-candidate-order0');
  let name0 = button.getAttribute('data-candidate-name0');
  if( type === 0 )
  {
    return TXT_BET_QUICK_TARGET
      .replace(':order', order0)
      .replace(':name', name0);
  }
  return TXT_BET_QUICK_TARGET_PAIR
    .replace(':order0', order0)
    .replace(':name0', name0)
    .replace(':order1', button.getAttribute('data-candidate-order1'))
    .replace(':name1', button.getAttribute('data-candidate-name1'));
}

function openQuickBet(button)
{
  let modalElem = document.getElementById('QuickBet');
  if( !modalElem )
  {
    return;
  }
  let type = parseInt(button.getAttribute('data-bet-type'), 10);
  let existingPoints = Number(button.getAttribute('data-points') || '0');
  if( isNaN(existingPoints) || existingPoints < 0 )
  {
    existingPoints = 0;
  }
  quickBetExistingPoints = existingPoints;
  document.getElementById('quick_bet_type').value = String(type);
  document.getElementById('quick_bet_candidate_id0').value = button.getAttribute('data-candidate-id0');
  document.getElementById('quick_bet_candidate_id1').value = button.getAttribute('data-candidate-id1') || '0';
  document.getElementById('quick_bet_points').value = String(existingPoints);
  document.getElementById('QuickBetLabel').textContent = TXT_BET_QUICK_TITLE[type] || TXT_BET_QUICK_TITLE[0];
  document.getElementById('quick_bet_target').textContent = formatQuickBetTarget(button);
  bootstrap.Modal.getOrCreateInstance(modalElem).show();
  window.setTimeout(function()
  {
    let input = document.getElementById('quick_bet_points');
    input.focus();
    input.select();
  }, 200);
}

function checkQuickBet()
{
  let raw = document.getElementById('quick_bet_points').value;
  if( isNaN(raw) )
  {
    alert(TXT_USER_INVALID_POINTS);
    return false;
  }
  let points = Number(raw);
  if( points < 0 )
  {
    alert(TXT_USER_INVALID_POINTS);
    return false;
  }
  if( points > LIMIT_BET_POINTS )
  {
    alert(TXT_USER_OVER_LIMIT_POINTS + LIMIT_BET_POINTS);
    return false;
  }
  let available = INITIAL_POINTS + quickBetExistingPoints;
  if( points > available )
  {
    alert(TXT_USER_POINTS_ARE_MISSING);
    return false;
  }
  return true;
}

function bindQuickBetButtons(selector)
{
  let buttons = document.querySelectorAll(selector);
  for( let i = 0; i < buttons.length; ++i )
  {
    buttons[i].addEventListener('click', function()
    {
      openQuickBet(this);
    });
  }
}

function applyMatrixQuickBetPoints()
{
  for( let i = 0; i < bets.length; ++i )
  {
    if( bets[i].payed )
    {
      continue;
    }
    if( bets[i].type !== 1 && bets[i].type !== 2 )
    {
      continue;
    }
    let selector = '.odds-game-matrix-bet[data-bet-type="' + bets[i].type + '"]'
                 + '[data-candidate-id0="' + bets[i].candidate_id0 + '"]'
                 + '[data-candidate-id1="' + bets[i].candidate_id1 + '"]';
    let button = document.querySelector(selector);
    if( !button )
    {
      continue;
    }
    button.setAttribute('data-points', String(bets[i].points));
    button.classList.add('odds-game-matrix-bet--active');
    let label = button.querySelector('.odds-game-matrix-bet__label');
    if( label )
    {
      label.textContent = bets[i].points + 'pt';
    }
  }
}

function initValues()
{
  bindCandidateHistoryLinks(candidateHistory, TXT_CANDIDATE_HISTORY);

  if( QUICK_BET_ENABLED )
  {
    bindQuickBetButtons('.odds-game-quick-bet');
  }

  // Odds layout
  layout_odds_caption( "caption_win", "{{ __('odds.bet_win') }}", "{{ __('odds.info_win') }}" );
  @if( $game->is_enabled(1) )
    layout_odds_caption( "caption_quinella", "{{ __('odds.bet_quinella') }}", "{{ __('odds.info_quinella') }}" );
    layout_quinella("odds_quinella", candidates, false, QUICK_BET_ENABLED);
  @endif
  @if( $game->is_enabled(2) )
    layout_odds_caption( "caption_exacta", "{{ __('odds.bet_exacta') }}", "{{ __('odds.info_exacta') }}" );
    layout_exacta("odds_exacta", candidates, false, QUICK_BET_ENABLED);
  @endif

  if( QUICK_BET_ENABLED )
  {
    bindQuickBetButtons('.odds-game-matrix-bet');
    applyMatrixQuickBetPoints();
  }

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

    elem = document.getElementById('mark_win_' + odds0[i].candidate_id0);
    if( elem )
    {
      elem.innerHTML = get_favorite_mark( odds0[i].favorite );
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
            markWinningTicket(elem, bets[i], findOdd(odds0, bets[i].candidate_id0, null));
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
            markWinningTicket(elem, bets[i], findOdd(odds1, bets[i].candidate_id0, bets[i].candidate_id1));
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
            markWinningTicket(elem, bets[i], findOdd(odds2, bets[i].candidate_id0, bets[i].candidate_id1));
          }
        }
      }
    }
  }
}
window.onload = initValues;

</script>
