<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            max-width: 400px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }
        .logo p {
            color: #718096;
            font-size: 14px;
        }
        .sso-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 14px 24px;
            background: #0078d4;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .sso-button:hover {
            background: #005a9e;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 120, 212, 0.3);
        }
        .sso-button svg {
            margin-right: 12px;
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider span {
            padding: 0 16px;
            color: #718096;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            color: #718096;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>{{ config('app.name') }}</h1>
            <p>Sign in to continue</p>
        </div>

        @if($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <a href="{{ route('entra.login') }}" class="sso-button">
            <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="10" height="10" fill="currentColor"/>
                <rect x="11" width="10" height="10" fill="currentColor" opacity="0.8"/>
                <rect y="11" width="10" height="10" fill="currentColor" opacity="0.8"/>
                <rect x="11" y="11" width="10" height="10" fill="currentColor" opacity="0.6"/>
            </svg>
            Sign in with Microsoft
        </a>

        <div class="footer">
            Secured by Azure Active Directory
        </div>
    </div>
</body>
</html>
