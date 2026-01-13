<?php

declare(strict_types=1);

namespace App\Service\ImageTransformation;

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

    public function transform(Coaster $coaster, string $rcdbImageUrl): string
    {
        $prompt = <<<TEXT
    TASK: Artistic Watercolor (Wasserfarben) Transformation.
    
    AESTHETIC (Hand-Painted Aquarelle):
    1. ART STYLE: A loose, expressive watercolor painting (Aquarell). Visible wet-on-wet effects, pigment blooms, watercolor backruns, and subtle artistic splashes.
    2. BRUSHWORK: Soft, organic, flowing edges with painterly imperfections. Clearly hand-painted, never photographic.
    3. COLORS: Desaturated, hand-tinted vintage palette. Original photo colors are preserved but rendered as muted watercolor washes.
    
    PAPER & CANVAS (VERY IMPORTANT):
    1. FORMAT: Final image is always 3:4 aspect ratio.
    2. PAPER: Heavy, textured, cold-pressed watercolor paper in warm creamy antique white / aged beige (never pure white).
    3. PAINT COVERAGE:
       - Watercolor paint must cover approximately 85–90% of the canvas.
       - The subject should fill the frame confidently.
       - Visible paper is subtle and intentional, never dominant.
    4. ASPECT-RATIO HANDLING:
       - If the source image is landscape, allow slightly more visible paper at the top and bottom.
       - Side edges should remain mostly painted.
       - Transitions from paint to paper must be soft, organic, and irregular.
    5. NO HARD BORDERS:
       - No frames, no clean margins, no straight edges.
       - Paint fades naturally into the paper texture.
    
    COMPOSITION:
    1. SUBJECT:
       - Transform the rollercoaster into watercolor style.
       - Preserve the overall structure and silhouette.
       - Simplify mechanical details into expressive brushstrokes.
    2. BACKGROUND:
       - Background elements are loosely suggested and partially washed out.
       - Atmospheric perspective and gentle fading into paper.
    3. NEGATIVE SPACE:
       - Any remaining paper acts as breathing space, not empty background.
    
    STRICT CONSTRAINTS:
    1. NO REALISM: This must look like a traditional watercolor painting, not a photo.
    2. NO TEXT: No letters, numbers, logos, or watermarks.
    3. NO DIGITAL LOOK: No sharp edges, no photo filters, no graphic design style.
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
            //create random slug
            $slug = uniqid();

            $uploadResult = $this->cloudinaryApiGateway->uploadImage($tempPath, [
                'folder' => 'coasters/images/' . $coaster->getId(),
                'public_id' => $slug,
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
