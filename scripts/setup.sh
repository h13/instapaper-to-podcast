#!/bin/bash
# setup.sh - 初期セットアップスクリプト

set -e

echo "🚀 Instapaper to Podcast セットアップを開始します..."

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

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

echo "📋 必要なコマンドを確認しています..."
check_command git
check_command php
check_command composer
check_command gcloud

# プロジェクトIDの入力
read -p "Google Cloud Project ID: " PROJECT_ID
export PROJECT_ID

# バケット名の生成
BUCKET_NAME="${PROJECT_ID}-instapaper-podcasts"
echo "Storage Bucket: $BUCKET_NAME"

# Google Cloud 設定
echo -e "\n☁️  Google Cloud を設定しています..."
gcloud config set project $PROJECT_ID

# APIの有効化
echo -e "\n🔌 必要なAPIを有効化しています..."
gcloud services enable \
  cloudfunctions.googleapis.com \
  cloudscheduler.googleapis.com \
  texttospeech.googleapis.com \
  aiplatform.googleapis.com \
  storage.googleapis.com \
  cloudbuild.googleapis.com

# バケットの作成
echo -e "\n📦 Cloud Storage バケットを作成しています..."
gsutil mb -p $PROJECT_ID -c STANDARD -l ASIA-NORTHEAST1 gs://$BUCKET_NAME/ || echo "バケットは既に存在します"

# 依存関係のインストール
echo -e "\n📚 依存関係をインストールしています..."
composer install

echo -e "\n${GREEN}✅ セットアップが完了しました！${NC}"
echo -e "\n次のステップ:"
echo "1. .env.example を .env にコピーして、必要な情報を入力してください"
echo "2. Instapaper API認証情報を取得してください"
echo "3. README.md の手順に従ってデプロイしてください"
