@php
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->take(1)->get()[0];
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
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css')  }}" >
</head>

@php
if( $user->admin )
{
  $enabled_win = '';
  $enable_quinella = '';
  $enable_exacta = '';
  if( $game->enabled & 1 )
  {
    $enabled_win = 'checked';
  }
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
    <h1>レース編集</h1>
    <form action="/update" method="POST">
      <div class="form-group">
        <label>{{ __('odds.game_name') }}</label>
        <input name="game_name" type="text" class="form-control" value="{{ $game->name }}">
      </div>
      <div class="form-group">
        <label>{{ __('odds.game_limit') }}</label>
        <input name="game_limit" type="datetime" class="form-control" value="{{ $game->limit }}">
      </div>
      <div class="form-group">
        <label>{{ __('odds.game_candidate') }}</label>
        <textarea name="game_candidate" style='height: 400px' class="form-control">{{ $candidates_name }}</textarea>
      </div>
      <table>
        <tr>
          <td style="padding: 4px;">
            <label for="enable_win">
              <input type="checkbox" id="enable_win" name="enabled[]" value="0" checked disabled />
              {{ __('odds.bet_win') }}
            </label>
          </td>
          <td style="padding: 4px;">
            <label for="enable_quinella">
              <input type="checkbox" id="enable_quinella" name="enabled[]" value="1" {{ $enable_quinella }} />
              {{ __('odds.bet_quinella') }}
            </label>
          </td>
          <td style="padding: 4px;">
            <label for="enable_exacta">
              <input type="checkbox" id="enable_exacta" name="enabled[]" value="2" {{ $enable_exacta }} />
              {{ __('odds.bet_exacta') }}
            </label>
          </td>
        </tr>
      </table>
      <input type="hidden" name="game_id" value="{{ $game_id }}">
      {{ csrf_field() }}
      <input type="button" class="btn btn-secondary" onclick="onCancel();" value="{{ __('odds.admin_cancel') }}">
      <button type="submit" class="btn btn-success">{{ __('odds.admin_save') }}</button>
    </form>
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
</script>
