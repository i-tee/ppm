<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестовая страница авторизации</title>
    <style>
        .auth-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            font-family: Arial, sans-serif;
        }
        .auth-container ul {
            list-style-type: none;
            padding: 0;
        }
        .auth-container li {
            margin-bottom: 10px;
        }
        .auth-container img {
            max-width: 100px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>Данные пользователя от {{ session('social_user.provider') ?? 'неизвестного провайдера' }}</h1>
        @if (session()->has('social_user'))
            <ul>
                <li><strong>Провайдер:</strong> {{ session('social_user.provider') }}</li>
                <li><strong>ID:</strong> {{ session('social_user.id') }}</li>
                <li><strong>Имя:</strong> {{ session('social_user.name') ?? 'Не указано' }}</li>
                <li><strong>Email:</strong> {{ session('social_user.email') ?? 'Не указан' }}</li>
                <li><strong>Аватар:</strong> @if (session('social_user.avatar')) <img src="{{ session('social_user.avatar') }}" alt="Avatar"> @else Нет аватара @endif</li>
            </ul>
            <a href="/">Вернуться на главную</a>
        @else
            <p>Данные не найдены. <a href="/">Попробовать снова</a></p>
        @endif
    </div>
</body>
</html>