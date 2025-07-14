# ステージ1: ビルド環境
FROM maven:3.9-eclipse-temurin-17-focal AS build
WORKDIR /app
COPY pom.xml .
RUN mvn dependency:go-offline
COPY src ./src
RUN mvn package -DskipTests

# ステージ2: 実行環境
FROM eclipse-temurin:17-jre-focal
WORKDIR /app

# ビルドステージから生成されたjarファイルのみをコピー
COPY --from=build /app/target/*.jar app.jar

EXPOSE 8080

# シンプルな起動コマンド
ENTRYPOINT ["java", "-jar", "app.jar"]