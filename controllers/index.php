<?php
$title = "無路由框架微型";
$features = [
    "Balde Template",
    "Laravel Eloquent ORM",
    "不需指定路由",
    "學習曲線低"
];
//controller名稱即是路徑名稱及view名稱
return view(compact("features","title"));