@php
$candidates_name = "";
if( $game_id === 'new' )
{
  $game = new App\Models\Game;
  $game->limit = date("Y-m-d") . 'T' . date("H:i:00");
  $game->user_id = $user->id;
}
else
{
  $game = App\Models\Game::findOrFail($game_id);
  if( !$user->admin && $game->user_id != $user->id )
  {
    die();
  }
  $candidates = App\Models\Candidate::where('game_id', $game_id)->orderBy('disp_order', 'asc')->get();
  foreach( $candidates as $candidate )
  {
    $candidates_name .= $candidate->name . "\n";
  }
}
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
if( $user->CanEditGame() )
{
  $enabled_win = 'checked';
  $enable_quinella = '';
  $enable_exacta = '';
  if( $game->enabled & 2 )
  {
    $enable_quinella = 'checked';
  }
  if( $game->enabled & 4 )
  {
    $enable_exacta = 'checked';
  }
@endphp

<div class="container">
  @include('parts.header')
  <center>
    <div class="col-md-10 text-start">
      <h3>{{ __("odds.game_edit") }}</h3>
    </div>
    <div class="col-md-10 shadow rounded" style="padding: 16px;">
      <form action="{{ asset('/update') }}" method="POST">
        <div class="form-group text-start">
          <label for="game_name" class="form-label fw-bold">{{ __('odds.game_name') }}</label>
          <input id="game_name" name="game_name" type="text" class="form-control" value="{{ $game->name }}">
        </div>
        <div class="form-group text-start">
          <label for="game_limit" class="form-label">
            <table style="width: 100%; table-layout: fixed;">
              <tr>
                <td class="col-md-3 text-start fw-bold">{{ __('odds.game_limit') }}</td>
                <td class="col-md-7 text-end">{{ __('odds.game_limit_desc') }}</td>
              </tr>
            </table>
          </label>
          <input id="game_limit" name="game_limit" type="datetime-local" class="form-control" value="{{ $game->limit }}">
        </div>
        <div class="form-group text-start">
          <label for="game_comment" class="form-label">
            <table style="width: 100%; table-layout: fixed;">
              <tr>
                <td class="col-md-3 text-start fw-bold">{{ __('odds.game_comment') }}</td>
                <td class="col-md-7 text-end">{{ __('odds.game_comment_desc') }}</td>
              </tr>
            </table>
          </label>
          <textarea id="game_comment" name="game_comment" class="form-control">{{ $game->comment }}</textarea>
        </div>
        <div class="form-group text-start">
          <label for="game_candidate" class="form-label">
            <table style="width: 100%; table-layout: fixed;">
              <tr>
                <td class="col-md-3 text-start fw-bold">{{ __('odds.game_candidate') }}</td>
                <td class="col-md-7 text-end">{{ __('odds.game_candidate_desc') }}</td>
              </tr>
            </table>
          </label>
          <textarea id="game_candidate" name="game_candidate" style='height: 400px' class="form-control">{{ $candidates_name }}</textarea>
        </div>
        <div class="form-group text-start">
          <label for="game_pubsetting" class="form-label">
            <table style="width: 100%; table-layout: fixed;">
              <tr>
                <td class="col-md-3 text-start fw-bold">{{ __('odds.game_public_setting') }}</td>
                <td class="col-md-7 text-end">{{ __('odds.game_public_setting_desc') }}</td>
              </tr>
            </table>
          </label>
          <select id='game_pubsetting' name='game_pubsetting' style='width: 180px;' class='form-control'>
            <option value="0">{{ __('odds.game_private') }}</option>
            <option value="1">{{ __('odds.game_public') }}</option>
          </select>
        </div>
        <div class="text-start fw-bold">
          {{ __('odds.game_odds_type') }}
        </div>
        <div class="text-start">
          <div class="form-check form-check-inline">
            <label for="enable_win">
              <input type="checkbox" id="enable_win" name="enabled[]" class="form-check-input" value="0" {{ $enabled_win }} disabled />
              {{ __('odds.bet_win') }}
            </label>
          </div>
          <div class="form-check form-check-inline">
            <label for="enable_quinella">
              <input type="checkbox" id="enable_quinella" name="enabled[]" class="form-check-input" value="1" {{ $enable_quinella }} />
              {{ __('odds.bet_quinella') }}
            </label>
          </div>
          <div class="form-check form-check-inline">
            <label for="enable_exacta">
              <input type="checkbox" id="enable_exacta" name="enabled[]" class="form-check-input" value="2" {{ $enable_exacta }} />
              {{ __('odds.bet_exacta') }}
            </label>
          </div>
        </div>
        <input type="hidden" name="game_id" value="{{ $game_id }}">
        {{ csrf_field() }}
        <hr>
        <div class="btn-toolbar text-start">
          <div class="col-5">
            <input type="button" class="btn btn-secondary" onclick="onCancel();" value="{{ __('odds.admin_cancel') }}">
            <input type="button" class="btn btn-success" onclick="if(checkEdit()) submit();" value="{{ __('odds.admin_save') }}">
          </div>
          @php
          if( $game_id !== 'new' )
          {
          @endphp
          <div class="col-5 text-end">
            <input type="button" class="btn btn-danger" onclick="onDelete();" value="{{ __('odds.admin_delete') }}">
          </div>
          @php
          }
          @endphp
        </div>
      </form>
    </div>
  </center>
</div>

@php
}
@endphp

<script type="text/javascript">

function initValues()
{
  let elem = document.getElementById('game_pubsetting');
  if( elem )
  {
    @php
    $pub = 0;
    if( $game->is_public > 0 )
    {
      $pub = 1;
    }
    @endphp
    elem.selectedIndex = {{ $pub }};
  }
}
window.onload = initValues;


// Cancel clicked
function onCancel()
{
  location.href = '{{ url("/mygames") }}';
}

//
function onDelete()
{
  if( confirm('{{ __("odds.admin_confirm_delete") }}') )
  {
    location.href = '{{ url("/delete/$game_id") }}';
  }
}

//
function checkEdit()
{
  let elemTitle = document.getElementById('game_name');
  let title = elemTitle.value.trim();
  if( !title )
  {
    alert('{{ __("odds.game_title_empty") }}');
    return false;
  }

  let elemCandidate = document.getElementById('game_candidate');
  let candidates = elemCandidate.value.split(/\r\n|\n/);
  if( candidates.length < 2 )
  {
    alert('{{ __("odds.game_needs_candidates") }}');
    return false;
  }

  return true;
}

</script>
