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
  <div class="table-responsive">
  	<h1>{{ $game->name }}</h1>
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
        @php
          $disp_odds = 0.0;
          $disp_favo = 0;
          foreach($odds0 as $odd)
          {
            if( $odd->candidate_id0 == $candidate->id )
            {
              $disp_odds = $odd->$odds;
              $disp_favo = $odd->$favorite;
              break;
            }
          }
        @endphp
        <td class="text-center align-middle">{{ $candidate->disp_order+1 }}</td>
        <td class="text-left align-middle" style="padding-left: 20px; padding-right: 20px;">{{ $candidate->name }}</td>
        <td class="text-center align-middle">{{ $disp_odds }}</td>
        <td class="text-center align-middle">{{ $disp_favo }}</td>
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
            <td class="text-right">{{ $candidate->result_rank }}</td>
        @php
          }
        @endphp
        </td>
      </tr>
    @endforeach
    </table>

    <div class="text-end">
      <a href="/bet/{{ $game->id }}" class="btn btn-info">{{ __('odds.game_bet') }}</a>
    </div>
  </div>
</div>

<script type="text/javascript">
  var userInfo = <?php echo json_encode($user); ?>;
  var oddsInfo = <?php echo json_encode($odds0); ?>;
</script>
