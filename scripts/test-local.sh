#!/bin/bash
# test-local.sh - マイクロサービスローカルテスト実行スクリプト

set -e

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# スクリプトのディレクトリを取得
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
MICROSERVICES_DIR="$PROJECT_ROOT/microservices"

echo -e "${BLUE}🧪 マイクロサービスのテストを実行しています...${NC}"

# テスト対象のサービス
SERVICES=("common" "instapaper-fetcher" "text-summarizer" "text-to-speech" "podcast-publisher")

# 各サービスでテストを実行
FAILED_SERVICES=()

for service in "${SERVICES[@]}"; do
    SERVICE_DIR="$MICROSERVICES_DIR/$service"
    
    if [ -d "$SERVICE_DIR" ]; then
        echo -e "\n${BLUE}📦 $service のテストを実行中...${NC}"
        cd "$SERVICE_DIR"
        
        # composer.jsonが存在するか確認
        if [ ! -f "composer.json" ]; then
            echo -e "${YELLOW}⚠️  $service に composer.json が見つかりません${NC}"
            continue
        fi
        
        # 依存関係の確認
        if [ ! -d "vendor" ]; then
            echo -e "${YELLOW}📚 依存関係をインストールしています...${NC}"
            composer install --no-interaction
        fi
        
        # PHPStan静的解析
        if [ -f "phpstan.neon" ] || [ -f "phpstan.neon.dist" ]; then
            echo -e "\n${BLUE}🔍 PHPStan 静的解析...${NC}"
            if vendor/bin/phpstan analyse --no-progress; then
                echo -e "${GREEN}✓ PHPStan チェック合格${NC}"
            else
                echo -e "${RED}❌ PHPStan エラー${NC}"
                FAILED_SERVICES+=("$service (PHPStan)")
            fi
        else
            echo -e "${YELLOW}⚠️  PHPStan 設定が見つかりません${NC}"
        fi
        
        # Psalm静的解析
        if [ -f "psalm.xml" ] || [ -f "psalm.xml.dist" ]; then
            echo -e "\n${BLUE}🔍 Psalm 静的解析...${NC}"
            if vendor/bin/psalm --no-progress; then
                echo -e "${GREEN}✓ Psalm チェック合格${NC}"
            else
                echo -e "${RED}❌ Psalm エラー${NC}"
                FAILED_SERVICES+=("$service (Psalm)")
            fi
        else
            echo -e "${YELLOW}⚠️  Psalm 設定が見つかりません${NC}"
        fi
        
        # PHPUnitテスト
        if [ -d "tests" ] && [ -f "phpunit.xml" -o -f "phpunit.xml.dist" ]; then
            echo -e "\n${BLUE}🧪 PHPUnit テスト...${NC}"
            if vendor/bin/phpunit --no-coverage; then
                echo -e "${GREEN}✓ ユニットテスト合格${NC}"
            else
                echo -e "${RED}❌ ユニットテストエラー${NC}"
                FAILED_SERVICES+=("$service (PHPUnit)")
            fi
        else
            echo -e "${YELLOW}⚠️  テストが見つかりません${NC}"
        fi
        
        # BEAR.Sunday特有のチェック
        if [ -f "src/Module/AppModule.php" ]; then
            echo -e "\n${BLUE}🐻 BEAR.Sunday アプリケーションチェック...${NC}"
            
            # DIコンテナのコンパイルチェック
            if php bin/app.php get / > /dev/null 2>&1; then
                echo -e "${GREEN}✓ DIコンテナのコンパイル成功${NC}"
            else
                echo -e "${RED}❌ DIコンテナのコンパイルエラー${NC}"
                FAILED_SERVICES+=("$service (DI Container)")
            fi
        fi
        
    else
        echo -e "${RED}❌ $service ディレクトリが見つかりません${NC}"
        FAILED_SERVICES+=("$service (Not Found)")
    fi
done

# 統合テスト
echo -e "\n${BLUE}🔗 統合テストを実行しています...${NC}"

# Docker Composeが利用可能か確認
if command -v docker &> /dev/null && docker compose version &> /dev/null; then
    cd "$PROJECT_ROOT"
    
    # サービスが起動しているか確認
    if docker compose ps | grep -q "Up"; then
        echo -e "${GREEN}✓ サービスが起動しています${NC}"
        
        # 各サービスのヘルスチェック
        echo -e "\n${BLUE}🏥 ヘルスチェック...${NC}"
        
        # Instapaper Fetcher
        if curl -s -o /dev/null -w "%{http_code}" http://localhost:8081/bookmarks | grep -q "200"; then
            echo -e "${GREEN}✓ Instapaper Fetcher: OK${NC}"
        else
            echo -e "${RED}❌ Instapaper Fetcher: NG${NC}"
            FAILED_SERVICES+=("instapaper-fetcher (Health Check)")
        fi
        
        # Text Summarizer
        if curl -s -o /dev/null -w "%{http_code}" http://localhost:8082/summaries | grep -q "200"; then
            echo -e "${GREEN}✓ Text Summarizer: OK${NC}"
        else
            echo -e "${RED}❌ Text Summarizer: NG${NC}"
            FAILED_SERVICES+=("text-summarizer (Health Check)")
        fi
        
        # Text-to-Speech
        if curl -s -o /dev/null -w "%{http_code}" http://localhost:8083/audio | grep -q "200"; then
            echo -e "${GREEN}✓ Text-to-Speech: OK${NC}"
        else
            echo -e "${RED}❌ Text-to-Speech: NG${NC}"
            FAILED_SERVICES+=("text-to-speech (Health Check)")
        fi
        
        # Podcast Publisher
        if curl -s -o /dev/null -w "%{http_code}" http://localhost:8084/feed | grep -q "200"; then
            echo -e "${GREEN}✓ Podcast Publisher: OK${NC}"
        else
            echo -e "${RED}❌ Podcast Publisher: NG${NC}"
            FAILED_SERVICES+=("podcast-publisher (Health Check)")
        fi
        
    else
        echo -e "${YELLOW}⚠️  サービスが起動していません。統合テストをスキップします。${NC}"
        echo -e "${YELLOW}   'docker compose up -d' でサービスを起動してください。${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Docker Compose が見つかりません。統合テストをスキップします。${NC}"
fi

# 結果のサマリー
echo -e "\n${BLUE}📊 テスト結果サマリー${NC}"
echo -e "${BLUE}===========================================${NC}"

if [ ${#FAILED_SERVICES[@]} -eq 0 ]; then
    echo -e "${GREEN}✅ すべてのテストが合格しました！${NC}"
    exit 0
else
    echo -e "${RED}❌ 以下のサービスでエラーが発生しました:${NC}"
    for failed in "${FAILED_SERVICES[@]}"; do
        echo -e "${RED}   - $failed${NC}"
    done
    exit 1
fi