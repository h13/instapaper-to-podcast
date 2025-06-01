#!/bin/bash
# run-pipeline.sh - パイプライン全体を実行するスクリプト

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

echo -e "${BLUE}🚀 Instapaper to Podcast パイプラインを実行します${NC}"

# 実行方法の選択
echo -e "\n実行方法を選択してください:"
echo "1) CLI コマンドで順次実行"
echo "2) REST API 経由で実行"
read -p "選択 (1-2): " RUN_METHOD

# 処理する記事数
read -p "処理する記事数 (デフォルト: 5): " LIMIT
LIMIT=${LIMIT:-5}

case $RUN_METHOD in
    1)
        echo -e "\n${BLUE}🔧 CLI コマンドでパイプラインを実行します${NC}"
        
        # Step 1: Instapaper から記事を取得
        echo -e "\n${BLUE}📚 Step 1: Instapaper から記事を取得中...${NC}"
        cd "$MICROSERVICES_DIR/instapaper-fetcher"
        if php bin/fetch.php --limit=$LIMIT; then
            echo -e "${GREEN}✓ 記事の取得が完了しました${NC}"
        else
            echo -e "${RED}❌ 記事の取得に失敗しました${NC}"
            exit 1
        fi
        
        # Step 2: 記事を要約
        echo -e "\n${BLUE}🤖 Step 2: 記事を要約中...${NC}"
        cd "$MICROSERVICES_DIR/text-summarizer"
        if php bin/summarize.php --limit=$LIMIT; then
            echo -e "${GREEN}✓ 記事の要約が完了しました${NC}"
        else
            echo -e "${RED}❌ 記事の要約に失敗しました${NC}"
            exit 1
        fi
        
        # Step 3: 音声ファイルを生成
        echo -e "\n${BLUE}🎙️ Step 3: 音声ファイルを生成中...${NC}"
        cd "$MICROSERVICES_DIR/text-to-speech"
        if php bin/generate-audio.php --limit=$LIMIT; then
            echo -e "${GREEN}✓ 音声ファイルの生成が完了しました${NC}"
        else
            echo -e "${RED}❌ 音声ファイルの生成に失敗しました${NC}"
            exit 1
        fi
        
        # Step 4: ポッドキャストフィードを生成
        echo -e "\n${BLUE}📡 Step 4: ポッドキャストフィードを生成中...${NC}"
        cd "$MICROSERVICES_DIR/podcast-publisher"
        if php bin/publish.php; then
            echo -e "${GREEN}✓ ポッドキャストフィードの生成が完了しました${NC}"
        else
            echo -e "${RED}❌ ポッドキャストフィードの生成に失敗しました${NC}"
            exit 1
        fi
        ;;
        
    2)
        echo -e "\n${BLUE}🌐 REST API でパイプラインを実行します${NC}"
        
        # サービスが起動しているか確認
        echo -e "\n${BLUE}🏥 サービスの起動を確認中...${NC}"
        SERVICES_UP=true
        
        for port in 8081 8082 8083 8084; do
            if ! curl -s -o /dev/null -w "%{http_code}" http://localhost:$port | grep -q "200\|404"; then
                echo -e "${RED}❌ ポート $port のサービスが応答しません${NC}"
                SERVICES_UP=false
            fi
        done
        
        if [ "$SERVICES_UP" = false ]; then
            echo -e "${YELLOW}⚠️  サービスが起動していません。${NC}"
            echo -e "${YELLOW}   'cd microservices && docker-compose up -d' でサービスを起動してください。${NC}"
            exit 1
        fi
        
        # Step 1: Instapaper から記事を取得
        echo -e "\n${BLUE}📚 Step 1: Instapaper から記事を取得中...${NC}"
        RESPONSE=$(curl -s -X POST http://localhost:8081/bookmarks/fetch \
            -H "Content-Type: application/json" \
            -d "{\"limit\": $LIMIT}")
        
        if echo "$RESPONSE" | grep -q '"success":true'; then
            echo -e "${GREEN}✓ 記事の取得が完了しました${NC}"
            FETCHED=$(echo "$RESPONSE" | grep -o '"fetched":[0-9]*' | cut -d: -f2)
            echo "  取得した記事数: $FETCHED"
        else
            echo -e "${RED}❌ 記事の取得に失敗しました${NC}"
            echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"
            exit 1
        fi
        
        # Step 2: 記事を要約
        echo -e "\n${BLUE}🤖 Step 2: 記事を要約中...${NC}"
        RESPONSE=$(curl -s -X POST http://localhost:8082/summaries/process \
            -H "Content-Type: application/json" \
            -d "{\"limit\": $LIMIT}")
        
        if echo "$RESPONSE" | grep -q '"success":true'; then
            echo -e "${GREEN}✓ 記事の要約が完了しました${NC}"
            PROCESSED=$(echo "$RESPONSE" | grep -o '"processed":[0-9]*' | cut -d: -f2)
            echo "  要約した記事数: $PROCESSED"
        else
            echo -e "${RED}❌ 記事の要約に失敗しました${NC}"
            echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"
            exit 1
        fi
        
        # Step 3: 音声ファイルを生成
        echo -e "\n${BLUE}🎙️ Step 3: 音声ファイルを生成中...${NC}"
        RESPONSE=$(curl -s -X POST http://localhost:8083/audio/generate \
            -H "Content-Type: application/json" \
            -d "{\"limit\": $LIMIT}")
        
        if echo "$RESPONSE" | grep -q '"success":true'; then
            echo -e "${GREEN}✓ 音声ファイルの生成が完了しました${NC}"
            PROCESSED=$(echo "$RESPONSE" | grep -o '"processed":[0-9]*' | cut -d: -f2)
            echo "  生成した音声ファイル数: $PROCESSED"
        else
            echo -e "${RED}❌ 音声ファイルの生成に失敗しました${NC}"
            echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"
            exit 1
        fi
        
        # Step 4: ポッドキャストフィードを生成
        echo -e "\n${BLUE}📡 Step 4: ポッドキャストフィードを生成中...${NC}"
        RESPONSE=$(curl -s -X POST http://localhost:8084/feed/generate \
            -H "Content-Type: application/json")
        
        if echo "$RESPONSE" | grep -q '"success":true'; then
            echo -e "${GREEN}✓ ポッドキャストフィードの生成が完了しました${NC}"
            EPISODES=$(echo "$RESPONSE" | grep -o '"episodes":[0-9]*' | cut -d: -f2)
            URL=$(echo "$RESPONSE" | grep -o '"url":"[^"]*' | cut -d'"' -f4)
            echo "  エピソード数: $EPISODES"
            echo "  フィードURL: $URL"
        else
            echo -e "${RED}❌ ポッドキャストフィードの生成に失敗しました${NC}"
            echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"
            exit 1
        fi
        ;;
        
    *)
        echo -e "${RED}❌ 無効な選択です${NC}"
        exit 1
        ;;
esac

echo -e "\n${GREEN}✅ パイプラインの実行が完了しました！${NC}"
echo -e "\n${BLUE}📊 結果の確認:${NC}"

# 結果の確認
if [ "$RUN_METHOD" = "2" ]; then
    # フィード情報を取得
    FEED_INFO=$(curl -s http://localhost:8084/feed)
    if [ -n "$FEED_INFO" ]; then
        echo -e "\n${BLUE}📡 ポッドキャストフィード情報:${NC}"
        echo "$FEED_INFO" | jq . 2>/dev/null || echo "$FEED_INFO"
    fi
fi

echo -e "\n${BLUE}📚 次のステップ:${NC}"
echo "1. ポッドキャストフィードURLをポッドキャストアプリに登録"
echo "2. Cloud Storage で生成されたファイルを確認"
echo "3. ログを確認して処理の詳細を把握"