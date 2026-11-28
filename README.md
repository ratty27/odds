# Odds について

Odds はあくまでも「お遊び」としてレースの着順などを予想し、賭けを行うシステムです。
課金などには一切対応しておらず、得られるポイントもこのシステム上で賭けに使う以外の使い道はありません。
配当ポイントも厳密に賭けポイントを分配していないため、実際の賭けの計算ツールとしても使用できません。
あくまでも「お遊び」として利用することを目的としています。

# 必要開発環境

* PHP 8.3+（推奨: PHP 8.5 / Laravel 13）
* MySQL
* ※memcached/redis は使用していません。

# 動作環境

* Chrome/Firefox
* スマホの縦長表示対応

# インストール

* Web サーバ、PHP 8.3+（推奨 8.5）、MySQL を準備
  * MySQL は予めユーザ作成、空のデータベースを作成しておいてください。
  * PHP はコマンドラインから実行できるようパスを通しておいてください。
* odds ソースを展開 (公開するのは public ディレクトリだけになるよう Web サーバを設定)
* [composer](https://getcomposer.org/download/) がなければインストール 
* odds のルートディレクトリで下記を実行
```
$ composer install
```
* .env ファイルを編集し、最低限以下を入力
  * APP_URL=<公開 URL>
  * DB_CONNECTION=mysql
  * DB_HOST=<MySQL のホスト名>
  * DB_PORT=<MySQL のポート番号>
  * DB_DATABASE=<MySQL のデータベース名>
  * DB_USERNAME=<MySQL のユーザ名>
  * DB_PASSWORD=<MySQL のパスワード>
  * MAIL_MAILER=smtp
  * MAIL_HOST=<smtp サーバ>
  * MAIL_PORT=<smtp サーバのポート番号>
  * MAIL_USERNAME=<smtp サーバのユーザ名>
  * MAIL_PASSWORD=<smtp サーバのパスワード>
  * MAIL_ENCRYPTION=<"ssl" など>
  * MAIL_FROM_ADDRESS=<メールアドレス>
  * TURNSTILE_SITE_KEY=<Cloudflare Turnstile のサイトキー>（本番では必須）
  * TURNSTILE_SECRET_KEY=<Cloudflare Turnstile のシークレットキー>（本番では必須）
* APP_KEY を作成
これは Laravel フレームワークでの暗号化に使用されます。
```
$ php artisan key:generate
```
* データベースのテーブル作成
```
$ php artisan migrate
```
* シンボリックリンク作成
```
$ php artisan storage:link
```

# 設定

## Odds の設定

config/odds.php

* initial_points ... ユーザの初期ポイント
* dummy_points ... 単勝にダミーユーザが賭けるポイント（1パターンあたりの基準。合計は 出走数×この値）
* dummy_quinella_points ... 馬連にダミーユーザが賭けるポイント（1パターンあたりの基準）
* dummy_exacta_points ... 馬単にダミーユーザが賭けるポイント（1パターンあたりの基準）
* dummy_weight_power ... 過去平均着順からダミー重みを作る指数。`score = (1 / 平均着順) ^ power`。大きいほど人気に偏る（既定 1.5）
* past_game_count ... 「過去のレース」に表示されるレース数
* calc_odds_on_request ... ユーザのリクエストをトリガとしてオッズ更新を行うなら true
* interval_calc_odds ... オッズ更新間隔 ※calc_odds_on_request=true の時のみ有効
* confirm_robot ... Turnstile 未設定時に、初めてのユーザへ「私はロボットではありません」チェックを表示するなら true

※ダミーユーザは賭け数が少ない場合にもオッズを機能させるための処置です。レースの作成・編集時に、同一主催者の過去成績（確定済み・成績除外でないもの、候補名一致）から平均着順を求め、`(1 / 平均着順) ^ dummy_weight_power` に比例する重みでダミーポイントを配分します。成績のない候補は、成績がある候補の平均スコアを使います。成績が誰にもない場合は従来どおり均一です。馬連・馬単は単勝重みから Harville 風に組み合わせへ展開します。重みは作成・編集時に候補へ保存され、以降のオッズ再計算ではその固定値を使います。

## Cloudflare Turnstile

初回ユーザ作成・ユーザ登録・ユーザ情報更新時のボット対策に [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) を使います。

`TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` が設定されている場合、Cookie 未保持の初回訪問ではユーザを即作成せず、Turnstile 通過後に作成します。ユーザ登録・情報更新でも同じキーを使います。

本番運用では次が必要です。

1. [Cloudflare](https://dash.cloudflare.com/) にアカウント登録する
2. Turnstile でサイト（ウィジェット）を作成し、サイトキーとシークレットキーを発行する
3. 発行したキーを `.env` に書く

```
TURNSTILE_SITE_KEY=<サイトキー>
TURNSTILE_SECRET_KEY=<シークレットキー>
```

両方空のときは検証をスキップします（ローカル開発用）。本番では必ず設定してください。未設定のままだと初回ユーザ作成ゲートを含むボット対策が無効になります。ローカル確認用の常時成功テストキーは Cloudflare ドキュメントを参照してください。

`command:send-auth-mail` 実行時、ニックネームが長すぎる、または `http:` / `https:` を含むユーザは確認メールを送らず `authorized = -1`（BAN）にします。既に認証待ち／認証済みでも同条件なら BAN します。

## オッズの再計算

calc_odds_on_request が true の場合、いずれかのユーザのリクエスト処理のついでにオッズの更新を行います。この場合デメリットとして、時折ユーザのリクエストのレスポンスが遅くなる可能性があります。

calc_odds_on_request は false にして、オッズ更新は cron で行うのをおすすめします。cron で更新させる場合は、
```
$ php artisan command:update-odds
```
を定期実行してください（例: 1分毎）。ベットが入った開催中レース（`exclusion_update = 0`）だけが再計算対象です。

## 確認メール送信

確認メール送信は、バッチでまとめて送信します。以下のコマンドを cron で１分毎程度で実行してください。オッズ更新の cron とは分けて設定してください。
```
$ php artisan command:send-auth-mail
```

## 文言

表示される文言はすべて、lang/ja/odds.php に書いてあります。必要に応じて適宜変更してください。

info_about には作者の GitHub へのリンクが書いてありますが、これも特に必須のものではありませんので、適宜変更してしまって構いません。

# 公開

Web サーバ設定で public フォルダだけを公開してください。

## 本番の負荷対策

共用レンタルサーバー向けの運用上の推奨です。

* Cloudflare を DNS プロキシ（オレンジ雲）で前段に置き、Cache Rules で `/css/` `/js/` `/img/` だけを長めにキャッシュする。HTML（`/` や `/game/*`）はキャッシュしない
* SSL/TLS モードは Full (strict) を推奨（Flexible は使わない）
* PHP OPcache が Web 向けに有効であること（`opcache.enable=1`）。CLI 向け（`opcache.enable_cli`）は無効のままでよい
* 開催告知はトップより `/game/{id}` の直リンクを使う
* イベントで馬連・馬単が不要なら、レース作成時に無効化する（オッズ組み合わせ数が減り再計算が軽くなる）

# 管理

## admin ユーザ設定

現在は Web 上から admin ユーザを設定する方法はありません。MySQL コマンドを直接叩くか phpMyAdmin 等を利用して、users テーブルの admin カラムを 1 にしてください。

クライアント側のウェブブラウザに Cookie として保存されている iden_token と、users テーブルの personal_id カラムを照合し、admin にしたいユーザを判別してください。(公開していなければ、自分しかアクセスしていないので判別する必要はないでしょう。)

admin が 1 になった状態でトップページにアクセスすると、各種管理ボタンが出現します。

## レースの管理

「追加」... 新規にレースを追加します。ボタンを押すと、追加するレースの詳細を入力する画面遷移します。

「編集」... 既存のレース内容を変更します。ボタンを押すと、追加時と同じレースの詳細を入力する画面遷移します。

「締切」... ボタンを押すと、ユーザがこれ以上レースにベットできなくなります。

「再開」... 「締切」によって停止されているユーザのベットを再度受け付けるようにします。

「結果」... レースの結果入力画面に遷移します。

## レースの詳細

「レース名」... 任意のレース名を入力します。空欄にはしないでください。

「予想期限」... ベットを締め切る予定の日時を指定します。※実際の締め切りは上記の「締切」ボタンでのみ行われます。この欄はユーザに見せるためだけのものになります。

「コメント」... 必要な場合にレースの説明を書きます。必要なければ空欄で構いません。

「出走者」... 出走者ごとに名前と枠色を入力します。「行を追加」で行を増やし、「色を自動で割り振る」で枠色を順番に割り当てられます。「一括入力」では出走者名を改行区切りで貼り付けて適用でき、適用すると既存の行は置き換えられます（枠色も自動割当されます）。上から順に自動的に番号が振られます。後から順番を変更した場合、番号の割り振りは変わりますが、ユーザが賭けた対象は同じ名前の相手に維持されます。リストから誰か削除して、ユーザがその対象に賭けていた場合は、そのポイントはユーザに返還されます。

「単勝」... 単勝による賭けを有効にします。※常に有効になります。

「馬連」... 馬連による賭けを有効にします。

「馬単」... 馬単による賭けを有効にします。

# Docker 開発環境について

- 開始
$ cd docker
$ ./up.sh

- composer install & migration
$ ./setup.sh

- Dockerfile を変更したあと反映させる場合
$ ./build-up.sh

- アプリ: http://localhost:8000
- phpMyAdmin: http://localhost:8080
  - サーバは `mysql` 固定
  - ログイン例: `odds_user` / `odds_password`、または `root` / `rootpassword`

- cron（`odds_cron` コンテナ）
  - ホストの crontab は使わず、コンテナ内の cron が artisan を直接実行します
  - `command:send-auth-mail` … 1 分毎
  - `command:update-odds` … 5 分毎
  - ログ確認: `docker compose logs -f cron`
  - Dockerfile / crontab を変えたあとは `./build-up.sh` が必要です

