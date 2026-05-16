<?php
/*
====================================================
 YOUTUBE SUBSCRIPTION VERIFIER WEBSITE
====================================================

WHAT THIS DOES:
- User logs in with Google
- Website checks if user subscribed to your YouTube channel
- Shows VERIFIED or NOT VERIFIED

====================================================
SETUP GUIDE
====================================================

1. Create Google Cloud Project
https://console.cloud.google.com

2. Enable YouTube Data API v3
https://console.cloud.google.com/apis/library/youtube.googleapis.com

3. Create OAuth Credentials
https://console.cloud.google.com/apis/credentials

Choose:
- OAuth Client ID
- Web Application

Authorized Redirect URL:
https://YOURDOMAIN.COM/index.php

4. Replace these values below:

====================================================
*/

$CLIENT_ID = "PASTE_CLIENT_ID_HERE";
$CLIENT_SECRET = "PASTE_CLIENT_SECRET_HERE";
$REDIRECT_URI = "https://YOURDOMAIN.COM/index.php";
$CHANNEL_ID = "PASTE_YOUR_CHANNEL_ID_HERE";

/*
====================================================
START SESSION
====================================================
*/

session_start();

/*
====================================================
GOOGLE LOGIN URL
====================================================
*/

$scope = urlencode("https://www.googleapis.com/auth/youtube.readonly");

$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $CLIENT_ID,
    'redirect_uri' => $REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/youtube.readonly',
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

/*
====================================================
HANDLE GOOGLE CALLBACK
====================================================
*/

if (isset($_GET['code'])) {

    $code = $_GET['code'];

    $token_url = "https://oauth2.googleapis.com/token";

    $post_fields = [
        'code' => $code,
        'client_id' => $CLIENT_ID,
        'client_secret' => $CLIENT_SECRET,
        'redirect_uri' => $REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (!isset($token_data['access_token'])) {
        die("Failed to get access token");
    }

    $access_token = $token_data['access_token'];

    /*
    ====================================================
    CHECK SUBSCRIPTION
    ====================================================
    */

    $youtube_api = "https://www.googleapis.com/youtube/v3/subscriptions?part=id&mine=true&forChannelId=" . $CHANNEL_ID;

    $headers = [
        "Authorization: Bearer " . $access_token
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $youtube_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $yt_response = curl_exec($ch);
    curl_close($ch);

    $yt_data = json_decode($yt_response, true);

    $verified = false;

    if (isset($yt_data['items']) && count($yt_data['items']) > 0) {
        $verified = true;
    }

    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>YouTube Verification</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                background: #0f172a;
                color: white;
                font-family: Arial;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }

            .box {
                background: #1e293b;
                padding: 30px;
                border-radius: 20px;
                text-align: center;
                width: 320px;
            }

            .success {
                color: #22c55e;
                font-size: 28px;
                font-weight: bold;
            }

            .failed {
                color: #ef4444;
                font-size: 28px;
                font-weight: bold;
            }

            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 20px;
                border-radius: 10px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                font-weight: bold;
            }
        </style>
    </head>
    <body>

    <div class="box">

        <?php if ($verified): ?>
            <div class="success">✅ VERIFIED</div>
            <p>User subscribed to your YouTube channel.</p>

            <!--
            =============================================
            TELEGRAM BOT INTEGRATION
            =============================================

            You can redirect user back to bot:

            tg://resolve?domain=YOUR_BOT_USERNAME&start=verified

            OR send verification data to your API/database.
            -->

            <a class="btn" href="tg://resolve?domain=YOUR_BOT_USERNAME&start=verified">
                Return To Bot
            </a>

        <?php else: ?>
            <div class="failed">❌ NOT VERIFIED</div>
            <p>User is not subscribed OR subscriptions are private.</p>

            <a class="btn" href="https://youtube.com/channel/<?php echo $CHANNEL_ID; ?>">
                Subscribe Now
            </a>

        <?php endif; ?>

    </div>

    </body>
    </html>

    <?php
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>YouTube Subscription Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            background: #0f172a;
            font-family: Arial;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: #1e293b;
            width: 350px;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
        }

        h1 {
            margin-top: 0;
        }

        .login-btn {
            display: inline-block;
            margin-top: 20px;
            background: #2563eb;
            color: white;
            padding: 14px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
        }

        .info {
            color: #cbd5e1;
            font-size: 14px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="card">

    <h1>YouTube Verify</h1>

    <p>
        Login with Google to verify your YouTube subscription.
    </p>

    <a class="login-btn" href="<?php echo $auth_url; ?>">
        Continue With Google
    </a>

    <div class="info">
        Your subscriptions must be public for verification to work.
    </div>

</div>

</body>
</html>
