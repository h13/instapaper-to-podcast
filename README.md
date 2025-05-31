# Instapaper to Podcast

This system automatically summarizes articles saved in Instapaper, converts them to audio, and distributes them as podcasts. It runs on Google Cloud Functions and uses Cloud Storage for cost-effective operation.

## 🚀 Main Features

- 📚 **Instapaper Integration**: Automatically retrieves saved articles via OAuth authentication
- 🤖 **AI Summary**: Summarize long articles using Google Vertex AI
- 🎙️ **Audio Generation**: Generate natural Japanese audio using Google Text-to-Speech API
- ☁️ **Cloud Storage**: Efficiently manage files using Cloud Storage
- 📡 **Podcast Distribution**: Supports various podcast apps via standard RSS feeds
- ⏰ **Scheduled Execution**: Automatically execute daily with Cloud Scheduler
- 🔄 **CI/CD**: Automated testing and deployment with GitHub Actions
- 💰 **Cost Optimization**: Automatically migrate old files to low-cost storage with lifecycle management

## 📋 Requirements

- PHP 8.3 or higher
- Google Cloud Project and service account
- Instapaper API credentials (OAuth)
- GitHub account (for CI/CD)

## 🛠️ Setup

### 1. Clone the repository

```bash
git clone https://github.com/your-username/instapaper-to-podcast.git
cd instapaper-to-podcast
```

### 2. Install dependencies
```bash
composer install
```

### 3. Initial setup for Google Cloud

#### Enable the necessary APIs

```bash
# Set the project ID
export PROJECT_ID="your-project-id"
gcloud config set project $PROJECT_ID

# Enable the necessary APIs
gcloud services enable \
cloudfunctions.googleapis.com \
cloudscheduler.googleapis.com \
texttospeech.googleapis.com \
aiplatform.googleapis.com \
storage.googleapis.com \
cloudbuild.googleapis.com
```

#### Create a Cloud Storage bucket

```bash
# Set the bucket name (globally unique)
export BUCKET_NAME="${PROJECT_ID}-instapaper-podcasts"

# Create the bucket (Tokyo region)
gsutil mb -p $PROJECT_ID -c STANDARD -l ASIA-NORTHEAST1 gs://$BUCKET_NAME/

# Set to public for podcast distribution
gsutil iam ch allUsers:objectViewer gs://$BUCKET_NAME

# Set lifecycle rules
cat > lifecycle.json << EOF
{
“lifecycle”: {
“rule”: [
{
“action”: { “type”: “SetStorageClass”, ‘storageClass’: “NEARLINE”},
“condition”: {“age”: 30, “matchesPrefix”: [“podcasts/”]}
},
{
“action”: {“type”: “SetStorageClass”, “storageClass”: “COLDLINE”},
“condition”: {“age”: 90, ‘matchesPrefix’: [“podcasts/”]}
},
{
“action”: {“type”: “Delete”},
“condition”: {“age”: 365, ‘matchesPrefix’: [“podcasts/”]}
}
]
}
}
EOF

gsutil lifecycle set lifecycle.json gs://$BUCKET_NAME
```

### 4. Obtain Instapaper API authentication information

1. Register your application at [Instapaper API](https://www.instapaper.com/main/request_oauth_consumer_token)
2. Obtain your Consumer Key and Consumer Secret
3. Execute the OAuth authentication flow to obtain your Access Token and Access Token Secret

### 5. Set environment variables

Create a `.env.yaml` file:

```yaml
INSTAPAPER_CONSUMER_KEY: “your_consumer_key”
INSTAPAPER_CONSUMER_SECRET: “your_consumer_secret”
INSTAPAPER_ACCESS_TOKEN: ‘your_access_token’
INSTAPAPER_ACCESS_TOKEN_SECRET: “your_access_token_secret”
GCP_PROJECT_ID: “your-project-id”
STORAGE_BUCKET_NAME: “your-project-id-instapaper-podcasts”
PODCAST_TITLE: “My Instapaper Podcast”
PODCAST_DESCRIPTION: “Listen to saved Instapaper articles as audio”
PODCAST_AUTHOR: “Your Name”
PODCAST_EMAIL: “your-email@example.com”
PODCAST_CATEGORY: “News”
PODCAST_LANGUAGE: ‘ja’
TTS_LANGUAGE_CODE: “ja-JP”
TTS_VOICE_NAME: “ja-JP-Neural2-B”
TTS_SPEAKING_RATE: “1.0”
```

### 6. Deploy to Cloud Functions

```bash
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
--max-instances=10
```

### 7. Set up scheduled execution

```bash
# Create a Cloud Scheduler job (every day at 8 a.m.)
gcloud scheduler jobs create http instapaper-podcast-job \
--location=asia-northeast1 \
--schedule=“0 8 * * *” \
--time-zone=“Asia/Tokyo” \
--uri=“https://asia-northeast1-${PROJECT_ID}.cloudfunctions.net/instapaper-to-podcast” \
--http-method=POST \
--headers=“Content-Type=application/json” \
--message-body=‘{“limit”:5,“folder”:‘unread’}’
```

## 🚀 GitHub Actions settings

### Workload Identity integration

```bash
# Create a service account
gcloud iam service-accounts create github-actions-sa \
--display-name=“GitHub Actions Service Account”

# Grant the necessary permissions
gcloud projects add-iam-policy-binding $PROJECT_ID \
--member=“serviceAccount:github-actions-sa@${PROJECT_ID}.iam.gserviceaccount.com” \
--role=“roles/cloudfunctions.developer”

# Create a Workload Identity pool
gcloud iam workload-identity-pools create github-pool \
--location=“global” \
--display-name=“GitHub Actions Pool”

# Create the provider
gcloud iam workload-identity-pools providers create-oidc github-provider \
--location=‘global’ \
--workload-identity-pool=“github-pool” \
--display-name=“GitHub provider” \
--attribute-mapping=“google.subject=assertion.sub,attribute.actor=assertion.actor,attribute.repository=assertion.repository” \
--issuer-uri=“https://token.actions.githubusercontent.com”
```

### GitHub Secrets

Set the following secrets in the repository:

| Secret Name | Description |
|------------|-------------|
| `INSTAPAPER_CONSUMER_KEY` | Instapaper OAuth Consumer Key |
| `INSTAPAPER_CONSUMER_SECRET` | Instapaper OAuth Consumer Secret |
| `INSTAPAPER_ACCESS_TOKEN` | Instapaper OAuth Access Token |
| `INSTAPAPER_ACCESS_TOKEN_SECRET` | Instapaper OAuth Access Token Secret |
| `GCP_PROJECT_ID` | Google Cloud Project ID |
| `STORAGE_BUCKET_NAME` | Cloud Storage Bucket name |
| `WIF_PROVIDER` | Workload Identity Provider |
| `WIF_SERVICE_ACCOUNT` | Service account email |

## 📱 Podcast subscription

### RSS feed URL

```
https://storage.googleapis.com/your-bucket-name/podcast.xml
```

### Registration with major podcast platforms

1. **Apple Podcasts**: [Podcasts Connect](https://podcastsconnect.apple.com)
2. **Spotify**: [Spotify for Podcasters](https://podcasters.spotify.com)
3. **Google Podcasts**: [Google Podcasts Manager](https://podcastsmanager.google.com)

## 💰 Cost estimate

Monthly cost (processing 5 articles per day, 20 MB per episode, 100 plays per month):

| Service | Usage | Monthly Cost |
|---------|-------|-----------|
| Cloud Functions | 150 executions × 1 minute | Approximately $0.50 |
| Text-to-Speech API | 400,000 characters | Approximately $6.40 |
| Vertex AI | 150,000 tokens | Approximately $0.30 |
| Cloud Storage | 3GB (Standard → Nearline → Coldline) | Approximately $0.10 |
| Network Transfer | 20GB | Approximately $2.40 |
| **Total** | | **Approximately $10/month** |

## 🧪 Development and Testing

```bash
# Run unit tests
composer test

# Code quality check
composer phpstan

# Code style check
composer cs-check

# Run all checks
composer check
```

## 📊 Monitoring

### Check logs

```bash
# Display latest logs
gcloud functions logs read instapaper-to-podcast \
--region=asia-northeast1 \
--limit=50

# Real-time logs
gcloud functions logs tail instapaper-to-podcast \
--region=asia-northeast1
```

### Check metrics

Monitor the following in Google Cloud Console:
- Number of executions and success rate
- Latency
- Memory usage
- Cost

## 🔧 Customization

### Adjusting the summary

Change the length of the summary with environment variables:
```yaml
SUMMARY_MAX_LENGTH: “1000” # Default: 800
```

### Audio settings

Available voices:
- `ja-JP-Neural2-A`: Female voice
- `ja-JP-Neural2-B`: Male voice (default)
- `ja-JP-Neural2-C`: Male voice
- `ja-JP-Neural2-D`: Female voice

Speech speed adjustment:
```yaml
TTS_SPEAKING_RATE: “1.2” # 1.2 times faster
```

## 🐛 Troubleshooting

### Common issues

1. **Instapaper API error**
- Check API authentication information
- Check rate limit (1,000 requests per hour)

2. **Voice generation error**
- Check the monthly free quota for the Text-to-Speech API (4 million characters)
- Check the character limit per request (5,000 characters)

3. **Storage access error**
- Check the bucket permissions
- Check the service account permissions

## 📝 License

This project is released under the MIT license. For details, see the [LICENSE](LICENSE) file.

## 🤝 Contributions

Pull requests are welcome!

1. Fork
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m ‘Add amazing feature’`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Create a pull request

## 📮 Support

If you encounter any issues, please report them at [Issues](https://github.com/your-username/instapaper-to-podcast/issues).
