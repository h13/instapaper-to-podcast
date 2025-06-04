#!/bin/bash
# setup.sh - マイクロサービス初期セットアップスクリプト

set -e

echo "🚀 Instapaper to Podcast マイクロサービスのセットアップを開始します..."

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

# 必要なコマンドの確認
check_command() {
    if ! command -v $1 &> /dev/null; then
        echo -e "${RED}❌ $1 がインストールされていません${NC}"
        return 1
    else
        echo -e "${GREEN}✓ $1 が見つかりました${NC}"
        return 0
    fi
}

echo -e "\n${BLUE}📋 必要なコマンドを確認しています...${NC}"
MISSING_COMMANDS=0
check_command git || MISSING_COMMANDS=1
check_command php || MISSING_COMMANDS=1
check_command composer || MISSING_COMMANDS=1
check_command docker || MISSING_COMMANDS=1
check_command docker || MISSING_COMMANDS=1

# オプショナルなコマンド
echo -e "\n${BLUE}📋 オプショナルなコマンドを確認しています...${NC}"
check_command gcloud || echo -e "${YELLOW}⚠️  gcloud は Google Cloud へのデプロイに必要です${NC}"
check_command kubectl || echo -e "${YELLOW}⚠️  kubectl は Kubernetes へのデプロイに必要です${NC}"

if [ $MISSING_COMMANDS -eq 1 ]; then
    echo -e "\n${RED}必要なコマンドがインストールされていません。インストール後に再実行してください。${NC}"
    exit 1
fi

# PHPバージョンの確認
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
if [[ $(echo "$PHP_VERSION >= 8.3" | bc) -ne 1 ]]; then
    echo -e "${RED}❌ PHP 8.3以上が必要です（現在: $PHP_VERSION）${NC}"
    exit 1
else
    echo -e "${GREEN}✓ PHP $PHP_VERSION${NC}"
fi

# Docker Compose設定ファイルの確認
echo -e "\n${BLUE}📚 Docker Compose設定を確認しています...${NC}"

if [ -f "$PROJECT_ROOT/compose.override.yml" ]; then
    echo -e "${GREEN}✓ compose.override.yml が見つかりました${NC}"
else
    if [ -f "$PROJECT_ROOT/compose.override.yml.example" ]; then
        cp "$PROJECT_ROOT/compose.override.yml.example" "$PROJECT_ROOT/compose.override.yml"
        echo -e "${YELLOW}📝 compose.override.yml を作成しました。必要な環境変数を設定してください。${NC}"
    else
        echo -e "${YELLOW}⚠️  compose.override.yml が見つかりません${NC}"
        echo -e "${YELLOW}    各サービスの環境変数を設定するために作成することを推奨します${NC}"
    fi
fi

# Google Cloud セットアップ（オプション）
echo -e "\n${BLUE}☁️  Google Cloud をセットアップしますか？ (y/N)${NC}"
read -p "" SETUP_GCP

if [[ "$SETUP_GCP" =~ ^[Yy]$ ]]; then
    # プロジェクトIDの入力
    read -p "Google Cloud Project ID: " PROJECT_ID
    export PROJECT_ID
    
    # バケット名の生成
    STORAGE_BUCKET="${PROJECT_ID}-instapaper-storage"
    PODCAST_BUCKET="${PROJECT_ID}-instapaper-podcasts"
    
    echo -e "\nStorage Bucket: $STORAGE_BUCKET"
    echo -e "Podcast Bucket: $PODCAST_BUCKET"
    
    # Google Cloud 設定
    echo -e "\n${BLUE}☁️  Google Cloud を設定しています...${NC}"
    gcloud config set project $PROJECT_ID
    
    # APIの有効化
    echo -e "\n${BLUE}🔌 必要なAPIを有効化しています...${NC}"
    gcloud services enable \
        storage.googleapis.com \
        texttospeech.googleapis.com \
        cloudbuild.googleapis.com \
        run.googleapis.com \
        artifactregistry.googleapis.com
    
    # バケットの作成
    echo -e "\n${BLUE}📦 Cloud Storage バケットを作成しています...${NC}"
    
    # ストレージバケット（プライベート）
    gsutil mb -p $PROJECT_ID -c STANDARD -l ASIA-NORTHEAST1 gs://$STORAGE_BUCKET/ 2>/dev/null || echo "Storage bucket already exists"
    
    # ポッドキャストバケット（パブリック）
    gsutil mb -p $PROJECT_ID -c STANDARD -l ASIA-NORTHEAST1 gs://$PODCAST_BUCKET/ 2>/dev/null || echo "Podcast bucket already exists"
    gsutil iam ch allUsers:objectViewer gs://$PODCAST_BUCKET
    
    # ライフサイクルルールの設定
    cat > /tmp/lifecycle.json << EOF
{
  "lifecycle": {
    "rule": [
      {
        "action": {"type": "SetStorageClass", "storageClass": "NEARLINE"},
        "condition": {"age": 30, "matchesPrefix": ["audio/"]}
      },
      {
        "action": {"type": "SetStorageClass", "storageClass": "COLDLINE"},
        "condition": {"age": 90, "matchesPrefix": ["audio/"]}
      }
    ]
  }
}
EOF
    
    gsutil lifecycle set /tmp/lifecycle.json gs://$STORAGE_BUCKET
    rm -f /tmp/lifecycle.json
    
    echo -e "\n${GREEN}✓ Google Cloud セットアップが完了しました${NC}"
    
    # 環境変数の更新案内
    echo -e "\n${YELLOW}📝 各サービスの .env ファイルに以下の値を設定してください:${NC}"
    echo "GCP_PROJECT_ID=$PROJECT_ID"
    echo "STORAGE_BUCKET_NAME=$STORAGE_BUCKET"
    echo "PODCAST_BUCKET_NAME=$PODCAST_BUCKET"
fi

# Docker イメージのビルド
echo -e "\n${BLUE}🐳 Docker イメージをビルドしますか？ (y/N)${NC}"
read -p "" BUILD_DOCKER

if [[ "$BUILD_DOCKER" =~ ^[Yy]$ ]]; then
    cd "$PROJECT_ROOT"
    docker compose build
    echo -e "${GREEN}✓ Docker イメージのビルドが完了しました${NC}"
fi

echo -e "\n${GREEN}✅ セットアップが完了しました！${NC}"
echo -e "\n${BLUE}📚 次のステップ:${NC}"
echo "1. 各サービスの .env ファイルを編集して必要な情報を入力してください:"
echo "   - Instapaper API認証情報"
echo "   - OpenAI API キー"
echo "   - Google Cloud 認証情報"
echo "2. docker compose up -d でサービスを起動してください"
echo "3. scripts/run-pipeline.sh でパイプラインを実行してください"
echo "4. scripts/deploy.sh でデプロイしてください"