@php
$user = App\Models\User::where('personal_id', Cookie::get('iden_token'))->take(1)->get()[0];
@endphp

<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<div class="container">
  <h1>{{ __('odds.title') }}</h1>  
  <table class="table text-center">
    <tr>
      <th class="text-center">{{ __('odds.game_id') }}</th>
      <th class="text-center">{{ __('odds.game_name') }}</th>
      <th class="text-center">{{ __('odds.game_limit') }}</th>
      @php
      if( $user->admin )
      {
      @endphp
        <th class="text-center">{{ __('odds.admin') }}</th>
      @php
      }
      @endphp
    </tr>
    @foreach($games as $game)
    <tr>
      <td>{{ $game->id }}</td>
      <td>
        <a href="/game/{{ $game->id }}">{{ $game->name }}</a>
      </td>
      <td>{{ $game->limit }}</td>
      @php
      if( $user->admin )
      {
      @endphp
        <td class="text-center"><a href="/edit/{{ $game->id }}">{{ __('odds.admin_edit') }}</a></th>
      @php
      }
      @endphp
    </tr>
    @endforeach
    @php
    if( $user->admin )
    {
    @endphp
      <tr>
        <td><a href="/edit/new">{{ __('odds.admin_add') }}</a></td>
      </tr>
    @php
    }
    @endphp
  </table>
</div>