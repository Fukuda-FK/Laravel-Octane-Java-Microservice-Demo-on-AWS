<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ProductController extends Controller
{
    public function show(string $id)
    {
        // Java APIのEC2インスタンスのプライベートIPアドレスを指定します
        // 環境変数から取得するのが望ましいです
        $javaApiHost = env('JAVA_API_HOST', 'localhost'); // デフォルトはlocalhost
        $productData = [];

        try {
            $response = Http::timeout(3)->get("http://{$javaApiHost}:8080/price/{$id}");

            if ($response->successful()) {
                $productData = $response->json();
            } else {
                $productData['message'] = 'API Error: Status ' . $response->status();
            }
        } catch (ConnectionException $e) {
            $productData['message'] = 'Could not connect to the API server.';
        }

        // ビューにデータを渡して表示
        return view('product.show', [
            'productId' => $id,
            'priceData' => $productData
        ]);
    }
}