#!/bin/bash
# deploy.sh - マイクロサービスデプロイスクリプト

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
SERVICES_ROOT="$(dirname "$PROJECT_ROOT")"

echo -e "${BLUE}🚀 Instapaper to Podcast マイクロサービスのデプロイを開始します${NC}"

# デプロイ方法の選択
echo -e "\nデプロイ方法を選択してください:"
echo "1) Docker Compose (ローカル/開発環境)"
echo "2) Kubernetes (本番環境)"
echo "3) Google Cloud Run (サーバーレス)"
read -p "選択 (1-3): " DEPLOY_METHOD

case $DEPLOY_METHOD in
    1)
        echo -e "\n${BLUE}🐳 Docker Compose でデプロイします...${NC}"
        cd "$PROJECT_ROOT"
        
        # .env.jsonファイルの確認
        for service in instapaper-fetcher text-summarizer text-to-speech podcast-publisher; do
            if [ ! -f "$SERVICES_ROOT/$service/.env.json" ]; then
                echo -e "${YELLOW}⚠️  $SERVICES_ROOT/$service/.env.json が見つかりません。.env.json.example からコピーします...${NC}"
                if [ -f "$SERVICES_ROOT/$service/.env.json.example" ]; then
                    cp "$SERVICES_ROOT/$service/.env.json.example" "$SERVICES_ROOT/$service/.env.json"
                    echo -e "${RED}❌ $SERVICES_ROOT/$service/.env.json を編集してください${NC}"
                    exit 1
                fi
            fi
        done
        
        # Docker Compose起動
        docker compose build
        docker compose up -d
        
        echo -e "\n${GREEN}✅ Docker Compose デプロイが完了しました！${NC}"
        echo -e "\nサービスURL:"
        echo "- Instapaper Fetcher: http://localhost:8081"
        echo "- Text Summarizer: http://localhost:8082"
        echo "- Text-to-Speech: http://localhost:8083"
        echo "- Podcast Publisher: http://localhost:8084"
        ;;
        
    2)
        echo -e "\n${BLUE}☸️  Kubernetes にデプロイします...${NC}"
        cd "$MICROSERVICES_DIR"
        
        # 各サービスのDockerイメージをビルド
        echo -e "\n${BLUE}🏗️  Docker イメージをビルドしています...${NC}"
        for service in instapaper-fetcher text-summarizer text-to-speech podcast-publisher; do
            echo -e "\n📦 $service をビルド中..."
            docker build -t "instapaper-podcast/$service:latest" "$service/"
        done
        
        # Kubernetes マニフェストの適用
        echo -e "\n${BLUE}📋 Kubernetes マニフェストを適用しています...${NC}"
        if [ -d "k8s/" ]; then
            kubectl apply -f k8s/
        else
            echo -e "${RED}❌ k8s/ ディレクトリが見つかりません${NC}"
            exit 1
        fi
        
        echo -e "\n${GREEN}✅ Kubernetes デプロイが完了しました！${NC}"
        kubectl get pods
        ;;
        
    3)
        echo -e "\n${BLUE}☁️  Google Cloud Run にデプロイします...${NC}"
        
        # プロジェクトIDの取得
        read -p "Google Cloud Project ID: " PROJECT_ID
        REGION="asia-northeast1"
        
        # 各サービスをCloud Runにデプロイ
        for service in instapaper-fetcher text-summarizer text-to-speech podcast-publisher; do
            echo -e "\n${BLUE}📦 $service をデプロイ中...${NC}"
            
            SERVICE_NAME="instapaper-$service"
            IMAGE_NAME="gcr.io/$PROJECT_ID/$SERVICE_NAME"
            
            # Cloud Buildでイメージをビルド（GitHubから直接）
            gcloud builds submit --tag "$IMAGE_NAME" --project "$PROJECT_ID" \
                "https://github.com/h13/$service.git"
            
            # Cloud Runにデプロイ
            gcloud run deploy "$SERVICE_NAME" \
                --image "$IMAGE_NAME" \
                --platform managed \
                --region "$REGION" \
                --allow-unauthenticated \
                --memory 512Mi \
                --timeout 300 \
                --project "$PROJECT_ID"
        done
        
        echo -e "\n${GREEN}✅ Cloud Run デプロイが完了しました！${NC}"
        echo -e "\n${BLUE}📡 サービスURL:${NC}"
        for service in instapaper-fetcher text-summarizer text-to-speech podcast-publisher; do
            SERVICE_NAME="instapaper-$service"
            URL=$(gcloud run services describe "$SERVICE_NAME" \
                --platform managed \
                --region "$REGION" \
                --format 'value(status.url)' \
                --project "$PROJECT_ID")
            echo "- $service: $URL"
        done
        ;;
        
    *)
        echo -e "${RED}❌ 無効な選択です${NC}"
        exit 1
        ;;
esac

echo -e "\n${BLUE}📚 次のステップ:${NC}"
echo "1. 各サービスのエンドポイントをテストしてください"
echo "2. ログを確認してください"
echo "3. パイプライン全体を実行してください"