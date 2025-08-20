<?php
// Synology OpenID Connect callback 實作
// 從 .env 取得設定
$app_url = env('APP_URL');
$oauth_server = env('OAUTH_SERVER');
$client_id = env('CLIENT_ID');
$client_secret = env('CLIENT_SECRET');
$redirect_uri = env('CALLBACK_URL'); // 例如: https://你的網域/auth/callback

if (env('OAUTH_DRIVER') === 'synology') {
    $token_url = rtrim($oauth_server, '/') . '/webman/sso/SSOAccessToken.cgi';
} elseif (env('OAUTH_DRIVER') === 'laravel') {
    $token_url = rtrim($oauth_server, '/') . '/oauth/token';
} else {
    exit('不支援的 OAUTH_DRIVER 設定。');
}

// 驗證 state
if (!isset($_GET['state']) || !isset($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']);
    exit('狀態驗證失敗，請重新登入。');
}

if (!isset($_GET['code'])) {
    exit('未取得授權碼。');
}

$code = $_GET['code'];

// 交換 token
$post_fields = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
];

use GuzzleHttp\Client;

$client = new Client();
try {
    $guzzleResponse = $client->post($token_url, [
        'form_params' => $post_fields,
        'verify' => false,
        'http_errors' => false,
    ]);
    $response = $guzzleResponse->getBody()->getContents();
    $http_code = $guzzleResponse->getStatusCode();
    $curl_error = ''; // Guzzle 沒有 curl_error，保留變數以兼容後續程式
} catch (\Exception $e) {
    $response = '';
    $http_code = 0;
    $curl_error = $e->getMessage();
}

if ($http_code != 200) {
    exit('Token 取得失敗: ' . $response . ' (CURL錯誤: ' . $curl_error . ')');
}

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    exit('Token 回應格式錯誤: ' . $response);
}

// 取得用戶信息
function getUserByToken($access_token, $oauth_server) {
    // 取得 OAUTH_DRIVER，預設為 synology
    $endpoint = rtrim($oauth_server, '/');
    if (env('OAUTH_DRIVER') === 'synology') {
        $endpoint .= '/webman/sso/SSOUserInfo.cgi';
    } elseif (env('OAUTH_DRIVER') === 'laravel') {
        $endpoint .= '/api/user';
    }else{
        throw new Exception("Unsupported OAuth driver: ".env('OAUTH_DRIVER'));
    }

    // 使用 Guzzle 發送請求
    $client = new \GuzzleHttp\Client();
    try {
        $guzzleResponse = $client->get($endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'verify' => false,
            'http_errors' => false,
        ]);
        $response = $guzzleResponse->getBody()->getContents();
        $http_code = $guzzleResponse->getStatusCode();
    } catch (\Exception $e) {
        error_log('取得用戶信息失敗: ' . $e->getMessage());
        return false;
    }

    if ($http_code != 200) {
        error_log('取得用戶信息失敗: HTTP ' . $http_code . ' - ' . $response);
        return false;
    }
    return json_decode($response, true);
}

$user_info = getUserByToken($token_data['access_token'], $oauth_server);

if (!$user_info) {
    exit('無法取得用戶信息');
}

// 處理 Synology 用戶數據
$username = $user_info['username'] ?? $user_info['socialite_id'] ?? '';
$user_info['name'] = ucfirst($username);

// 將用戶信息存入 session
$_SESSION['user_info'] = $user_info;
$_SESSION['access_token'] = $token_data['access_token'];

// 導向首頁
header('Location: /');
exit;
