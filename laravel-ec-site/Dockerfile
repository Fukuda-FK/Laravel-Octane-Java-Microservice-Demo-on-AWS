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

# ★★★ここからが重要★★★
# 必要なパッケージを個別に追加
RUN composer require guzzlehttp/guzzle laravel/octane laravel/sanctum
RUN php artisan octane:install --server=swoole

# 私たちが作ったファイルを、既存の構造を壊さずに「上書き」または「追加」する
COPY app/Http/Controllers/ProductController.php ./app/Http/Controllers/ProductController.php
COPY routes/web.php ./routes/web.php
COPY resources/views/product/show.blade.php ./resources/views/product/show.blade.php

# 最後に所有権を変更
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "octane:start", "--host=0.0.0.0", "--port=8000"]