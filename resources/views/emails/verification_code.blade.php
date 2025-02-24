<!DOCTYPE html>
<html>
<head>
    <title>Verification Code</title>
    <style>

        p span{
            font-size: 20px;
            font-weight: 200;
        }
        body{
            padding-top: 100px;
            background-color: green;
            color: white;
            text-align: center;

        }


    </style>
</head>


<body>
    <p>Enter the following code in your app to continue.</p>
    <p>Your verification code is: <span>{{ $code }}</span></p>

    <span>If you didn't initiate this process, please ignore. </span>
</body>
</html>
