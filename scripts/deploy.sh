#!/bin/bash
# deploy.sh - Cloud Functions デプロイスクリプト

set -e

# .env.yaml が存在するか確認
if [ ! -f ".env.yaml" ]; then
    echo "❌ .env.yaml が見つかりません"
    echo "📝 .env.example を参考に .env.yaml を作成してください"
    exit 1
fi

# プロジェクトIDを取得
PROJECT_ID=$(grep "GCP_PROJECT_ID:" .env.yaml | cut -d '"' -f 2)

echo "🚀 Cloud Functions にデプロイしています..."
echo "Project: $PROJECT_ID"

gcloud functions deploy instapaper-to-podcast \
  --gen2 \
  --runtime=php82 \
  --region=asia-northeast1 \
  --source=. \
  --entry-point=instapaperToPodcast \
  --trigger-http \
  --allow-unauthenticated \
  --env-vars-file=.env.yaml \
  --memory=512MB \
  --timeout=540s \
  --max-instances=10 \
  --project=$PROJECT_ID

echo "✅ デプロイが完了しました！"

# Function URLを取得
FUNCTION_URL=$(gcloud functions describe instapaper-to-podcast \
  --region=asia-northeast1 \
  --format='value(serviceConfig.uri)' \
  --project=$PROJECT_ID)

echo -e "\n📡 Function URL: $FUNCTION_URL"
echo -e "\n🧪 テストコマンド:"
echo "curl -X POST $FUNCTION_URL -H 'Content-Type: application/json' -d '{\"limit\":1}'"
