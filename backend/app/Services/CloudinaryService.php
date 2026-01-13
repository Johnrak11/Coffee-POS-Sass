<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    /**
     * Upload image from local file
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string Cloudinary secure URL
     */
    public function uploadImage(UploadedFile $file, string $folder = 'products'): string
    {
        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => "coffee-pos/{$folder}",
            'transformation' => [
                'width' => 800,
                'height' => 800,
                'crop' => 'limit',
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]
        ]);

        return $result->getSecurePath();
    }

    /**
     * Delete image by public ID
     *
     * @param string $publicId
     * @return array
     */
    public function deleteImage(string $publicId): array
    {
        return Cloudinary::destroy($publicId);
    }

    /**
     * Get optimized URL from existing Cloudinary URL
     *
     * @param string $cloudinaryUrl
     * @param int $width
     * @param int $height
     * @return string|null
     */
    public function getOptimizedUrl(string $cloudinaryUrl, int $width = 800, int $height = 800): ?string
    {
        $publicId = $this->extractPublicId($cloudinaryUrl);

        if (!$publicId) {
            return $cloudinaryUrl;
        }

        return cloudinary()->getUrl($publicId, [
            'width' => $width,
            'height' => $height,
            'crop' => 'limit',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ]);
    }

    /**
     * Extract public_id from Cloudinary URL
     *
     * @param string $url
     * @return string|null
     */
    private function extractPublicId(string $url): ?string
    {
        // Extract public_id from Cloudinary URL
        // Example: https://res.cloudinary.com/demo/image/upload/v1234567890/coffee-pos/products/abc123.jpg
        preg_match('/\/v\d+\/(.+)\.\w+$/', $url, $matches);
        return $matches[1] ?? null;
    }
}
