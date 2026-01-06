<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Coaster;
use App\Service\Cloudinary\CloudinaryApiGateway;
use App\Service\Gemini\GeminiImageService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class CoasterImageTransformer
{
    public function __construct(
        private GeminiImageService $geminiImageService,
        private CloudinaryApiGateway $cloudinaryApiGateway,
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function transform(Coaster $coaster): string
    {
        $rcdbImageUrl = $coaster->getRcdbImageUrl();
        if (!$rcdbImageUrl) {
            throw new RuntimeException('Coaster has no rcdbImageUrl.');
        }

        $prompt = <<<TEXT
    TASK: Artistic Watercolor (Wasserfarben) Transformation.
    
    AESTHETIC (Hand-Painted Aquarelle):
    1. ART STYLE: A loose, expressive Watercolor painting (Aquarell). Use visible wet-on-wet paint effects, pigment blooms, and artistic splashes.
    2. BRUSHWORK: Soft, flowing edges. It must look like a hand-painted piece of art on paper, strictly avoiding photographic precision.
    3. COLORS: Use a desaturated "Hand-Tinted" vintage palette. Incorporate the original colors from the photo (like the ride's paint), but render them as muted, faded watercolor washes.
    
    PAPER & BACKGROUND:
    1. CANVAS: The entire background is heavy, textured, cold-pressed watercolor paper in a warm "Creamy Antique White" or "Aged Beige."
    2. NO WHITE SPACE: The background wash must extend to all edges of the 3:4 frame. Strictly NO pure digital white (#FFFFFF).
    3. NO BORDERS: No frames or white margins. The paint flows to the edges of the creamy paper.
    
    STRICT CONSTRAINTS:
    1. SUBJECT: Transform the rollercoaster into this watercolor style. Preserve the basic structure but simplify the mechanical details into artistic brushstrokes.
    2. NO REALISM: This is a painting. Do not use photographic textures or sharp digital filters.
    3. NO TEXT: Zero letters or numbers.
    TEXT;

        // 1. Generate image via Gemini
        $cartoonImage = $this->geminiImageService->generateImage($rcdbImageUrl, $prompt);

        // 2. Save to temporary file
        $tempDir = $this->projectDir . '/var/temp_images';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $tempPath = sprintf('%s/coaster_%d_%s.png', $tempDir, $coaster->getId(), uniqid());
        file_put_contents($tempPath, $cartoonImage);

        try {
            // 3. Upload to Cloudinary
            $uploadResult = $this->cloudinaryApiGateway->uploadImage($tempPath, [
                'folder' => 'coasters/cartoonized',
                'public_id' => (string) $coaster->getId(),
                'overwrite' => true,
            ]);

            $cloudinaryUrl = $uploadResult['secure_url'];

            // 4. Update Coaster entity
            $coaster->setCdnImageUrl($cloudinaryUrl);
            $this->entityManager->persist($coaster);
            $this->entityManager->flush();

            return $cloudinaryUrl;
        } finally {
            // 5. Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
