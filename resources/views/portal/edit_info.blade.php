<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('odds.title') }}</title>
  <link rel="stylesheet" href="{{ asset('/css/bootstrap.min.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/odds.css?v=' . __('odds.css_ver')) }}" >
</head>
<div class="container">
  @include('parts.header')
</div>
<center>
  <div class="col-md-10 text-start">
    <h3>{{ __("odds.admin_edit_info") }}</h3>
  </div>
  <div class="col-md-10 shadow rounded" style="padding: 16px;">
    <form action="{{ asset('/admin_add_info') }}" method="POST">
      <div class="form-group text-start">
        <label for="info_message" class="form-label">{{ __('odds.admin_info_message') }}</label>
        <textarea id="info_message" name="info_message" class="form-control"></textarea>
      </div>
      {{ csrf_field() }}
      <div class="text-start">
        <input type="button" class="btn btn-success" onclick="submit();" value="{{ __('odds.admin_save') }}">
      </div>
    </form>
  </div>
</center>
