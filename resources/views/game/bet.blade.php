@php
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
	->select('type', 'candidate_id0', 'candidate_id1', 'candidate_id2', 'points', 'payed')
	->get();

@endphp

<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<div class="container">
  @include('parts.header')
  <div class="table-responsive">
    <form action="/bet" method="POST">
      <input type="button" class="btn btn-info" onclick="if(checkBet()) submit();" value="{{ __('odds.game_bet_save') }}">
      <input type="hidden" name="game_id" value="{{ $game_id }}">
      {{ csrf_field() }}

      <hr>

		  <div class="text-end text-info">{!! __("odds.info_points") !!}</div>

	  	<h3>{{ $game->name }}</h3>
	  	<h4>{{ __('odds.bet_win') }}</h4>
	    <table class="table table-striped table-bordered">
				<tr>
	        <th class="text-center col-md-1">{{ __('odds.candidate_order') }}</th>
	        <th class="text-center col-md-6">{{ __('odds.candidate_name') }}</th>
	        <th class="text-center col-md-1">{{ __('odds.candidate_odds') }}</th>
	        <th class="text-center col-md-2">{{ __('odds.bet_points') }}</th>
				</tr>
	    @foreach($candidates as $candidate)
	      <tr>
	        <td class="text-center align-middle">{{ $candidate->disp_order+1 }}</td>
	        <td class="text-left align-middle" style="padding-left: 20px; padding-right: 20px;">{{ $candidate->name }}</td>
	        <td class="text-center align-middle" id="odds_win_{{ $candidate->id }}"></td>
	        <td class="text-left align-middle">
	        	<input id="bet_win_{{ $candidate->id }}" name="bet_win_{{ $candidate->id }}" type='number' class="form-control" oninput="onModifyBet()">
	        </td>
	      </tr>
	    @endforeach
	  	</table>

	  	<hr>

	  </form>
  </div>
</div>

<script type="text/javascript">
const candidates = <?php echo json_encode($candidates); ?>;
const odds0 = <?php echo json_encode($odds0); ?>;
const bets = <?php echo json_encode($bets); ?>;
const initial_points = {{ $user->get_current_points() }};
var input_bet_elements = [];

// Calculate total of bets that isn't resulted.
function calcTotalBets()
{
	let total = 0;
	for( let i = 0; i < bets.length; ++i )
	{
		if( !bets[i].payed )
		{
			total += bets[i].points;
		}
	}
	return total;
}
const initial_bets = calcTotalBets();

// Initialize the values of odds, and bets.
function initOddsBets()
{
	// for win
	candidates.forEach( function(candidate)
		{
			// Odds
			let elem = document.getElementById('odds_win_' + candidate.id);
			elem.innerHTML = '1';
			for( let i = 0; i < odds0.length; ++i )
			{
				if( odds0[i].candidate_id0 == candidate.id )
				{
					elem.innerHTML = '' + odds0[i].odds;
					break;
				}
			}
			// Bets
			let elem_name = 'bet_win_' + candidate.id;
			elem = document.getElementById(elem_name);
			elem.value = 0;
			for( let i = 0; i < bets.length; ++i )
			{
				if( bets[i].type == 0
				 && bets[i].candidate_id0 == candidate.id )
				{
					elem.value = bets[i].points;
					break;
				}
			}
			input_bet_elements.push(elem_name);
		}
	);
}
window.onload = initOddsBets;

// Total current bets
function getTotalBets()
{
	let	current_bets = 0;
	for( let i = 0; i < input_bet_elements.length; ++i )
	{
		let elem = document.getElementById(input_bet_elements[i]);
		let num = Number(elem.value);
		if( num > 0 )
		{
			current_bets += num;
		}
	}
	return current_bets;
}

// Update current own points 
function onModifyBet()
{
	let	current_bets = getTotalBets();
	let current_points = initial_points + initial_bets - current_bets;
	let elem = document.getElementById("my_points");
	elem.innerHTML = current_points;
	if( current_points < 0 )
		elem.style.color = '#f11';
	else
		elem.style.color = '#fff';
}

// Check whether the input bet is correct
function checkBet()
{
	let	current_bets = getTotalBets();
	let current_points = initial_points + initial_bets - current_bets;
	if( current_points >= 0 )
	{
		return true;
	}
	else
	{
		return false;
	}
}

</script>
