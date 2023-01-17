@php
$app_games = App\Models\Game::where('is_public', 1)->get();
@endphp

<head>
  <meta charset='utf-8'>
  <meta http-equiv='X-UA-Compatible' content='IE=edge'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>{{ __("odds.title") }}</title>
  <link rel='stylesheet' href='{{ asset("/css/bootstrap.min.css") }}'>
  <link rel='stylesheet' href='{{ asset("/css/odds.css")  }}' >
</head>
<div class='container'>
  @include('parts.header')

  <h4>{{ __("odds.admin_apply_public") }}</h4>
    <table class='table table-striped table-bordered'>
      <tr>
        <th class='text-center col-md-1'>ID</th>
        <th class='text-center col-md-3'>Title</th>
        <th class='text-center col-md-5'>Comment</th>
        <th class='text-center col-md-2'></th>
      </tr>
      @php
        foreach( $app_games as $game )
        {
          echo "<tr>";
          echo "<td class='align-middle text-center'>" . $game->id . "</td>";
          echo "<td class='align-middle'><a href='" . url("/game/" . $game->id) . "'>" . $game->name . "</a></td>";
          echo "<td class='align-middle'>" . $game->comment . "</td>";
          echo "<td class='align-middle' id='ctrl$game->id'>";
          echo "<a class='btn btn-success' href='javascript:void(0)' onclick='pubgame_approve($game->id); return false;'>" . __("odds.admin_approve") ."</a>";
          echo "<a class='btn btn-warning' href='javascript:void(0)' onclick='pubgame_reject($game->id); return false;'>" . __("odds.admin_reject") ."</a>";
          echo "</td>";
          echo "</tr>";
        }
      @endphp
    </table>

</div>

<script type='text/javascript'>
/**
 *  Approve a game to public
 */
async function pubgame_approve(game_id)
{
  let data = { 'game_id': game_id, 'pub': 1 };
  const res = await fetch('{{ url("/admin_pubgame") }}',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify(data)
    });
  const body = await res.json();
  if( body.result == 'success' )
  {
    let elem = document.getElementById('ctrl' + game_id);
    if( elem )
    {
      elem.innerHTML = '{{ __("odds.game_public") }}';
    }
  }
  else
  {
    let elem = document.getElementById('ctrl' + game_id);
    if( elem )
    {
      elem.innerHTML = 'fail';
      console.log(body);
    }
  }
}

/**
 *  Reject a game to public
 */
async function pubgame_reject(game_id)
{
  let data = { 'game_id': game_id, 'pub': 0 };
  const res = await fetch('{{ url("/admin_pubgame") }}',
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify(data)
    });
  const body = await res.json();
  if( body.result == 'success' )
  {
    let elem = document.getElementById('ctrl' + game_id);
    if( elem )
    {
      elem.innerHTML = '{{ __("odds.game_private") }}';
    }
  }
  else
  {
    let elem = document.getElementById('ctrl' + game_id);
    if( elem )
    {
      elem.innerHTML = 'fail';
      console.log(body);
    }
  }
}
</script>
