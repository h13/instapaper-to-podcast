<?php

declare(strict_types=1);

namespace InstapaperToPodcast;

/**
 * 設定の型定義
 *
 * @psalm-suppress UnusedClass
 * @psalm-type InstapaperConfig = array{
 *     consumer_key: string,
 *     consumer_secret: string,
 *     access_token: string,
 *     access_token_secret: string
 * }
 *
 * @psalm-type GcpConfig = array{
 *     project_id: string,
 *     credentials_path?: ?string
 * }
 *
 * @psalm-type StorageConfig = array{
 *     bucket_name: string
 * }
 *
 * @psalm-type TtsConfig = array{
 *     languageCode?: string,
 *     name?: string,
 *     ssmlGender?: int,
 *     speakingRate?: float,
 *     pitch?: float
 * }
 *
 * @psalm-type PodcastConfig = array{
 *     title?: string,
 *     description?: string,
 *     author?: string,
 *     email?: string,
 *     category?: string,
 *     language?: string,
 *     copyright?: string,
 *     image?: ?string,
 *     feedUrl?: string
 * }
 *
 * @psalm-type AppConfig = array{
 *     instapaper: InstapaperConfig,
 *     gcp: GcpConfig,
 *     storage: StorageConfig,
 *     tts?: TtsConfig,
 *     podcast?: PodcastConfig
 * }
 *
 * @psalm-type Bookmark = array{
 *     bookmark_id: int,
 *     title: string,
 *     url: string,
 *     type?: string
 * }
 *
 * @psalm-type Episode = array{
 *     bookmarkId: int,
 *     title: string,
 *     description: string,
 *     articleUrl: string,
 *     audioUrl: string,
 *     size: int,
 *     duration: string,
 *     created: string,
 *     storagePath: string,
 *     guid: string
 * }
 */
final class ConfigTypes
{
    // このクラスは型定義のみを含むため、インスタンス化を防ぐ
    private function __construct()
    {
    }
}
