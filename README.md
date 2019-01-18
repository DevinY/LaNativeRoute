# LaNativeRoute

## LaNativeRoute 說明

自行組裝的輕量框架。

捨棄路由檔案，由檔案系統的決定路由。

保留在Laravel中，我喜歡的特點，像是Model及Blade等。

沒有太多限制，極度客制化。

## INSTALL
<pre>
composer install
</pre>

## 變數資料夾
<pre>
LaNativeRoute 使用獨創的_變數資料夾_(_var_)將變數傳入controller。

例如:
網址: /content/1
資料夾為:
/controllers/content/_id_/index.php
/views/content/_id_/index.blade.php  (非必要，如果controllers的index.php只是想吐json，可直接回傳array即可，同Laravel)
於index.php中，我們可以透過
$id = input("id"); 
取得資料夾的變數名稱
</pre>

## MVC
<pre>
URL: /demo
程式:
controllers/demo.php: 邏輯放置於此，return view()即可載入同名的view。
views/demo.blade.php: 採用Laravel blade樣版引擎規則
</pre>

## dotenv
<pre>
 DB_CONNECTION=mysql
 DB_HOST=db
 DB_PORT=3306
 DB_DATABASE=
 DB_USERNAME=
 DB_PASSWORD= 
</pre>
