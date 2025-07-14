# ベースOSとして汎用的なUbuntuイメージを使用
FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive

# 必要なパッケージとPHPをインストール
RUN apt-get update && \
    apt-get install -y software-properties-common && \
    add-apt-repository ppa:ondrej/php -y && \
    apt-get update && \
    apt-get install -y \
        php8.3-fpm php8.3-swoole php8.3-mysql php8.3-mbstring \
        php8.3-xml php8.3-curl php8.3-zip unzip curl

# Composerをインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# アプリケーション用のディレクトリを作成
WORKDIR /var/www

# Laravel v10系の完全なプロジェクトを作成
RUN composer create-project --prefer-dist laravel/laravel:"^10.0" html

# プロジェクトディレクトリに移動
WORKDIR /var/www/html

# 必要なパッケージを個別に追加
RUN composer require guzzlehttp/guzzle laravel/octane laravel/sanctum
RUN php artisan octane:install --server=swoole --no-interaction

# Dockerfile内で直接ファイルを作成・上書きする
# --- routes/web.php を作成 ---
RUN echo '<?php \
use Illuminate\Support\Facades\Route; \
use App\Http\Controllers\ProductController; \
Route::get("/products/{id}", [ProductController::class, "show"]); \
Route::get("/fetch", [ProductController::class, "showFetchForm"]); \
Route::post("/fetch", [ProductController::class, "fetchPrice"]);' > routes/web.php

# --- app/Http/Controllers/ProductController.php を作成 ---
RUN echo '<?php \
namespace App\Http\Controllers; \
use Illuminate\Http\Request; \
use Illuminate\Support\Facades\Http; \
use Illuminate\Http\Client\ConnectionException; \
class ProductController extends Controller { \
    public function show(string $id) { \
        $productData = $this->getPriceFromApi($id); \
        return view("product.show", ["productId" => $id, "priceData" => $productData]); \
    } \
    public function showFetchForm() { \
        return view("fetch_form"); \
    } \
    public function fetchPrice(Request $request) { \
        $productId = $request->input("product_id", "none"); \
        $productData = $this->getPriceFromApi($productId); \
        return view("fetch_result", ["productId" => $productId, "priceData" => $productData]); \
    } \
    private function getPriceFromApi(string $productId) { \
        $javaApiHost = env("JAVA_API_HOST", "localhost"); \
        $data = []; \
        try { \
            $response = Http::timeout(3)->get("http://{$javaApiHost}:8080/price/{$productId}"); \
            if ($response->successful()) { $data = $response->json(); } \
            else { $data["message"] = "API Error: Status " . $response->status(); } \
        } catch (ConnectionException $e) { \
            $data["message"] = "Could not connect to the API server: " . $e->getMessage(); \
        } \
        return $data; \
    } \
}' > app/Http/Controllers/ProductController.php

# --- ビューファイルを作成 ---
RUN mkdir -p resources/views/product
RUN echo "<!DOCTYPE html><html><body><h1>商品ID: {{ \$productId }}</h1>@if(isset(\$priceData['price']))<p>価格: &yen;{{ number_format(\$priceData['price']) }}</p><p><small>(取得元: {{ \$priceData['source'] }})</small></p><p><small>(サーバー: {{ \$priceData['server_hostname'] ?? 'N/A' }})</small></p>@endif</body></html>" > resources/views/product/show.blade.php
RUN echo "<!DOCTYPE html><html><body><h1>価格取得フォーム</h1><form action=\"/fetch\" method=\"POST\">@csrf<label>商品ID:</label><input type=\"text\" name=\"product_id\" value=\"123\"><button type=\"submit\">価格を取得</button></form></body></html>" > resources/views/fetch_form.blade.php
RUN echo "<!DOCTYPE html><html><body><h1>取得結果</h1><p>商品ID: {{ \$productId }}</p><hr>@if(isset(\$priceData['price']))<h2>成功</h2><p>価格: &yen;{{ number_format(\$priceData['price']) }}</p><p><small>(取得元: {{ \$priceData['source'] }})</small></p><p><small>(サーバー: {{ \$priceData['server_hostname'] ?? 'N/A' }})</small></p>@else<h2 style=\"color:red;\">失敗</h2>@if(isset(\$priceData['message']))<p style=\"color:red;\"><small>理由: {{ \$priceData['message'] }}</small></p>@endif @endif <br><a href=\"/fetch\">もう一度試す</a></body></html>" > resources/views/fetch_result.blade.php

# 最後に所有権を変更
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "octane:start", "--host=0.0.0.0", "--port=8000"]