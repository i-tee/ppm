<!DOCTYPE html>
<html>
<head>
    <title>@lang('verification.email_title')</title>
</head>
<body>
    <h1>@lang('verification.verify_email')</h1>
    <p>@lang('verification.greeting', ['name' => $notifiable->name])</p>
    <p>@lang('verification.instruction')</p>
    <a href="{{ $url }}">@lang('verification.verify_button')</a>
    <p>@lang('verification.thanks')</p>
</body>
</html>