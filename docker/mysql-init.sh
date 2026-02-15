#!/bin/bash
set -e

# MySQL公式イメージでは、MYSQL_DATABASEとMYSQL_USERは自動的に作成される
# このスクリプトは追加の設定が必要な場合に使用

# Wait for MySQL to be ready
until mysqladmin ping -h localhost --silent; do
	echo "Waiting for MySQL to be ready..."
	sleep 1
done

# ユーザーに全ホストからのアクセス権限を付与（既に作成されている場合）
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';" 2>/dev/null || true
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

echo "MySQL initialization completed"
