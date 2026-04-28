<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class ImageProcessingService
{
    private const MAIN_WIDTH = 1000;
    private const MAIN_HEIGHT = 563;
    private const THUMB_WIDTH = 110;
    private const THUMB_HEIGHT = 56;
    private const QUALITY = 85; // WebP quality (0-100)
    private const SAVE_ERROR_CODE_2X = 2002;
    private const SAVE_ERROR_CODE_3X = 3003;

    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    /**
     * Process uploaded image: resize to 1000x563 and create all variants
     * 
     * @param string $sourcePath Full path to the uploaded image
     * @param string $baseFilename Base filename without extension (e.g., "my-image-12345")
     * @param string $uploadDir Directory where processed images should be saved
     * @return array Array with filenames: ['main' => '...', 'thumb' => '...', '2x' => '...', '3x' => '...']
     * @throws \Exception If image processing fails
     */
    public function processUploadedImage(string $sourcePath, string $baseFilename, string $uploadDir): array
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension is not available');
        }

        if (!function_exists('imagewebp')) {
            throw new \Exception('WebP support is not available in GD extension');
        }

        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Load the source image
        $sourceImage = $this->loadImage($sourcePath);
        if (!$sourceImage) {
            throw new \Exception('Failed to load source image');
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Process main image (1000x563)
        $mainImage = $this->resizeAndCrop($sourceImage, self::MAIN_WIDTH, self::MAIN_HEIGHT);
        $mainFilename = $baseFilename . '.webp';
        $mainPath = $uploadDir . '/' . $mainFilename;
        if (!$this->saveWebP($mainImage, $mainPath)) {
            throw new \Exception('Failed to save main image to ' . $mainPath);
        }

        // Process thumbnail (110x56)
        $thumbImage = $this->resizeAndCrop($sourceImage, self::THUMB_WIDTH, self::THUMB_HEIGHT);
        $thumbFilename = $baseFilename . '_thumb.webp';
        $thumbPath = $uploadDir . '/' . $thumbFilename;
        if (!$this->saveWebP($thumbImage, $thumbPath)) {
            throw new \Exception('Failed to save thumbnail image to ' . $thumbPath);
        }

        // Process 2x version (2000x1126). If high-res creation fails, keep flow alive with main fallback.
        $filename2x = $baseFilename . '@2x.webp';
        $path2x = $uploadDir . '/' . $filename2x;
        $image2x = null;
        try {
            $image2x = $this->resizeAndCrop($sourceImage, self::MAIN_WIDTH * 2, self::MAIN_HEIGHT * 2);
            if (!$this->saveWebP($image2x, $path2x)) {
                throw new \RuntimeException('Failed to save 2x image to ' . $path2x, self::SAVE_ERROR_CODE_2X);
            }
        } catch (\Throwable $exception) {
            if ($this->isExpectedVariantSaveFailure($exception, self::SAVE_ERROR_CODE_2X)) {
                $this->logger?->warning('Falling back to main image for 2x gallery variant.', [
                    'source_path' => $sourcePath,
                    'main_path' => $mainPath,
                    'variant_path' => $path2x,
                    'exception' => $exception,
                ]);
                $this->copyFallbackVariant($mainPath, $path2x);
            } else {
                $this->logger?->error('Unexpected 2x gallery variant processing failure.', [
                    'source_path' => $sourcePath,
                    'main_path' => $mainPath,
                    'variant_path' => $path2x,
                    'exception' => $exception,
                ]);
                throw $exception;
            }
        } finally {
            if (null !== $image2x) {
                imagedestroy($image2x);
            }
        }

        // Process 3x version (3000x1689). If high-res creation fails, keep flow alive with main fallback.
        $filename3x = $baseFilename . '@3x.webp';
        $path3x = $uploadDir . '/' . $filename3x;
        $image3x = null;
        try {
            $image3x = $this->resizeAndCrop($sourceImage, self::MAIN_WIDTH * 3, self::MAIN_HEIGHT * 3);
            if (!$this->saveWebP($image3x, $path3x)) {
                throw new \RuntimeException('Failed to save 3x image to ' . $path3x, self::SAVE_ERROR_CODE_3X);
            }
        } catch (\Throwable $exception) {
            if ($this->isExpectedVariantSaveFailure($exception, self::SAVE_ERROR_CODE_3X)) {
                $this->logger?->warning('Falling back to main image for 3x gallery variant.', [
                    'source_path' => $sourcePath,
                    'main_path' => $mainPath,
                    'variant_path' => $path3x,
                    'exception' => $exception,
                ]);
                $this->copyFallbackVariant($mainPath, $path3x);
            } else {
                $this->logger?->error('Unexpected 3x gallery variant processing failure.', [
                    'source_path' => $sourcePath,
                    'main_path' => $mainPath,
                    'variant_path' => $path3x,
                    'exception' => $exception,
                ]);
                throw $exception;
            }
        } finally {
            if (null !== $image3x) {
                imagedestroy($image3x);
            }
        }

        // Compatibility alias for historic naming patterns observed in existing data.
        $path3Legacy = $uploadDir . '/' . $baseFilename . '@3.webp';
        if (!file_exists($path3Legacy)) {
            $this->copyFallbackVariant($path3x, $path3Legacy);
        }

        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($mainImage);
        imagedestroy($thumbImage);

        return [
            'main' => $mainFilename,
            'thumb' => $thumbFilename,
            '2x' => $filename2x,
            '3x' => $filename3x,
        ];
    }

    /**
     * Load image from file path (supports JPEG, PNG, GIF, WebP)
     */
    private function loadImage(string $path)
    {
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return false;
        }

        $mimeType = $imageInfo['mime'];

        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * Resize and crop image to exact dimensions (maintains aspect ratio, crops if needed)
     */
    private function resizeAndCrop($sourceImage, int $targetWidth, int $targetHeight)
    {
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Calculate aspect ratios
        $sourceAspect = $sourceWidth / $sourceHeight;
        $targetAspect = $targetWidth / $targetHeight;

        // Calculate dimensions for resizing (maintain aspect ratio)
        if ($sourceAspect > $targetAspect) {
            // Source is wider - fit to height
            $newHeight = $targetHeight;
            $newWidth = (int)($targetHeight * $sourceAspect);
        } else {
            // Source is taller - fit to width
            $newWidth = $targetWidth;
            $newHeight = (int)($targetWidth / $sourceAspect);
        }

        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG/GIF
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
        imagefill($resizedImage, 0, 0, $transparent);

        // Resize
        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Crop to exact dimensions if needed
        if ($newWidth != $targetWidth || $newHeight != $targetHeight) {
            $croppedImage = imagecreatetruecolor($targetWidth, $targetHeight);
            
            // Preserve transparency
            imagealphablending($croppedImage, false);
            imagesavealpha($croppedImage, true);
            $transparent = imagecolorallocatealpha($croppedImage, 0, 0, 0, 127);
            imagefill($croppedImage, 0, 0, $transparent);

            // Calculate crop position (center)
            $cropX = (int)(($newWidth - $targetWidth) / 2);
            $cropY = (int)(($newHeight - $targetHeight) / 2);

            imagecopyresampled(
                $croppedImage,
                $resizedImage,
                0, 0, $cropX, $cropY,
                $targetWidth, $targetHeight,
                $targetWidth, $targetHeight
            );

            imagedestroy($resizedImage);
            return $croppedImage;
        }

        return $resizedImage;
    }

    /**
     * Save image as WebP format
     */
    private function saveWebP($image, string $path): bool
    {
        return imagewebp($image, $path, self::QUALITY);
    }

    /**
     * Copy a generated variant from an existing file.
     */
    private function copyFallbackVariant(string $sourcePath, string $targetPath): void
    {
        if (!file_exists($sourcePath) || !copy($sourcePath, $targetPath)) {
            throw new \Exception('Failed to create fallback variant ' . $targetPath . ' from ' . $sourcePath);
        }
    }

    private function isExpectedVariantSaveFailure(\Throwable $exception, int $expectedCode): bool
    {
        return $exception instanceof \RuntimeException && $exception->getCode() === $expectedCode;
    }
}
