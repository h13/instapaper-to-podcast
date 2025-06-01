<?php

declare(strict_types=1);

namespace Common\Language;

use LanguageDetection\Language;

class LanguageDetector
{
    private Language $detector;
    
    // 言語コードマッピング (ISO 639-1 to Google TTS language codes)
    private const LANGUAGE_MAP = [
        'en' => 'en-US',
        'ja' => 'ja-JP',
        'es' => 'es-ES',
        'fr' => 'fr-FR',
        'de' => 'de-DE',
        'zh' => 'zh-CN',
        'ko' => 'ko-KR',
        'it' => 'it-IT',
        'pt' => 'pt-BR',
        'ru' => 'ru-RU',
    ];
    
    public function __construct()
    {
        $this->detector = new Language();
    }
    
    /**
     * Detect language from text
     * 
     * @return array{code: string, confidence: float, tts_code: string}
     */
    public function detect(string $text): array
    {
        $result = $this->detector->detect($text);
        $bestMatch = $result->bestResults()->close()[0] ?? null;
        
        if (!$bestMatch) {
            return [
                'code' => 'en',
                'confidence' => 0.0,
                'tts_code' => 'en-US'
            ];
        }
        
        $langCode = $bestMatch['lang'];
        $confidence = $bestMatch['score'];
        
        return [
            'code' => $langCode,
            'confidence' => $confidence,
            'tts_code' => self::LANGUAGE_MAP[$langCode] ?? 'en-US'
        ];
    }
    
    /**
     * Get appropriate summarization prompt for the language
     */
    public function getSummarizationPrompt(string $langCode): string
    {
        $prompts = [
            'ja' => 'この記事を200文字程度で要約してください。重要なポイントを箇条書きで含めてください。',
            'en' => 'Please summarize this article in about 200 words. Include key points in bullet format.',
            'es' => 'Por favor, resume este artículo en unas 200 palabras. Incluye los puntos clave en formato de viñetas.',
            'fr' => 'Veuillez résumer cet article en environ 200 mots. Incluez les points clés sous forme de puces.',
            'de' => 'Bitte fassen Sie diesen Artikel in etwa 200 Wörtern zusammen. Fügen Sie die wichtigsten Punkte in Aufzählungsform hinzu.',
        ];
        
        return $prompts[$langCode] ?? $prompts['en'];
    }
    
    /**
     * Get appropriate TTS voice for the language and gender
     */
    public function getTtsVoice(string $ttsCode, string $gender = 'female'): array
    {
        $voices = [
            'en-US' => [
                'female' => 'en-US-Neural2-F',
                'male' => 'en-US-Neural2-D'
            ],
            'ja-JP' => [
                'female' => 'ja-JP-Neural2-B',
                'male' => 'ja-JP-Neural2-C'
            ],
            'es-ES' => [
                'female' => 'es-ES-Neural2-A',
                'male' => 'es-ES-Neural2-B'
            ],
            'fr-FR' => [
                'female' => 'fr-FR-Neural2-A',
                'male' => 'fr-FR-Neural2-B'
            ],
            'de-DE' => [
                'female' => 'de-DE-Neural2-F',
                'male' => 'de-DE-Neural2-B'
            ],
        ];
        
        $voiceConfig = $voices[$ttsCode] ?? $voices['en-US'];
        
        return [
            'languageCode' => $ttsCode,
            'name' => $voiceConfig[$gender] ?? $voiceConfig['female']
        ];
    }
}