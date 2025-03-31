<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Your Brand</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add CSRF token in head -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --error-color: #f72585;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
        }
        
        .split-container {
            display: flex;
            width: 100%;
            height: 100%;
        }
        
        .image-section {
            flex: 1;
            background: url('https://source.unsplash.com/random/1600x900/?nature,water') center/cover no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
        }
        
        .image-content {
            position: relative;
            z-index: 1;
            padding: 40px;
            text-align: center;
        }
        
        .image-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .image-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .login-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background-color: white;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            font-size: 2rem;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #777;
            font-size: 0.9rem;
        }
        
        .form-groups {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-groups label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .form-groups input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-groups input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .form-groups i {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #777;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 5px;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .login-button:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
        }
        
       
        
        @media (max-width: 768px) {
            .split-container {
                flex-direction: column;
            }
            
            .image-section {
                display: none;
            }
            
            .login-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="split-container">
        <!-- Left side with image -->
        <div class="image-section">
            <div class="image-overlay"></div>
            <div class="image-content">
                <h1>Welcome to Our Platform</h1>
                <p>Join thousands of satisfied users who manage their work seamlessly with our services.</p>
            </div>
        </div>
        
        <!-- Right side with login form -->
        <div class="login-section">
            <div class="login-container">
                <div class="login-header">
                    <h2>Login to your account</h2>
                    <p>Enter your credentials to access your dashboard</p>
                </div>
                
                <!-- Display errors if any -->
                @if($errors->any())
                    <div style="color: red; margin-bottom: 15px;">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                
                <form method="POST" action="{{ route('admin.login.submit') }}" class="login-form">
                    @csrf
                    
                    <div class="form-groups">
                        <label for="email">Email Address</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required autofocus>
                    </div>
                    
                    <div class="form-groups">
                        <label for="password">Password</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="remember-forgot">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <div class="forgot-password">
                        </div>
                    </div>
                    
                    <button type="submit" class="login-button">Login</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>