<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Xtremepush</title>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            align-items: center;
            display: flex;
            justify-content: center;
            font-family: system-ui, sans-serif;
            padding: 1.5rem;
            text-align: center;
        }

        .message {
            max-width: 28rem;
        }
    </style>

</head>

<body>

<p class="message">{{ $message }}</p>

</body>

</html>
