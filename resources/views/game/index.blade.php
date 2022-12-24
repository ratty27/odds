<head>
  <title>The odds</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>
<div>
    <table class="table text-center">
      <tr>
        <th class="text-center">ID</th>
        <th class="text-center">ゲーム</th>
        <th class="text-center">期限</th>
      </tr>
      @foreach($games as $game)
      <tr>
        <td>{{ $game->id }}</td>
        <td>
          <a href="/game/{{ $game->id }}">{{ $game->name }}</a>
        </td>
        <td>{{ $game->limit }}</td>
      </tr>
      @endforeach
    </table>
</div>