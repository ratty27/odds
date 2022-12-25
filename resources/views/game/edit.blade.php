@php
$user = App\Models\User::where('personal_id', Cookie::get('iden_token'))->take(1)->get()[0];
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<div>
      @php
      if( $user->admin )
      {
      @endphp

        <div class="container">
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
            <input type="hidden" name="game_id" value="{{ $game_id }}">
            {{ csrf_field() }}
            <button type="submit" class="btn btn-primary">{{ __('odds.admin_save') }}</button>
          </form>
        </div>

      @php
      }
      @endphp
</div>
