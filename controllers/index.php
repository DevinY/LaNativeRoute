<?php
$title = "無路由框架微型";
$features = [
    "Balde Template",
    "Laravel Eloquent ORM",
    "不需指定路由，資料夾即是路由",
    "學習曲線低"
];
//controller名稱即是路徑名稱及view名稱
dd($_SESSION);
return view(compact("features","title"));