<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use App\Models\Proxy;
use Exception;

/**
 * Контроллер для главной страницы
 */
class IndexController extends Controller
{
    /**
     * Показать главный шаблон страницы
     */
    public function show()
    {
        return view('index');
    }

    /**
     * Проверка прокси
     */
    public function checker(Request $request)
    {
        /*
         * получить данные из формы
         * создать массив из этих данных
         * валидация: проверить каждое значение на соответствие формату ip:port
         *   если не соответсвует, убрать из проверки (возможно отдельно вывести как не прошедшего проверку на формат)
         */

        $listIpChecker = explode(' ', $request->get('body'));

        $validIpValue = [];
        $notValidIpValue = [];
        foreach ($listIpChecker as $item)
        {
            $pattern = '/^((25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)\.){3}(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d):([0-9]{1,5})$/';

            if (preg_match($pattern, $item, $matches)) {
                $port = intval($matches[4]);
                ($port >= 0 && $port <= 65535)
                    ? $validIpValue[] = $item
                    : $notValidIpValue[] = $item;
            } else {
                $notValidIpValue[] = $item;
            }
        }

        /*
         * Выполнить сразу несколько HTTP-запросов одновременно
         * Получить ответ по каждому и упаковать в итоговый массив
         */
        $responses = Http::pool(function (Pool $pool) use ($validIpValue) {
            $res = [];

            foreach ($validIpValue as $item) {
                $res[$item] = $pool->withOptions(['proxy' => $item])->get('google.com');
            }

            return $res;
        });

        /**
         * Записать каждый ответ в таблицу Proxy
         */
        foreach ($responses as $item) {
            try {
                if (isset($item) && !empty($item->handlerStats())) {
                    $proxy = new Proxy();

                    $handlerStats = $item->handlerStats();

                    $downloadSpeed = $handlerStats['speed_download'] ?? 'N/A';

                    $data = [
                        'ip' => $handlerStats['primary_ip'],
                        'port' => $handlerStats['primary_port'],
                        'type' => $handlerStats['scheme'],
                        'status' => 'fail',
                        'speed' =>  $downloadSpeed . ' bytes/sec',
                        'real_ip' => $handlerStats['local_ip'] ?? 'N/A',
                    ];

                    if ($item->successful()) {
                        $data [] = ['status' => 'success'];
                    }

                    // Дополнительно можно использовать API для определения геолокации
                    // например, ipinfo.io или ipstack.com
                    $geoInfo = Http::get("http://ipinfo.io/{$handlerStats['local_ip']}/json")->json();
                    $country = $geoInfo['country'] ?? 'N/A';

                    $city = $geoInfo['city'] ?? 'N/A';
                    // конечно лучше город и страну сделать отдельным полем в таблице
                    $data [] = ['city' => $country . '/' . $city ];

                    $proxy->fill($data);
                    $proxy->save();

                }
            } catch (ConnectException $e) {
                // Проксисервер недоступен, данные уже инициализированы как 'Not Working'
            } catch (Exception $e) {
                // Общая ошибка, данные уже инициализированы как 'Not Working'
            }
        }

        return redirect()->route('index');
    }
}
