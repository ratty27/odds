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

@endphp
<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
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

  <div class="table-responsive">
  	<h3>{{ $game->name }}</h3>
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
</div>

<script type="text/javascript">
var candidates = <?php echo json_encode($candidates); ?>;
var odds0 = <?php echo json_encode($odds0); ?>;

function initOddsFavo()
{
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
}
window.onload = initOddsFavo;

</script>
