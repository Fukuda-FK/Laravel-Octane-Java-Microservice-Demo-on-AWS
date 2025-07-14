承知いたしました。現在のREADMEに、各アプリケーションコンテナの起動・停止コマンドの詳細セクションを追加します。

-----

# Laravel + Java マイクロサービス on AWS (EC2)

## 1\. 概要

このプロジェクトは、PHPのアプリケーションサーバー **Laravel Octane (Swoole)** で構築されたフロントエンドと、バックエンドの **Java (Spring Boot)** 製マイクロサービスが連携するWebアプリケーションのデモです。

インフラは **AWS CloudFormation** によってコード管理され、各アプリケーション（負荷生成ツール含む）は **Docker** コンテナとしてEC2インスタンス上で動作します。一連のトラブルシューティングを経て、CPUアーキテクチャの互換性問題や、フレームワークのバージョン不整合、デプロイ手順などを解決した、安定的で再現性の高い構成となっています。

-----

## 2\. システムアーキテクチャ

本システムは、以下の3つの主要コンポーネントで構成されます。

  - **ECサイトフロント (Laravel / PHP)**

      * ユーザーからのリクエストを受け付け、商品ページや価格取得フォームを表示します。
      * バックエンドの価格情報APIに対してHTTPリクエストを送信し、動的にデータを取得します。
      * CPUアーキテクチャ互換のため、UbuntuベースのカスタムDockerfileでビルドされます。

  - **価格情報API (Spring Boot / Java)**

      * 商品IDに基づき、価格情報と自身のサーバーホスト名をJSON形式で返すRESTful APIです。
      * フロントエンドから独立したマイクロサービスとして稼働します。

  - **負荷生成アプリ (Python)**

      * テスト環境として、上記2つのアプリケーションに定期的にHTTPリクエストを送信し、トランザクションを発生させます。

  - **インフラストラクチャ (AWS)**

      * **EC2**: 3台のAmazon Linux 2023インスタンス。それぞれPHP用、Java用、負荷生成用に割り当てられます。
      * **CloudFormation**: 上記EC2インスタンスは、`infrastructure.yaml`によって一元的に管理・デプロイされます。

-----

## 3\. 技術スタック

  - **フロントエンド側**: PHP 8.3, Laravel 10, Octane (Swoole)
  - **API側**: Java 17, Spring Boot 3
  - **負荷生成**: Python 3.9
  - **コンテナ技術**: Docker
  - **クラウド**: AWS (EC2)
  - **インフラ管理**: AWS CloudFormation

-----

## 4\. デプロイ・ワークフロー

1.  **コード管理**: アプリケーションの全ソースコード（Dockerfile含む）は、単一のGitリポジトリで管理します。
2.  **インフラ構築**: (初回のみ) `infrastructure.yaml` を利用し、`aws cloudformation create-stack` コマンドでAWS上にEC2インスタンス群を一括構築します。
3.  **アプリケーションのデプロイ**:
      * 各EC2インスタンスにSSHで接続します。
      * GitHubからソースコードを `git clone` します。
      * 各アプリケーションのディレクトリに移動し、`docker build` でコンテナイメージをビルドします。
      * `docker run` でコンテナを起動します。この際、Laravel側にはJavaサーバーのプライベートIPを、負荷生成アプリにはLaravelとJavaサーバーのプライベートIPを、それぞれ環境変数で渡します。

-----

## 5\. アプリケーションコンテナのデプロイ手順 🚀

各EC2インスタンスにSSH接続後、以下の手順でアプリケーションコンテナをビルド・起動します。

### 5.1. ECサイトフロント (Laravel / PHP)

PHPアプリケーションをデプロイするEC2インスタンスで実行します。

1.  **リポジトリのクローン**:

    ```bash
    git clone <YOUR_REPOSITORY_URL>
    cd laravel-ec-site/ # Laravelプロジェクトのルートディレクトリに移動
    ```

2.  **Dockerイメージのビルド**:
    `.env`ファイルに`APP_KEY`が設定されていることを確認してください（未設定の場合は`php artisan key:generate`を実行）。

    ```bash
    docker build -t ec-site-app:latest .
    ```

      * `Dockerfile`内で`composer create-project`を使用しているため、**ローカルの`laravel-ec-site`ディレクトリにLaravelのコアファイル（`artisan`など）がなくてもビルドは成功します。**

3.  **Dockerコンテナの起動**:
    Laravel Octaneはデフォルトでポート`8000`を使用します。ホストのポート`80`をコンテナの`8000`にマッピングします。
    `JAVA_API_HOST`には、**Javaマイクロサービスが稼働しているEC2インスタンスのプライベートIPアドレス**を指定してください。

    ```bash
    docker run -d \
      --name ec-site-app-container \
      -p 80:8000 \
      --env JAVA_API_HOST="<JavaサーバーのプライベートIP>" \
      ec-site-app:latest
    ```

      * **重要**: `Dockerfile`の`composer create-project`でイメージ内にLaravelプロジェクトが作成されているため、**ここでは`"-v "$(pwd)":/var/www/html"`のボリュームマウントは不要です**。これにより、イメージ内のアプリケーションがそのまま実行されます。

4.  **起動確認**:

    ```bash
    docker ps
    docker logs ec-site-app-container # ログを確認してエラーがないかチェック
    ```

### 5.2. 価格情報API (Spring Boot / Java)

JavaマイクロサービスをデプロイするEC2インスタンスで実行します。

1.  **リポジトリのクローン**:

    ```bash
    git clone <YOUR_REPOSITORY_URL>
    cd java-price-api/ # Javaプロジェクトのルートディレクトリに移動
    ```

2.  **Dockerイメージのビルド**:

    ```bash
    docker build -t java-price-api:latest .
    ```

3.  **Dockerコンテナの起動**:
    Javaアプリケーションはデフォルトでポート`8080`を使用します。

    ```bash
    docker run -d \
      --name java-price-api-container \
      -p 8080:8080 \
      java-price-api:latest
    ```

4.  **起動確認**:

    ```bash
    docker ps
    docker logs java-price-api-container # ログを確認してエラーがないかチェック
    ```

### 5.3. 負荷生成アプリ (Python)

負荷生成ツールをデプロイするEC2インスタンスで実行します。

1.  **リポジトリのクローン**:

    ```bash
    git clone <YOUR_REPOSITORY_URL>
    cd load-generator/ # 負荷生成プロジェクトのルートディレクトリに移動
    ```

2.  **Dockerイメージのビルド**:

    ```bash
    docker build -t load-generator-app:latest .
    ```

3.  **Dockerコンテナの起動**:
    `LARAVEL_APP_IP`には**Laravelアプリケーションが稼働しているEC2インスタンスのプライベートIPアドレス**を、`JAVA_API_HOST`には**Javaマイクロサービスが稼働しているEC2インスタンスのプライベートIPアドレス**を指定してください。

    ```bash
    docker run -d \
      --name load-generator \
      --env LARAVEL_APP_IP="<LaravelサーバーのプライベートIP>" \
      --env JAVA_API_HOST="<JavaサーバーのプライベートIP>" \
      load-generator-app:latest
    ```

4.  **起動確認**:

    ```bash
    docker ps
    docker logs load-generator -f # リアルタイムで負荷生成状況を確認
    ```

-----

## 6\. アプリケーションの管理コマンド ⚙️

各アプリケーションコンテナの起動、停止、状態確認、ログ表示のためのコマンドです。

### 6.1. ECサイトフロント (Laravel / PHP)

  - **コンテナを起動する**:

    ```bash
    docker run -d \
      --name ec-site-app-container \
      -p 80:8000 \
      --env JAVA_API_HOST="<JavaサーバーのプライベートIP>" \
      ec-site-app:latest
    ```

    *既に起動している場合は、ポート競合エラーが出るため、一度停止・削除してください。*

  - **コンテナを停止する**:

    ```bash
    docker stop ec-site-app-container
    ```

  - **コンテナを削除する**:

    ```bash
    docker rm ec-site-app-container
    ```

  - **コンテナの状態を確認する**:

    ```bash
    docker ps # 実行中のコンテナのみ
    docker ps -a # 全てのコンテナ (停止中のものも含む)
    ```

  - **コンテナのログを見る**:

    ```bash
    docker logs ec-site-app-container
    docker logs -f ec-site-app-container # リアルタイムでログを追跡
    ```

### 6.2. 価格情報API (Spring Boot / Java)

  - **コンテナを起動する**:

    ```bash
    docker run -d \
      --name java-price-api-container \
      -p 8080:8080 \
      --env SERVER_HOSTNAME=$(hostname) \
      java-price-api:latest
    ```

    *`SERVER_HOSTNAME=$(hostname)` は、APIのレスポンスにEC2インスタンスのホスト名を含めるためのものです。*
    *すでに起動している場合は、ポート競合エラーが出るため、一度停止・削除してください。*

  - **コンテナを停止する**:

    ```bash
    docker stop java-price-api-container
    ```

  - **コンテナを削除する**:

    ```bash
    docker rm java-price-api-container
    ```

  - **コンテナの状態を確認する**:

    ```bash
    docker ps # 実行中のコンテナのみ
    docker ps -a # 全てのコンテナ (停止中のものも含む)
    ```

  - **コンテナのログを見る**:

    ```bash
    docker logs java-price-api-container
    docker logs -f java-price-api-container # リアルタイムでログを追跡
    ```

### 6.3. 負荷生成アプリ (Python)

  - **コンテナを起動する**:

    ```bash
    docker run -d \
      --name load-generator \
      --env LARAVEL_APP_IP="<LaravelサーバーのプライベートIP>" \
      --env JAVA_API_HOST="<JavaサーバーのプライベートIP>" \
      load-generator-app:latest
    ```

    *すでに起動している場合は、ポート競合エラーが出るため、一度停止・削除してください。*

  - **コンテナを停止する**:

    ```bash
    docker stop load-generator
    ```

  - **コンテナを削除する**:

    ```bash
    docker rm load-generator
    ```

  - **コンテナの状態を確認する**:

    ```bash
    docker ps # 実行中のコンテナのみ
    docker ps -a # 全てのコンテナ (停止中のものも含む)
    ```

  - **コンテナのログを見る**:

    ```bash
    docker logs load-generator
    docker logs -f load-generator # リアルタイムでログを追跡
    ```

-----

## 7\. アプリケーションへのアクセス

`<PHPサーバーのパブリックIP>` や `<JavaサーバーのパブリックIP>` の部分は、CloudFormationの出力結果で確認した実際のIPアドレスに置き換えてください。

### **PHP ECサイト (ブラウザでアクセス)** 🖥️

こちらがユーザーが主に操作するアプリケーションです。

  - **価格取得フォームページ**:
    手動でJava APIへの連携をテストするために作成した機能です。
    `http://<PHPサーバーのパブリックIP>/fetch`

  - **商品詳細ページ**:
    負荷生成アプリが定期的にアクセスするページです。
    `http://<PHPサーバーのパブリックIP>/products/123`

### **Java価格情報API (テスト用)** ⚙️

バックエンドのAPIですが、ブラウザで直接呼び出してテストすることができます。

  - `http://<JavaサーバーのパブリックIP>:8080/price/456`

### **負荷生成アプリ (Python)** 🏃

このアプリケーションには、アクセス用のURLはありません。バックグラウンドで動作し、他のアプリケーションにリクエストを送信し続けます。

  - 動作状況は、**負荷生成サーバー**にSSH接続し、以下のコマンドで確認できます。
    `docker logs load-generator -f`