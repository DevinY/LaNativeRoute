<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{$title ?? ""}}</title>
</head>
<body>
    <ul>
        @foreach($features as $feature)
        <li>{{$feature}}</li>
        @endforeach
    </ul>

    <h4>變數資料夾(option)</h4>
    <div style="margin-left:25px;">
        LaNativeRoute 使用獨創的<b>_變數資料夾_</b>(_var_)將變數傳入controller。<br/><br/>
        例如:<br/>
        網址: /content/1<br/>
        資料夾為:/content/_id_/index.php<br/>

        於index.php中，我們可以透過<br/>
        $id = input("id"); <br/>
        取得資料夾的變數名稱
    </div>
    <h4>MVC架構</h4>
    <div style="margin-left:25px;">
        URL: <b>/demo</b><br/>
        程式:<br/>
        controllers/demo.php: 邏輯放置於此，return view()即可載入同名的view。<br/>
        views/demo.blade.php: 採用Laravel blade樣版引擎規則<br/>
    </div>
    <h4>環境dotenv支援</h4>
    <div style="margin-left:25px;">
        <pre>
           DB_CONNECTION=mysql
           DB_HOST=db
           DB_PORT=3306
           DB_DATABASE=
           DB_USERNAME=
           DB_PASSWORD= 
       </pre>
   </div>
</body>
</html>