<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Web Downloader</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Roboto', sans-serif;
            background: #FAFAFA;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-container { width: 100%; max-width: 380px; }

        .login-card {
            background: #FFFFFF;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            overflow: hidden;
        }

        .login-header {
            background: #1976D2;
            color: white;
            padding: 32px 24px;
            text-align: center;
        }

        .login-header svg { width: 48px; height: 48px; margin-bottom: 16px; }
        .login-header h1 { font-size: 22px; font-weight: 400; margin-bottom: 4px; }
        .login-header p { font-size: 14px; opacity: 0.9; }

        .login-body { padding: 24px; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 12px; font-weight: 500; color: #757575; margin-bottom: 8px; }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #E0E0E0;
            border-radius: 4px;
            font-size: 14px;
            color: #212121;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus { outline: none; border-color: #1976D2; box-shadow: 0 0 0 2px #BBDEFB; }
        .form-input::placeholder { color: #BDBDBD; }
        .form-input.error { border-color: #F44336; }

        .checkbox-group { display: flex; align-items: center; gap: 8px; margin-bottom: 24px; }
        .checkbox-group input { width: 18px; height: 18px; accent-color: #1976D2; }
        .checkbox-group label { font-size: 14px; color: #757575; }

        .btn {
            width: 100%;
            padding: 12px;
            background: #1976D2;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
        }

        .btn:hover { background: #1565C0; box-shadow: 0 3px 6px rgba(0,0,0,0.16); }

        .alert { padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 14px; }
        .alert-error { background: #FFEBEE; color: #C62828; }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                <h1>Web Downloader</h1>
                <p>Sign in to continue</p>
            </div>

            <div class="login-body">
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" 
                            class="form-input @error('username') error @enderror"
                            value="{{ old('username') }}" placeholder="admin" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password"
                            class="form-input @error('password') error @enderror" placeholder="••••••••" required>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>