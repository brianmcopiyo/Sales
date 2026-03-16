<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock – {{ $subjectLine }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            background: #f9fafb;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #f3f4f6;
            overflow: hidden;
            box-shadow: 0 2px 15px -3px rgba(0, 111, 120, 0.07);
        }

        .header {
            background: #fff;
            padding: 24px;
            border-bottom: 1px solid #f3f4f6;
        }

        .title {
            color: #006F78;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .body {
            padding: 24px;
            color: #4b5563;
        }

        .button {
            display: inline-block;
            background: #006F78;
            color: #fff !important;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            margin-top: 16px;
        }

        .footer {
            background: #f8fafc;
            padding: 16px 24px;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Stock Management</h1>
        </div>
        <div class="body">
            <p>{{ $subjectLine }}</p>
            <p>{{ $messageBody }}</p>
            @if ($actionUrl)
                <a href="{{ $actionUrl }}" class="button">{{ $actionLabel }}</a>
            @endif
        </div>
        <div class="footer">© {{ date('Y') }} Stock Management System.</div>
    </div>
</body>

</html>
