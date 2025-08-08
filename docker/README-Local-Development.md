# ローカル開発環境セットアップ（Git Push不要）

このガイドでは、gitにpushすることなくローカルでoddsアプリケーションをテストする方法を説明します。

## 方法1: 既存のDocker環境を使用（推奨）

現在のDocker設定は既にローカル開発に最適化されています：

### 基本的な使用方法
```bash
cd docker
cp .env.docker .env.docker.local
# .env.docker.localを編集してパスワードを設定
docker-compose --env-file .env.docker.local up --build
```

### ライブリロード開発
コードを変更すると自動的に反映されます：
- PHPファイルの変更は即座に反映
- storageディレクトリはホストとコンテナ間で同期
- データベースの変更は永続化

### デバッグモード
```bash
# デバッグモードを有効にする
docker-compose exec app sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' /var/www/html/.env
docker-compose exec app php artisan config:clear
```

## 方法2: 完全にローカルなDocker環境

git履歴に依存しない完全にローカルな環境を作成：

### ローカル専用Dockerfile
```dockerfile
# docker/Dockerfile.local
FROM php:8.1-fpm

# システム依存関係をインストール
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip cron nginx \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能をインストール
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd xml

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリを設定
WORKDIR /var/www/html

# アプリケーションファイルをコピー（.gitignoreを無視）
COPY . .

# 依存関係をインストール
RUN composer install --no-dev --optimize-autoloader

# 適切な権限を設定
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && chown -R www-data:www-data /var/www/html/storage

# Nginxを設定
COPY docker/nginx.conf /etc/nginx/sites-available/default

# cronジョブを設定
COPY docker/crontab /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron && crontab /etc/cron.d/laravel-cron

# 起動スクリプト
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
```

### ローカル専用docker-compose
```yaml
# docker/docker-compose.local.yml
version: '3.8'

services:
  app:
    build: 
      context: ..
      dockerfile: docker/Dockerfile.local
    ports:
      - "8080:80"
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=odds
      - DB_USERNAME=odds_user
      - DB_PASSWORD=local_password
      - APP_ENV=local
      - APP_DEBUG=true
    depends_on:
      - mysql
    volumes:
      - ../storage:/var/www/html/storage:delegated
      - ../public:/var/www/html/public:delegated
      - ../resources:/var/www/html/resources:delegated
      - ../app:/var/www/html/app:delegated
      - ../routes:/var/www/html/routes:delegated

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: odds
      MYSQL_USER: odds_user
      MYSQL_PASSWORD: local_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_local_data:/var/lib/mysql

volumes:
  mysql_local_data:
```

## 方法3: ホットリロード開発環境

最も高速な開発体験のための設定：

```bash
# ホットリロード用の起動
cd docker
docker-compose -f docker-compose.local.yml up --build

# 別ターミナルでファイル監視
docker-compose -f docker-compose.local.yml exec app php artisan serve --host=0.0.0.0 --port=8000
```

## 方法4: 本番環境シミュレーション

本番環境に近い状態でのテスト：

```bash
# 本番モードでビルド
docker build -f docker/Dockerfile.local -t odds-local:latest ..

# 本番環境変数でテスト
docker run -p 8080:80 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e DB_HOST=host.docker.internal \
  odds-local:latest
```

## トラブルシューティング

### よくある問題と解決方法

1. **権限エラー**
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

2. **キャッシュクリア**
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
```

3. **データベース接続エラー**
```bash
docker-compose exec app php artisan migrate:status
docker-compose exec mysql mysql -u root -p -e "SHOW DATABASES;"
```

4. **ログの確認**
```bash
docker-compose logs app
docker-compose exec app tail -f /var/www/html/storage/logs/laravel.log
```

## 利点

- **Git履歴不要**: ローカルファイルから直接ビルド
- **高速開発**: ファイル変更が即座に反映
- **安全**: バグのあるコードをリモートにpushする必要なし
- **柔軟性**: 複数の環境設定を簡単に切り替え可能
- **デバッグ**: 詳細なエラー情報とログアクセス

この方法により、gitにpushすることなく完全にローカルでoddsアプリケーションの開発とテストが可能になります。
