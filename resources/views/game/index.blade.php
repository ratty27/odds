@php
$user_token = Cookie::queued('iden_token') ? Cookie::queued('iden_token')->getValue() : Cookie::get('iden_token');
$user = App\Models\User::where('personal_id', $user_token)->take(1)->get()[0];
@endphp

<head>
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<div class="container">
  @include('parts.header')
  <table class="table text-center table-striped table-bordered">
    <tr>
      <th class="text-center col-md-1">{{ __('odds.game_id') }}</th>
      <th class="text-center col-md-7">{{ __('odds.game_name') }}</th>
      <th class="text-center col-md-2">{{ __('odds.game_limit') }}</th>
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
        <td class="align-middle">{{ $game->id }}</td>
        <td class="align-middle">
          <a href="/game/{{ $game->id }}">{{ $game->name }}</a>
        </td>
        <td class="align-middle">{{ $game->limit }}</td>
        @php
        if( $user->admin )
        {
        @endphp
          <td class="text-center align-middle"><a href="/edit/{{ $game->id }}" class="btn btn-info">{{ __('odds.admin_edit') }}</a></td>
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
        <td><a href="/edit/new" class="btn btn-info">{{ __('odds.admin_add') }}</a></td>
      </tr>
    @php
    }
    @endphp
  </table>
</div>