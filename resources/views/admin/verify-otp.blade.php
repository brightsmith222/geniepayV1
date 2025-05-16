<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Your Brand</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --error-color: #f72585;
        }

        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-color);
        }

        .otp-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .otp-container h2 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .otp-container p {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 25px;
        }

        .form-groups {
            margin-bottom: 20px;
            position: relative;
        }

        .form-groups input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-groups input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }

        .otp-button {
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

        .otp-button:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
        }

        .resend-link {
            display: block;
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .resend-link a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <h2>Verify OTP</h2>
        <p>Please enter the 6-digit code sent to your email address.</p>

        @if(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.otp.verify.submit') }}">
            @csrf
            <div class="form-groups">
                <input type="text" name="otp" maxlength="6" placeholder="Enter OTP" required>
            </div>
            @if($errors->has('otp'))
                <div class="error-message">{{ $errors->first('otp') }}</div>
            @endif
            <button type="submit" class="otp-button">Verify</button>
        </form>

        <div class="resend-link">
            Didn’t receive the code? <button id="resend-otp-btn" class="btn btn-sm btn-primary">Resend OTP</button>
            <p id="otp-message" class="mt-2 text-success"></p>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        document.getElementById('resend-otp-btn').addEventListener('click', function () {
            fetch("{{ route('admin.otp.resend') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                const msgBox = document.getElementById('otp-message');
                if (data.status === 'success') {
                    msgBox.textContent = data.message;
                    msgBox.style.color = 'green';
                } else {
                    msgBox.textContent = data.message;
                    msgBox.style.color = 'red';
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('otp-message').textContent = "Something went wrong.";
            });
        });
    </script>
</body>

</html>
