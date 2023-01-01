@php
// User
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->select('id', 'name', 'points', 'admin')->first();
// Game
$game = App\Models\Game::findOrFail($game_id);
$game->update_odds_if_needs();
// Canddates
$candidates = App\Models\Candidate::where('game_id', $game_id)
  ->orderBy('disp_order', 'asc')
  ->select('id', 'name', 'disp_order')
  ->get();

@endphp

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
</head>

@php
if( $user->admin )
{
@endphp

<div class="container">
  @include('parts.header')
  <div class="table-responsive">
    <form action="/finish" method="POST">
      <input type="hidden" name="game_id" value="{{ $game_id }}">
      {{ csrf_field() }}
      <h3>{{ $game->name }}</h3>
      <table class="table table-striped table-bordered">
        <tr>
          <th class="text-center col-md-1">{{ __('odds.candidate_order') }}</th>
          <th class="text-center col-md-7">{{ __('odds.candidate_name') }}</th>
          <th class="text-center col-md-2">{{ __('odds.candidate_ranking') }}</th>
        </tr>
      @foreach($candidates as $candidate)
        <tr>
          <td class="text-center align-middle">{{ $candidate->disp_order+1 }}</td>
          <td class="text-left align-middle" style="padding-left: 20px; padding-right: 20px;">{{ $candidate->name }}</td>
          <td class="text-left align-middle">
            <input id="ranking_{{ $candidate->id }}" name="ranking_{{ $candidate->id }}" type='number' class="form-control" oninput="onModifyBet()">
          </td>
        </tr>
      @endforeach
      </table>
      <input type="button" class="btn btn-secondary" onclick="onCancel();" value="{{ __('odds.admin_cancel') }}">
      <input type="button" class="btn btn-danger" onclick="if(checkRanking()) submit();" value="{{ __('odds.admin_save') }}">
    </form>
  </div>
</div>



<script type="text/javascript">
const candidates = <?php echo json_encode($candidates); ?>;

// Check whether the rankings are correct
function checkRanking()
{
  for( let i = 0; i < candidates.length; ++i )
  {
    let elem = document.getElementById('ranking_' + candidates[i].id);
    if( !elem )
    {
      return false;
    }
    let ranking = +elem.value;
    if( ranking <= 0 )
    {
      alert("{{ __('odds.admin_error_input') }}");
      return false;
    }
  }
  return window.confirm("{{ __('odds.admin_confirm_finish') }}");
}

// Cancel clicked
function onCancel()
{
  location.href = "/";
}

</script>

@php
}
@endphp
