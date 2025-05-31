#!/bin/bash
# test-local.sh - ローカルテスト実行スクリプト

echo "🧪 テストを実行しています..."

# コードスタイルチェック
echo -e "\n📝 コードスタイルチェック..."
vendor/bin/php-cs-fixer fix --dry-run --diff

# 静的解析
echo -e "\n🔍 PHPStan 静的解析..."
vendor/bin/phpstan analyse

# ユニットテスト
echo -e "\n🧪 PHPUnit テスト..."
vendor/bin/phpunit

echo -e "\n✅ すべてのテストが完了しました！"
