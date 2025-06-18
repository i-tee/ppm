<!DOCTYPE html>
<html>
<head>
    <title>@lang('passwords.email_title')</title>
</head>
<body>
    <h1>@lang('passwords.reset_password')</h1>
    <p>@lang('passwords.greeting', ['name' => $notifiable->name])</p>
    <p>@lang('passwords.instruction')</p>
    <a href="{{ $url }}">@lang('passwords.reset_button')</a>
    <p>@lang('passwords.expires', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])</p>
    <p>@lang('passwords.thanks')</p>
</body>
</html>