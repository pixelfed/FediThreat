<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FediThreat</title>
    <style>
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-family: "system", sans-serif;
        }
        h1 {
            font-size: 3rem;
            margin: 0;
            letter-spacing: -2px;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 1.25rem;
            color: #555;
            margin-top: 0.5rem;
            margin-bottom: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>fedi<span style="color: red;">threat</span></h1>
    <p>Content moderation API for Pixelfed.<br />Detect and prevent abuse, spam, and threats in real-time.</p>
    <p style="color: #aaa;">Launching July 2025</p>
</body>
</html>
