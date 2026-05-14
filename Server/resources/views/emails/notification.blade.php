<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $notification->titre }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2 style="margin-bottom: 8px;">{{ $notification->titre }}</h2>
    <p style="line-height: 1.6; margin-top: 0;">{{ $notification->message }}</p>
    <p style="font-size: 12px; color: #6b7280; margin-top: 24px;">
        Notification envoyée par {{ config('app.name') }}.
    </p>
</body>
</html>
