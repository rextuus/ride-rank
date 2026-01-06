<?php

namespace App\Service\Gemini;

use Gemini;
use Gemini\Client;
use Gemini\Data\Blob;
use Gemini\Data\GenerationConfig;
use Gemini\Data\ImageConfig;
use Gemini\Enums\MimeType;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class GeminiImageService
{
    private Client $client;

    public function __construct(
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private readonly string $apiKey,
    ) {
        $this->client = Gemini::client($this->apiKey);
    }

    public function generateImage(string $imageUrl, string $prompt): string
    {
        $imageConfig = new ImageConfig(aspectRatio: '3:4');
        $generationConfig = new GenerationConfig(imageConfig: $imageConfig);

        $response = $this->client
            ->generativeModel(model: 'gemini-2.5-flash-image')
            ->withGenerationConfig($generationConfig)
            ->generateContent([
                $prompt,
                new Blob(
                    mimeType: MimeType::IMAGE_JPEG,
                    data: base64_encode(
                        file_get_contents($imageUrl)
                    )
                )
            ]);

        foreach ($response->parts() as $part) {
            if ($part->inlineData !== null) {
                return base64_decode($part->inlineData->data);
            }
        }

        throw new RuntimeException('Gemini did not return an image in the response. Check safety ratings or response content.');
    }
}
