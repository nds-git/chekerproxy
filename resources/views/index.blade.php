<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Proxy Checker </title>
</head>
<body>
    <div style="margin: 0 auto; width: 750px;min-height: 100%; border: 1px dotted blueviolet;padding: 20px">
        <h1>Proxy Checker</h1>
        @if ($errors->any())
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{ html()->modelForm([1,2,3], 'POST', route('index.checker'))->open() }}
            {{  html()->label('Введите список ip', 'name') }}
            <br/>
            {{  html()->textarea('body')->text('103.133.221.251:80 147.28.145.213:9443 103.133.221.251:80 dd.km.dd:5 147.28.145.213:65536') }}
            <br/>
            {{ html()->submit('Проверить ip') }}
        {{ html()->closeModelForm() }}

        <div style="margin: 20px 0;">
            @if(isset($data))
                <table>
                    <tr>
                        <th>Ip адрес</th>
                        <th>Номер порта</th>
                        <th>Тип прокси</th>
                        <th>Статус</th>
                        <th>Скорость скачивания</th>
                        <th>Реальный ip</th>
                    <tr>
                @foreach($data as $item)
                    <tr>
                        <td>{{ $item['ip'] }}</td>
                        <td>{{ $item['port'] }}</td>
                        <td>{{ $item['type'] }}</td>
                        <td>{{ $item['status'] }}</td>
                        <td>{{ $item['speed'] }}</td>
                        <td>{{ $item['real_ip'] }}</td>
                    </tr>
                @endforeach
                </table>
            @endif
        </div>

    </div>
</body>
</html>
