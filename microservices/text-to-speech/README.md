# Text-to-Speech Service

BEAR.Sunday microservice that converts summarized texts to audio files using Google Cloud Text-to-Speech.

## Features

- Processes summarized texts from Cloud Storage
- Generates MP3 audio files using Google TTS
- Stores audio files back to Cloud Storage
- RESTful API endpoints
- CLI command for batch processing

## API Endpoints

- `GET /audio` - List existing audio files
- `POST /audio/generate` - Generate audio from summaries

## Setup

1. Copy `.env.example` to `.env` and configure:
   ```bash
   cp .env.example .env
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Run the service:
   ```bash
   php -S localhost:8083 -t public/
   ```

## CLI Usage

Generate audio:
```bash
php bin/generate-audio.php --limit=10
```

List audio files:
```bash
php bin/generate-audio.php --list --limit=20
```

## Docker

Build:
```bash
docker build -t text-to-speech .
```

Run:
```bash
docker run -p 8083:8083 --env-file .env text-to-speech
```

## Environment Variables

- `TTS_LANGUAGE_CODE` - Language code (default: en-US)
- `TTS_VOICE_NAME` - Voice name (default: en-US-Wavenet-D)
- `TTS_SPEAKING_RATE` - Speaking rate (default: 1.0)
- `TTS_PITCH` - Voice pitch (default: 0.0)
- `STORAGE_BUCKET_NAME` - GCS bucket name
- `GCP_PROJECT_ID` - Google Cloud project ID
- `GOOGLE_APPLICATION_CREDENTIALS` - Path to GCP credentials