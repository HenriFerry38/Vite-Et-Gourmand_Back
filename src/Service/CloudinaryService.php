<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? null;
        $apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? null;
        $apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? null;

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new \RuntimeException('Cloudinary configuration is missing.');
        }

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * @return array{public_id: string|null, secure_url: string|null}
     */
    public function uploadPlatPhoto(UploadedFile $file, int $platId): array
    {
        $uploadPath = $this->prepareImageForUpload($file);

        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $uploadPath,
                [
                    'folder' => 'vite-et-gourmand/plats',
                    'public_id' => sprintf('plat_%d_%s', $platId, bin2hex(random_bytes(6))),
                    'resource_type' => 'image',
                    'transformation' => [
                        'width' => 800,
                        'height' => 800,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'fetch_format' => 'auto',
                    ],
                ]
            );

            return [
                'public_id' => $result['public_id'] ?? null,
                'secure_url' => $result['secure_url'] ?? null,
            ];
        } finally {
            if ($uploadPath !== $file->getRealPath() && is_file($uploadPath)) {
                @unlink($uploadPath);
            }
        }
    }

    private function prepareImageForUpload(UploadedFile $file): string
    {
        $originalPath = $file->getRealPath();
        if (!$originalPath) {
            throw new \RuntimeException('Unable to read uploaded file.');
        }

        // Si fichier déjà assez léger, on l’envoie tel quel
        if (($file->getSize() ?? 0) <= 9 * 1024 * 1024) {
            return $originalPath;
        }

        $mime = (string) $file->getMimeType();

        switch ($mime) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($originalPath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($originalPath);
                break;
            case 'image/webp':
                $source = function_exists('imagecreatefromwebp')
                    ? @imagecreatefromwebp($originalPath)
                    : false;
                break;
            default:
                throw new \RuntimeException('Unsupported image format for compression.');
        }

        if (!$source) {
            throw new \RuntimeException('Unable to create image resource from uploaded file.');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $maxWidth = 1600;
        $maxHeight = 1600;

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int) floor($width * $ratio);
        $newHeight = (int) floor($height * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Transparence PNG/WebP
        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled(
            $resized,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'plat_img_');
        if (!$tmpFile) {
            imagedestroy($source);
            imagedestroy($resized);
            throw new \RuntimeException('Unable to create temporary file.');
        }

        $tmpJpeg = $tmpFile . '.jpg';

        // On réencode en JPEG qualité 82 pour alléger franchement
        if (!imagejpeg($resized, $tmpJpeg, 82)) {
            imagedestroy($source);
            imagedestroy($resized);
            @unlink($tmpFile);
            throw new \RuntimeException('Unable to write compressed image.');
        }

        imagedestroy($source);
        imagedestroy($resized);
        @unlink($tmpFile);

        return $tmpJpeg;
    }

    public function deleteByUrl(?string $url): void
    {
        if (!$url || !str_contains($url, 'cloudinary.com')) {
            return;
        }

        $publicId = $this->extractPublicIdFromUrl($url);
        if (!$publicId) {
            return;
        }

        $this->cloudinary->uploadApi()->destroy($publicId, [
            'resource_type' => 'image',
            'invalidate' => true,
        ]);
    }

    private function extractPublicIdFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        $parts = explode('/', trim($path, '/'));
        $uploadIndex = array_search('upload', $parts, true);

        if ($uploadIndex === false) {
            return null;
        }

        $publicParts = array_slice($parts, $uploadIndex + 1);

        if (!$publicParts) {
            return null;
        }

        if (preg_match('/^v\d+$/', $publicParts[0])) {
            array_shift($publicParts);
        }

        if (!$publicParts) {
            return null;
        }

        $last = array_pop($publicParts);
        $lastWithoutExt = preg_replace('/\.[^.]+$/', '', $last);
        $publicParts[] = $lastWithoutExt;

        return implode('/', $publicParts);
    }
}