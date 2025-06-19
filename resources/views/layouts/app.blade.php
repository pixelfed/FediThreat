<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FediThreat - Content Moderation API for the Fediverse</title>
    <meta name="description" content="Open source content moderation API by Pixelfed. Detect and prevent abuse, spam, and threats in real-time across federated platforms.">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-slate-100 min-h-screen">
    @yield('content')
</body>
</html>
