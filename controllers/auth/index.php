<?php
// 這裡實作 Synology OpenID Connect 驗證流程
// 參考 https://sso.ccc.tc/webman/sso/.well-known/openid-configuration

// 從 .env 取得設定
$app_url = env('APP_URL');
$oauth_server = env('OAUTH_SERVER');
$client_id = env('CLIENT_ID');
$client_secret = env('CLIENT_SECRET');
$redirect_uri = env('CALLBACK_URL'); // 例如: https://你的網域/auth/callback

// 組合 Synology SSO 授權與 token 端點
if(env('OAUTH_DRIVER') == 'synology'){
    $authorize_url = rtrim($oauth_server, '/') . '/webman/sso/SSOOauth.cgi';
    $token_url = rtrim($oauth_server, '/') . '/webman/sso/SSOAccessToken.cgi';
}elseif(env('OAUTH_DRIVER') == 'laravel'){
    $authorize_url = rtrim($oauth_server, '/') . '/oauth/authorize';
    $token_url = rtrim($oauth_server, '/') . '/oauth/token';
}else{
    throw new Exception("Unsupported OAuth driver: ".env('OAUTH_DRIVER'));
}

// 檢查必要參數
if (!$client_id || !$client_secret || !$redirect_uri || !$oauth_server) {
    exit('OAuth 設定有誤，請檢查 .env 檔案。');
}

// 取得目前路徑
$uri = $_SERVER['REQUEST_URI'];

// /auth 進行驗證
if (strpos($uri, '/auth/callback') === false) {
    // 產生 state 防止 CSRF
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth2state'] = $state;

    // 組合授權網址 - 使用 Synology 的 scope 格式
    $params = [
        'response_type' => 'code',
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => env("OAUTH_SCOPE",''), // Synology 使用的 scope
        'state' => $state,
    ];
    $auth_url = $authorize_url . '?' . http_build_query($params);

    // 除錯用：檢查組合後的URL
    error_log('OAuth 授權網址: ' . $auth_url);

    // 導向 Synology SSO 授權頁
    header('Location: ' . $auth_url);
    exit;
}