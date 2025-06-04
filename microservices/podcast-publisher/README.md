# Podcast Publisher Service

BEAR.Sunday microservice that generates and publishes podcast RSS feeds from audio files.

## Features

- Generates RSS podcast feed from audio files in Cloud Storage
- iTunes-compatible podcast metadata
- Public feed hosting on Google Cloud Storage
- RESTful API endpoints
- CLI command for feed generation

## API Endpoints

- `GET /feed` - Get current feed information
- `POST /feed/generate` - Generate new podcast feed

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
   php -S localhost:8084 -t public/
   ```

## CLI Usage

Generate podcast feed:
```bash
php bin/publish.php
```

Show feed info:
```bash
php bin/publish.php --info
```

## Docker

Build:
```bash
docker build -t podcast-publisher .
```

Run:
```bash
docker run -p 8084:8084 --env-file .env podcast-publisher
```

## Environment Variables

### Storage
- `STORAGE_BUCKET_NAME` - Main GCS bucket for audio files
- `PODCAST_BUCKET_NAME` - Public bucket for podcast feed
- `GCP_PROJECT_ID` - Google Cloud project ID
- `GOOGLE_APPLICATION_CREDENTIALS` - Path to GCP credentials

### Podcast Metadata
- `PODCAST_TITLE` - Podcast title
- `PODCAST_DESCRIPTION` - Podcast description
- `PODCAST_AUTHOR` - Author name
- `PODCAST_EMAIL` - Contact email
- `PODCAST_CATEGORY` - iTunes category
- `PODCAST_LANGUAGE` - Language code (e.g., en)
- `PODCAST_IMAGE_URL` - Cover art URL
- `PODCAST_WEBSITE_URL` - Website URL