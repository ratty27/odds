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
	->select('type', 'candidate_id0', 'candidate_id1', 'candidate_id2', 'points', 'payed')
	->get();

@endphp

<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
</head>
<div class="container">
  @include('parts.header')
    <form action="/bet" method="POST">
      <input type="button" class="btn btn-info" onclick="if(checkBet()) submit();" value="{{ __('odds.game_bet_save') }}">
      <input type="hidden" name="game_id" value="{{ $game_id }}">
      {{ csrf_field() }}

      <hr>

		  <div class="text-end text-info odds_tips">{!! __("odds.info_points") !!}</div>

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
	        <td class="text-center align-middle odds_number">{{ $candidate->disp_order+1 }}</td>
	        <td class="text-left align-middle" style="padding-left: 20px; padding-right: 20px;">{{ $candidate->name }}</td>
	        <td class="text-center align-middle" id="odds_win_{{ $candidate->id }}"></td>
	        <td class="text-left align-middle">
	        	<input id="bet_win_{{ $candidate->id }}" name="bet_win_{{ $candidate->id }}" type='number' class="form-control" oninput="onModifyBet()" value="0">
	        </td>
	      </tr>
	    @endforeach
	  	</table>

	    @php
	    if( $game->is_enabled(1) )
	    {
	    	echo '<hr>';
	      echo '<h4>' . __('odds.bet_quinella') . '</h4>';
	      for( $i = 0; $i < count($candidates) - 1; ++$i )
	      {
				  echo '<div class="table-responsive">';
	        echo '<table class="table-bordered" style="table-layout: auto;">';
	        echo '<tr><td class="odds_value odds_number" rowspan="2">' . ($candidates[$i]->disp_order+1) . '</td>';
	        for( $j = $i + 1; $j < count($candidates); ++$j )
	        {
	          echo '<td class="odds_value odds_number">' . ($candidates[$j]->disp_order+1) . '</td>';
	        }
	        echo '</tr>';
	        echo '<tr>';
	        for( $j = $i + 1; $j < count($candidates); ++$j )
	        {
	        	$id0 = $candidates[$i]->id;
	        	$id1 = $candidates[$j]->id;
	        	if( $id0 > $id1 )
	        	{
	        		$tmp = $id0;
	        		$id0 = $id1;
	        		$id1 = $tmp;
	        	}
	          echo '<td class="odds_value text-center" id="odds_quinella_' . $id0 . '_' . $id1 . '"></td>';
	        }
	        echo '</tr>';
	        echo '<tr><td>' . __("odds.bet_points") . '</td>';
	        for( $j = $i + 1; $j < count($candidates); ++$j )
	        {
	        	$id0 = $candidates[$i]->id;
	        	$id1 = $candidates[$j]->id;
	        	if( $id0 > $id1 )
	        	{
	        		$tmp = $id0;
	        		$id0 = $id1;
	        		$id1 = $tmp;
	        	}
	        	$bet_id = 'bet_quinella_' . $id0 . '_' . $id1;
	          echo '<td class="odds_value text-center" id="">';
	        	echo '<input id="' . $bet_id . '" name="' . $bet_id . '" type="number" class="form-control" style="width: 90px;" oninput="onModifyBet()" value="0">';
		        echo '</td>';
	        }
	        echo '</tr>';
	        echo '</table></div><br>';
	      }
	    }

	    if( $game->is_enabled(2) )
	    {
	    	echo '<hr>';
	      echo '<h4>' . __('odds.bet_exacta') . '</h4>';
	      for( $i = 0; $i < count($candidates); ++$i )
	      {
				  echo '<div class="table-responsive">';
	        echo '<table class="table-bordered" style="table-layout: auto;">';
	        echo '<tr><td class="odds_value odds_number" rowspan="2">' . ($candidates[$i]->disp_order+1) . '</td>';
	        for( $j = 0; $j < count($candidates); ++$j )
	        {
	          if( $i == $j ) continue;
	          echo '<td class="odds_value odds_number">' . ($candidates[$j]->disp_order+1) . '</td>';
	        }
	        echo '</tr>';
	        echo '<tr>';
	        for( $j = 0; $j < count($candidates); ++$j )
	        {
	          if( $i == $j ) continue;
	          echo '<td class="odds_value text-center" id="odds_exacta_' . $candidates[$i]->id . '_' . $candidates[$j]->id . '"></td>';
	        }
	        echo '</tr>';
	        echo '<tr><td>' . __("odds.bet_points") . '</td>';
	        for( $j = 0; $j < count($candidates); ++$j )
	        {
	          if( $i == $j ) continue;
	        	$bet_id = 'bet_exacta_' . $candidates[$i]->id . '_' . $candidates[$j]->id;
	          echo '<td class="odds_value text-center" id="">';
	        	echo '<input id="' . $bet_id . '" name="' . $bet_id . '" type="number" class="form-control" style="width: 90px;" oninput="onModifyBet()" value=0>';
		        echo '</td>';
	        }
	        echo '</tr>';
	        echo '</table></div><br>';
	      }
	    }
	    @endphp
	  </form>

  	<hr>

</div>

<script src="{{ asset('/js/odds_util.js') }}"></script>
<script type="text/javascript">
const candidates = <?php echo json_encode($candidates); ?>;
const odds0 = <?php echo json_encode($odds0); ?>;
const odds1 = <?php echo json_encode($odds1); ?>;
const odds2 = <?php echo json_encode($odds2); ?>;
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
					elem.innerHTML = get_disp_odds( odds0[i].odds );
					break;
				}
			}
		}
	);

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
	bets.forEach( function(bet)
		{
			if( bet.type == 0 )
			{
				let elem_name = 'bet_win_' + bet.candidate_id0;
				elem = document.getElementById(elem_name);
				elem.value = bet.points;
			}
			else if( bet.type == 1 )
			{
				let elem_name = 'bet_quinella_' + bet.candidate_id0 + '_' + bet.candidate_id1;
				elem = document.getElementById(elem_name);
				elem.value = bet.points;
			}
			else if( bet.type == 2 )
			{
				let elem_name = 'bet_exacta_' + bet.candidate_id0 + '_' + bet.candidate_id1;
				elem = document.getElementById(elem_name);
				elem.value = bet.points;
			}
		}
	);
	// Collect betting cell name
	candidates.forEach( function(candidate)
		{
			input_bet_elements.push('bet_win_' + candidate.id);
		}
	);
	for( let i = 0; i < candidates.length - 1; ++i )
	{
		for( let j = i + 1; j < candidates.length; ++j )
		{
			input_bet_elements.push('bet_quinella_' + candidates[i].id + '_' + candidates[j].id);
		}
	}
	for( let i = 0; i < candidates.length; ++i )
	{
		for( let j = 0; j < candidates.length; ++j )
		{
			if( i == j ) continue;
			input_bet_elements.push('bet_exacta_' + candidates[i].id + '_' + candidates[j].id);
		}
	}
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
