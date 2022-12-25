@php
// User
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->take(1)->get()[0];
// Game
$game = App\Models\Game::findOrFail($game_id);
$game->update_odds_if_needs();
// Canddates
$candidates = App\Models\Candidate::where('game_id', $game_id)->orderBy('disp_order', 'asc')->get();
// Odds for win
$odds0 = App\Models\Odd::where('game_id', $game_id)->where('type', 0)->get();
// Bets
$bets = App\Models\Bet::where('game_id', $game_id)->where('user_id', $user->id)->get();
@endphp
<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<div class="container">
  @include('parts.header')
  <div class="table-responsive">
    <form action="/update" method="POST">
	  	<h1>{{ __('odds.bet_win') }}</h1>
	    <table class="table table-striped table-bordered">
				<tr>
	        <th class="text-center col-md-1">{{ __('odds.candidate_order') }}</th>
	        <th class="text-center col-md-6">{{ __('odds.candidate_name') }}</th>
	        <th class="text-center col-md-1">{{ __('odds.candidate_odds') }}</th>
	        <th class="text-center col-md-2">{{ __('odds.bet_points') }}</th>
				</tr>
	    @foreach($candidates as $candidate)
	    	@php
          $disp_odds = 0.0;
          foreach($odds0 as $odd)
          {
            if( $odd->candidate_id0 == $candidate->id )
            {
              $disp_odds = $odd->$odds;
              break;
            }
          }

	    		$bet_points = 0;
	    		foreach($bets as $bet)
	    		{
	    			if( $bet->type == 0
	    			 && $bet->candidate_id0 == $candidate->id )
	    			{
	    				$bet_points = $bet->points;
	    				break;
	    			}
	    		}
	    	@endphp
	      <tr>
	        <td class="text-center">{{ $candidate->disp_order+1 }}</td>
	        <td class="text-left" style="padding-left: 20px; padding-right: 20px;">{{ $candidate->name }}</td>
	        <td class="text-center">{{ $disp_odds }}</td>
	        <td class="text-left">
	        	<input name="bet_win_{{ $candidate->id }}" type='text' class="form-control" value="{{ $bet_points }}">
	        </td>
	      </tr>
	    @endforeach
	  	</table>

	  	<hr>

      <input type="hidden" name="game_id" value="{{ $game_id }}">
      {{ csrf_field() }}
      <button type="submit" class="btn btn-primary">{{ __('odds.game_bet_save') }}</button>
	  </form>
  </div>
</div>

<script type="text/javascript">
</script>
