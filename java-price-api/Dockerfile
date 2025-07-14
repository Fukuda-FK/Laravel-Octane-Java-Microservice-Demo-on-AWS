# ビルドステージ
FROM openjdk:17-jdk-slim as build
WORKDIR /workspace
COPY . .
RUN ./mvnw package -DskipTests

# 実行ステージ
FROM openjdk:17-jre-slim
WORKDIR /app
COPY --from=build /workspace/target/*.jar app.jar
EXPOSE 8080
ENTRYPOINT ["java", "-jar", "app.jar"]