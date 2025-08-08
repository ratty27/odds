# ローカルデプロイメントガイド（Git Push不要）

## 🎯 概要

このガイドでは、**gitにpushすることなく**完全にローカルでoddsアプリケーションをテスト・デプロイする方法を説明します。バグのあるコードをリモートリポジトリにpushする心配がありません。

## ✅ 実証済み：完全ローカルデプロイメント

現在のDocker環境で**実際にテスト済み**：
- ✅ ローカルファイルから直接Dockerビルド
- ✅ データベースマイグレーション実行
- ✅ Laravel アプリケーション正常動作確認
- ✅ http://localhost:8080 でアクセス可能

## 🚀 方法1: 既存設定を使用（最も簡単）

### 基本的な使用方法
```bash
cd docker
docker-compose -f docker-compose.local.yml up --build -d
```

### 初回セットアップ
```bash
# データベースマイグレーション
docker-compose -f docker-compose.local.yml exec app php artisan migrate

# アプリケーションキー生成
docker-compose -f docker-compose.local.yml exec app php artisan key:generate
```

### アクセス
- アプリケーション: http://localhost:8080
- MySQL: localhost:3306

## 🔄 ライブリロード開発ワークフロー

### コード変更の即座反映
以下のディレクトリはホストとコンテナ間で同期されます：
- `app/` - PHPアプリケーションコード
- `resources/` - ビューとアセット
- `routes/` - ルート定義
- `config/` - 設定ファイル
- `public/` - 公開ファイル
- `storage/` - ログとキャッシュ

### 変更テストの流れ
1. **コード編集** → ローカルファイルを直接編集
2. **即座反映** → ブラウザでリロードして確認
3. **デバッグ** → ログとエラー詳細を確認
4. **満足したら** → gitにcommit & push

## 🛠️ デバッグとトラブルシューティング

### デバッグモード有効化
```bash
docker-compose -f docker-compose.local.yml exec app sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' /var/www/html/.env
docker-compose -f docker-compose.local.yml exec app php artisan config:clear
```

### ログ確認
```bash
# アプリケーションログ
docker-compose -f docker-compose.local.yml logs app

# Laravelログ
docker-compose -f docker-compose.local.yml exec app tail -f /var/www/html/storage/logs/laravel.log

# Nginxアクセスログ
docker-compose -f docker-compose.local.yml exec app tail -f /var/log/nginx/access.log
```

### キャッシュクリア
```bash
docker-compose -f docker-compose.local.yml exec app php artisan config:clear
docker-compose -f docker-compose.local.yml exec app php artisan cache:clear
docker-compose -f docker-compose.local.yml exec app php artisan view:clear
```

### データベース操作
```bash
# マイグレーション状態確認
docker-compose -f docker-compose.local.yml exec app php artisan migrate:status

# 新しいマイグレーション実行
docker-compose -f docker-compose.local.yml exec app php artisan migrate

# データベース接続確認
docker-compose -f docker-compose.local.yml exec mysql mysql -u odds_user -plocal_password odds
```

## 🔧 高度な設定

### 環境変数カスタマイズ
`docker/.env.local`ファイルを編集：
```env
APP_ENV=local
APP_DEBUG=true
DB_DATABASE=odds
DB_USERNAME=odds_user
DB_PASSWORD=local_password
```

### 本番環境シミュレーション
```bash
# 本番モードでテスト
docker-compose -f docker-compose.local.yml exec app sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' /var/www/html/.env
docker-compose -f docker-compose.local.yml exec app sed -i 's/APP_ENV=local/APP_ENV=production/' /var/www/html/.env
docker-compose -f docker-compose.local.yml exec app php artisan config:clear
```

## 📊 パフォーマンステスト

### 負荷テスト
```bash
# Apache Bench でテスト
docker run --rm --network host httpd:alpine ab -n 100 -c 10 http://localhost:8080/

# または curl で簡単テスト
for i in {1..10}; do curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" http://localhost:8080/; done
```

## 🔄 開発ワークフローの例

### 新機能開発の流れ
1. **ローカル環境起動**
   ```bash
   cd docker
   docker-compose -f docker-compose.local.yml up -d
   ```

2. **コード変更**
   - ローカルファイルを直接編集
   - IDE/エディタで通常通り開発

3. **即座テスト**
   - ブラウザでhttp://localhost:8080にアクセス
   - 変更が即座に反映される

4. **デバッグ**
   ```bash
   # エラーログ確認
   docker-compose -f docker-compose.local.yml logs app --tail=50
   ```

5. **満足したらコミット**
   ```bash
   git add .
   git commit -m "新機能追加"
   git push origin docker
   ```

## 🎯 利点

### 安全性
- ❌ バグのあるコードをリモートにpushしない
- ✅ ローカルで完全にテスト後にpush
- ✅ 他の開発者に影響を与えない

### 効率性
- ⚡ ファイル変更が即座に反映
- ⚡ Docker再ビルド不要（ほとんどの変更で）
- ⚡ 高速な開発サイクル

### 柔軟性
- 🔧 複数の環境設定を簡単切り替え
- 🔧 本番環境シミュレーション可能
- 🔧 詳細なデバッグ情報アクセス

## 🚨 注意事項

### データ永続化
- MySQLデータは`mysql_local_data`ボリュームに保存
- コンテナ削除してもデータは保持される
- 完全リセットしたい場合：
  ```bash
  docker-compose -f docker-compose.local.yml down -v
  ```

### ポート競合
- 8080番ポートが使用中の場合、docker-compose.local.ymlで変更：
  ```yaml
  ports:
    - "8081:80"  # 8081に変更
  ```

この方法により、**gitにpushすることなく**完全にローカルでoddsアプリケーションの開発・テスト・デプロイが可能になります。
