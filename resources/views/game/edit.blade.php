@php
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->first();
$candidates_name = "";
if( $game_id === 'new' )
{
  $game = new App\Models\Game;
  $game->limit = date("Y/m/d H:i:s");
  $game->user_id = $user->id;
}
else
{
  $game = App\Models\Game::findOrFail($game_id);
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
      <h3>レース編集</h3>
    </div>
    <div class="col-md-10 shadow rounded" style="padding: 16px;">
      <form action="{{ asset('/update') }}" method="POST">
        <div class="form-group text-start">
          <label>{{ __('odds.game_name') }}</label>
          <input name="game_name" type="text" class="form-control" value="{{ $game->name }}">
        </div>
        <div class="form-group text-start">
          <label>{{ __('odds.game_limit') }}</label>
          <input name="game_limit" type="datetime" class="form-control" value="{{ $game->limit }}">
        </div>
        <div class="form-group text-start">
          <label>{{ __('odds.game_comment') }}</label>
          <textarea name="game_comment" class="form-control">{{ $game->comment }}</textarea>
        </div>
        <div class="form-group text-start">
          <label>{{ __('odds.game_candidate') }}</label>
          <textarea name="game_candidate" style='height: 400px' class="form-control">{{ $candidates_name }}</textarea>
        </div>
        <div class="form-group text-start">
          <label>{{ __('odds.game_public_setting') }}</label>
          <select name="game_pubsetting" style='width: 180px;' class="form-control">
            <option value="0">{{ __('odds.game_private') }}</option>
            <option value="1">{{ __('odds.game_public') }}</option>
          </select>
        </div>
        <div class="text-start">
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
            <button type="submit" class="btn btn-success">{{ __('odds.admin_save') }}</button>
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
// Cancel clicked
function onCancel()
{
  location.href = "/";
}

function onDelete()
{
  if( confirm('{{ __("odds.admin_confirm_delete") }}') )
  {
    location.href = "/delete/{{ $game_id }}";
  }
}

</script>
