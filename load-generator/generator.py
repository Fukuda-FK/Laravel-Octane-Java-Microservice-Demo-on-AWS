import os
import time
import requests
import random

# 環境変数から設定を読み込む
PHP_APP_URL_BASE = os.getenv('PHP_APP_URL', 'http://localhost/products/')
JAVA_APP_URL_BASE = os.getenv('JAVA_APP_URL', 'http://localhost:8080/price/')
INTERVAL_SECONDS = int(os.getenv('INTERVAL_SECONDS', 15))

print("--- Load Generator Started ---")
print(f"Target PHP URL: {PHP_APP_URL_BASE}<product_id>")
print(f"Target Java URL: {JAVA_APP_URL_BASE}<product_id>")
print(f"Interval: {INTERVAL_SECONDS} seconds")
print("----------------------------")

while True:
    try:
        # ランダムな商品IDを生成
        product_id = random.randint(100, 999)

        # PHPアプリケーションにリクエスト
        php_url = f"{PHP_APP_URL_BASE}{product_id}"
        print(f"-> Calling PHP App: {php_url}")
        php_response = requests.get(php_url, timeout=5)
        print(f"<- PHP App responded with status: {php_response.status_code}")

        # Javaアプリケーションにリクエスト
        java_url = f"{JAVA_APP_URL_BASE}{product_id}"
        print(f"-> Calling Java App: {java_url}")
        java_response = requests.get(java_url, timeout=5)
        print(f"<- Java App responded with status: {java_response.status_code}")
        print(f"   Response body: {java_response.json()}")

    except requests.exceptions.RequestException as e:
        print(f"!! An error occurred: {e}")
    
    finally:
        print(f"--- Waiting for {INTERVAL_SECONDS} seconds... ---\n")
        time.sleep(INTERVAL_SECONDS)