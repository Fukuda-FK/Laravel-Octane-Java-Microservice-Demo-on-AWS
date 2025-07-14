<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>商品詳細</title>
    <style>
        body { font-family: sans-serif; padding: 2em; }
        .container { max-width: 600px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>商品詳細ページ</h1>
        <p><strong>商品ID:</strong> {{ $productId }}</p>
        
        @if(isset($priceData['price']))
            <p><strong>価格:</strong> {{ number_format($priceData['price']) }} 円</p>
            <p><small>(データ取得元: {{ $priceData['source'] }})</small></p>
        @else
            <p class="error">価格情報を取得できませんでした。</p>
            @if(isset($priceData['message']))
                <p class="error"><small>理由: {{ $priceData['message'] }}</small></p>
            @endif
        @endif
    </div>
</body>
</html>