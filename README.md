# Laravel Octane & Java Microservice Demo on AWS

## 1. 概要

このプロジェクトは、PHPのアプリケーションサーバー **Laravel Octane (Swoole)** で構築されたフロントエンドアプリケーションが、バックエンドの **Java (Spring Boot)** 製マイクロサービスからAPI経由で情報を取得する、モダンなWebアプリケーションのデモです。

インフラは **AWS CloudFormation** によってコード管理され、各アプリケーションは **Docker** コンテナとしてEC2インスタンス上で動作します。この構成は、PHPのライフサイクルの違い（オブジェクトの再利用）や、複数言語サービス間の連携といった課題に対する実践的な解決策を提示します。

## 2. システムアーキテクチャ

本システムは、以下のコンポーネントで構成されます。

[Image of a modern web application architecture diagram]

-   **ECサイトフロント (PHP / Laravel Octane)**
    -   ユーザーからのリクエストを受け付け、商品ページを表示します。
    -   バックエンドの価格情報APIに対してHTTPリクエストを送信し、動的に価格データを取得します。
    -   CPUアーキテクチャの互換性問題を解決するため、UbuntuベースのカスタムDockerfileでビルドされます。

-   **価格情報API (Java / Spring Boot)**
    -   商品IDに基づき、価格情報をJSON形式で返すシンプルなRESTful APIです。
    -   マイクロサービスとしてフロントエンドから独立しており、個別のデプロイが可能です。

-   **インフラストラクチャ (AWS)**
    -   **VPC / Subnet**: 指定された既存のVPC・サブネット内にリソースを構築します。
    -   **EC2**: 2台のAmazon Linux 2023インスタンス。それぞれPHP用、Java用として稼働します。
    -   **Security Group**: 既存のセキュリティグループを使用し、外部からのHTTP(80)アクセスと、インスタンス間のAPI通信(8080)を許可します。
    -   **CloudFormation**: 上記のAWSリソースは、`infrastructure.yaml`によって一元的に管理・デプロイされます。

## 3. 技術スタック

-   **バックエンド (フロントエンド側)**: PHP 8.3, Laravel 10, Octane (Swoole)
-   **バックエンド (API側)**: Java 17, Spring Boot 3
-   **コンテナ技術**: Docker
-   **クラウドプラットフォーム**: AWS (EC2)
-   **インフラ管理**: AWS CloudFormation
-   **ベースOS (コンテナ)**: Ubuntu 22.04

## 4. デプロイ・ワークフロー

1.  **コード管理**: アプリケーションの全ソースコード（Dockerfile含む）は、単一のGitリポジトリ（GitHub）で管理します。
2.  **インフラ構築**: `infrastructure.yaml` を利用し、`aws cloudformation create-stack` コマンドでAWS上にインフラを一括構築します。
3.  **アプリケーションのデプロイ**:
    -   各EC2インスタンスにSSHで接続します。
    -   GitHubからソースコードを `git clone` します。
    -   各アプリケーションのディレクトリに移動し、`docker build` でコンテナイメージをビルドします。
    -   `docker run` でコンテナを起動します。Laravel側では、`.env`ファイルにJavaサーバーのプライベートIPを設定します。