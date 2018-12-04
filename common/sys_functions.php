<?php
    use eftec\bladeone\BladeOne;

	function redirect($url){
        header("Location: $url");
	}

    function dd($v){
        dump($v);
        exit;
    }

    //web的目錄
    function base_path(){
        return sprintf(__DIR__."/..");
    }

    function storage_path($path=""){
        if($path==""){
            return sprintf(__DIR__."/../../storage");
        }else{
            return sprintf(__DIR__."/../../storage/".$path);
        }
    }

    //移除問號後的字串
    function strip_parameter($request_uri="", $pattern_num="2"){
        preg_match_all('/(\/)(.+)(\\?)(.+)/uim', $request_uri, $matches);
        return $matches[$pattern_num][0];
    }

    //取得view的名稱 
    function get_view(){
        $view = ltrim($_SERVER['REQUEST_URI'],"/");
            //如果有問號，取問號前的url
        if(preg_match('/.+\\?/uU', $view)){
            $view = explode("?",$view)[0];
        }
        return $view;
    }

    function view($data=[], $view=""){

        if($view==""){
            $view = get_view();
        }

        $views = __DIR__ . '/../views';
        $cache = __DIR__ . '/../storage/cache';
        if($view == ""){
            $view="index";
        }

        $blade=new BladeOne($views, $cache,BladeOne::MODE_AUTO);
        echo $blade->run($view,$data);
    }

    //退出
    function abort($http_code, $view=""){
        switch($http_code){
            case "404":
            header("HTTP/1.0 404 Not Found");
            break;
        }
        exit;
    }

    //取得Header的token字串或回傳錯誤的Array
    function get_jwt(){
        $token = "";
        if(array_key_exists("HTTP_AUTHORIZATION",$_SERVER)){
            $token = explode(" ",$_SERVER['HTTP_AUTHORIZATION'])[1];
        }else{
            return [
                'state'=>'n',
                'msg' => '数据异常，无法处理'
            ];
        }
        return $token;
    }

    //移除session并登出 
    function logout(){
        session_destroy();
        redirect("/login");
    }

    //檢測是否為JSON字串
    function isJsonString($string) {
        return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    //回传不可逆的hash密码
    function bcrypt($password){
        return password_hash($password, PASSWORD_BCRYPT);
    }

    function encrypt($v){
       return (new Cryptor(env("APP_KEY")))->encrypt($v);
    }

    function decrypt($v){
       return (new Cryptor(env("APP_KEY")))->decrypt($v);
    }
