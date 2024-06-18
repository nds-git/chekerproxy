<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

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

        // 103.133.221.251:80
        // 47.252.29.28:11222
        $credentials = "147.28.145.213:9443";
        // $response = Http::withOptions(['proxy' => '47.252.29.28:11222'])->get('google.com');

        dd($validIpValue, $notValidIpValue);
        return redirect()->route('index');
    }
}
