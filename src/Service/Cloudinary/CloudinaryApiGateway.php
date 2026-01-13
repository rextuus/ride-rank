<?php

declare(strict_types=1);

namespace App\Service\Cloudinary;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CloudinaryApiGateway
{
    private Cloudinary $cloudinary;

    public function __construct(
        #[Autowire('%env(CLOUDINARY_NAME)%')]
        string $cloudname,
        #[Autowire('%env(CLOUDINARY_API_KEY)%')]
        string $apiKey,
        #[Autowire('%env(CLOUDINARY_API_SECRET)%')]
        string $apiSecret
    ) {
        $config = new Configuration();
        $config->cloud->cloudName = $cloudname;
        $config->cloud->apiKey = $apiKey;
        $config->cloud->apiSecret = $apiSecret;
        $config->url->secure = true;
        $this->cloudinary = new Cloudinary($config);
    }

    public function uploadImage(string $imagePath, array $options = []): ApiResponse
    {
        return $this->cloudinary->uploadApi()->upload($imagePath, $options);
    }
}
