<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TickerController extends Controller
{
    private $commission = 0.02; // 2%
    public function index(Request $request)
{
    $method = $request->input('method');
    switch ($method) {
        case 'rates':
            return $this->rates($request);
        case 'convert':
            return $this->convert($request);
        default:
            return response()->json(['status' => 'error', 'code' => 400, 'message' => 'Unknown method'], 400);
    }
}
    private function rates(Request $request)
    {
        try {
            $response = Http::get('https://blockchain.info/ticker');
            $rates = $response->json();

            // Применяем комиссию
            foreach ($rates as $currency => $data) {
                $rates[$currency]['last'] *= (1 + $this->commission);
            }

            // Сортировка
            uasort($rates, function ($a, $b) {
                return $a['last'] <=> $b['last'];
            });

            // Вывод заданной валюты
            $currency = $request->input('currency');
            if ($currency) {
                $rates = $rates[$currency];
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $rates
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'=> 'error',
                'code'=> 403,
                'message'=> 'Invalid token'], 403);
        }

    }

    private function convert(Request $request)
    {
        try {
            //Делаем запрос по ссылке и получаем ответ
            $response = Http::get('https://blockchain.info/ticker');
            $rates = $response->json();
            
            $from = $request->input('currency_from');
            $to = $request->input('currency_to');
            $value = (float) $request->input('value');
            
            if ($value < 0.01) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'Minimum exchange value is 0.01'
                ], 403);
            }
            //Проверяем имеется ли тикер по заданой валюте и проводим калькуляцию с учетом комиссии
            if (isset($rates[$from]) || isset($rates[$to])) {
                if ($from === "BTC") {
                    $rate = $rates[$to]['last'] * (1 + $this->commission);
                    $converted_value = round($value * $rate, 2);
                } else {
                    $rate = $rates[$from]['last'] * (1 - $this->commission);
                    $converted_value = round($value / $rate, 10);
                }
                
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'data' => [
                        'currency_from' => $from,
                        'currency_to' => $to,
                        'value' => $value,
                        'converted_value' => $converted_value,
                        'rate' => $rate
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'Invalid currency'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Invalid token'
            ], 403);
        }
    }
}
