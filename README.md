# Odds について

Odds はあくまでも「お遊び」としてレースの着順などを予想し、賭けを行うシステムです。
課金などには一切対応しておらず、得られるポイントもこのシステム上で賭けに使う以外の使い道はありません。
配当ポイントも厳密に賭けポイントを分配していないため、実際の賭けの計算ツールとしても使用できません。
あくまでも「お遊び」として利用することを目的としています。

# 必要環境

* PHP 8.1 (Laravel 9)
* MySQL
* ※memcached/redis は使用していません。

# インストール

* Web サーバ、PHP 8.1、MySQL を準備
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
* APP_KEY を作成
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
* dummy_points ... 単勝にダミーユーザが賭けるポイント
* dummy_quinella_points ... 馬連にダミーユーザが賭けるポイント
* dummy_exacta_points ... 馬単にダミーユーザが賭けるポイント
* past_game_count ... 「過去のレース」に表示されるレース数
* calc_odds_on_request ... ユーザのリクエストをトリガとしてオッズ更新を行うなら true
* interval_calc_odds ... オッズ更新間隔 (単位:分) ※calc_odds_on_request=true の時のみ有効
* confirm_robot ... 初めてのユーザに「私はロボットではありません」チェックを表示するなら true

※ダミーユーザは賭け数が少ない場合にもオッズを機能させるための処置で、予め全ての枠に指定のポイントが均一に賭けられているものとしてオッズの計算を行います。

## オッズの再計算

calc_odds_on_request が true の場合、いずれかのユーザのリクエスト処理のついでにオッズの更新を行います。この場合デメリットとして、時折ユーザのリクエストのレスポンスが遅くなる可能性があります。

calc_odds_on_request は false にして、オッズ更新は cron で行うのをおすすめします。cron で更新させる場合は、
```
$ php artisan command:update-odds
```
を定期実行してください。

## 文言

表示される文言はすべて、lang/ja/odds.php に書いてあります。必要に応じて適宜変更してください。
info_about は作者の GitHub へのリンクが書いてありますが、これも特に必須のものではありませんので、適宜変更してしまって構いません。

# 公開

Web サーバ設定で public フォルダだけを公開してください。
