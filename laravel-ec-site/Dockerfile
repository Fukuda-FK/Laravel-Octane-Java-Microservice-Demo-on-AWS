FROM ghcr.io/laravel/octane:8.2-swoole

# composer.jsonとcomposer.lockを先にコピーして、依存関係をインストール
COPY composer.json composer.lock /var/www/html/
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader

# アプリケーションコードをコピー
COPY . /var/www/html

# 所有権の変更と最適化
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Octaneの起動コマンド
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]