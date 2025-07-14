# Laravel Octane & Java Microservice Demo on AWS

これは、PHPのアプリケーションサーバーであるLaravel Octane (Swoole)で構築されたECサイトが、バックエンドのJava (Spring Boot) 製マイクロサービスから情報を取得する構成のデモアプリケーションです。

インフラの構築はAWS CloudFormationで自動化され、各アプリケーションはDockerコンテナとしてEC2インスタンス上で動作します。



---

## 🚀 アーキテクチャ

- **ECサイトフロント (Laravel Octane on PHP)**
  - ユーザーからのリクエストを受け付け、商品ページを表示します。
  - 価格情報を取得するため、バックエンドのJava APIを呼び出します。
- **価格情報API (Spring Boot on Java)**
  - 商品の価格情報を提供するシンプルなREST APIです。
- **インフラ (AWS)**
  - 2台のEC2インスタンス (PHP用, Java用)
  - インスタンス間の通信と外部からのHTTPアクセスを制御するセキュリティグループ
  - 上記全てを **CloudFormation (`infrastructure.yaml`)** で一括管理します。

---

## 🛠 必要要件

- [Git](https://git-scm.com/)
- [AWS CLI](https://aws.amazon.com/cli/) (設定済みであること)
- [Docker](https://www.docker.com/) (ローカルでのテスト用)
- AWSアカウントと、EC2キーペア

---

## ⚙️ セットアップ & デプロイ手順

### ステップ1: リポジトリのクローン

まず、このリポジトリをローカル環境にクローンします。

```bash
git clone [https://github.com/your-username/demo-project.git](https://github.com/your-username/demo-project.git)
cd demo-project
```

### ステップ2: CloudFormationによるインフラ構築

AWS CLIを使い、EC2インスタンスとセキュリティグループを作成します。

1.  **コマンドの実行**
    `infrastructure.yaml` ファイルがあるディレクトリで、以下のコマンドを実行します。`YourKeyPairName` はご自身がAWSに登録済みのEC2キーペア名に置き換えてください。

    ```bash
    aws cloudformation create-stack \
      --stack-name demo-app-stack \
      --template-body file://infrastructure.yaml \
      --parameters ParameterKey=KeyPairName,ParameterValue=YourKeyPairName
    ```

2.  **出力の確認**
    スタックの作成が完了するまで数分待った後、以下のコマンドでEC2インスタンスのIPアドレスを取得します。このIPアドレスは後のステップで使います。

    ```bash
    aws cloudformation describe-stacks --stack-name demo-app-stack --query "Stacks[0].Outputs"
    ```

### ステップ3: Java価格情報APIのデプロイ

1.  **SSH接続**
    CloudFormationの出力から `JavaApiPublicIp` を確認し、SSHで接続します。

    ```bash
    ssh -i /path/to/your-key.pem ec2-user@<JavaApiPublicIp>
    ```

2.  **アプリケーションの起動**
    接続後、リポジトリをクローンし、Dockerコンテナをビルド・実行します。

    ```bash
    git clone [https://github.com/your-username/demo-project.git](https://github.com/your-username/demo-project.git)
    cd demo-project/java-price-api

    # Dockerイメージをビルド
    docker build -t price-api .

    # Dockerコンテナを実行
    docker run -d -p 8080:8080 --name java-api-container price-api
    ```

### ステップ4: Laravel ECサイトのデプロイ

1.  **SSH接続**
    CloudFormationの出力から `PhpEcSitePublicIp` を確認し、SSHで接続します。

    ```bash
    ssh -i /path/to/your-key.pem ec2-user@<PhpEcSitePublicIp>
    ```

2.  **アプリケーションの起動**
    接続後、同様にリポジトリをクローンし、コンテナを起動します。このとき、`.env` ファイルにJava APIサーバーの **プライベートIP** を設定します。

    ```bash
    git clone [https://github.com/your-username/demo-project.git](https://github.com/your-username/demo-project.git)
    cd demo-project/laravel-ec-site

    # .envファイルを作成
    cp .env.example .env

    # .envファイルにJava APIのプライベートIPを追記
    # <JavaApiPrivateIp> はCloudFormationの出力で確認した値に置き換える
    echo "JAVA_API_HOST=<JavaApiPrivateIp>" >> .env

    # Dockerイメージをビルド
    docker build -t ec-site-app .

    # Dockerコンテナを実行
    docker run -d -p 80:8000 --env-file .env --name ec-site-container ec-site-app
    ```

---

## ✅ 動作確認

ブラウザを開き、以下のURLにアクセスします。
`PhpEcSitePublicIp` はCloudFormationの出力で確認したPHPサーバーのパブリックIPに置き換えてください。

`http://<PhpEcSitePublicIp>/products/123`

「価格: 1,980 円 (データ取得元: Java API)」と表示されれば成功です。

---

## 🧹 クリーンアップ

デモの確認が終わったら、不要な課金を避けるためにCloudFormationスタックを削除してください。これにより、作成されたEC2インスタンスとセキュリティグループが全て削除されます。

```bash
aws cloudformation delete-stack --stack-name demo-app-stack
```