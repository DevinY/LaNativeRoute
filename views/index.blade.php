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
</body>
</html>