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
    public function show(Request $request)
    {
        $data = $request->query('data', null);

        return !is_null($data)
            ? view('index',  compact('data'))
            : view('index');
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
        $res = [];
        foreach ($responses as $item) {
            try {
                if (method_exists($item, 'handlerStats') && !empty($item->handlerStats())) {
                    $proxy = new Proxy();

                    $handlerStats = $item->handlerStats();

                    $downloadSpeed = $handlerStats['speed_download'] ?? null;

                    // Дополнительно можно использовать API для определения геолокации
                    // например, ipinfo.io или ipstack.com
                    $geoInfo = Http::get("http://ipinfo.io/{$handlerStats['local_ip']}/json")->json();

                    $data = [
                        'ip' => $handlerStats['primary_ip'],
                        'port' => $handlerStats['primary_port'],
                        'type' => $handlerStats['scheme'],
                        'status' => 'fail',
                        'speed' =>  $downloadSpeed . ' bytes/sec',
                        'real_ip' => $handlerStats['local_ip'] ?? null,
                        'city' => !empty($geoInfo['city']) ? $geoInfo['city'] : null
                    ];

                    if ($item->successful()) {
                        $data['status'] = 'success';
                    }

                    $proxy->fill($data);
                    $proxy->save();
                    // Cобрать итоговый массив объектов
                    $res [] = $data;
                }
            } catch (ConnectException $e) {
                response()->json(['error' => 'Connection failed: ' . $e->getMessage()], 200);
                continue;
            } catch (Exception $e) {
                response()->json(['error' => 'Connection failed: ' . $e->getMessage()], 200);
                continue;
            }
        }

        return redirect()->route('index', ['data' => $res]);
    }
}
