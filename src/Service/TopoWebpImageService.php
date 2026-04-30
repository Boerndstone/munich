<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Writes topo raster images to {@see TopoSvgParser} conventions:
 * `{basename}.webp` (750w) and `{basename}@2x.webp` (1024w), aspect 1024:820.
 */
final class TopoWebpImageService
{
    private const VIEW_W = 1024;
    private const VIEW_H = 820;
    private const WIDTH_1X = 750;
    private const WIDTH_2X = 1024;
    private const QUALITY = 90;

    /**
     * @throws \RuntimeException on GD / IO failure
     */
    public function writeTopoVariantsFromFile(string $sourcePath, string $basename, string $outputDir): void
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            throw new \RuntimeException('GD with WebP support is required for topo image processing.');
        }

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
            throw new \RuntimeException('Cannot create topo image directory: ' . $outputDir);
        }

        $sourceImage = null;
        $img1x = null;
        $img2x = null;

        try {
            $sourceImage = $this->loadImage($sourcePath);
            if ($sourceImage === false) {
                throw new \RuntimeException('Failed to load image: ' . $sourcePath);
            }

            $h1x = (int) round(self::WIDTH_1X * self::VIEW_H / self::VIEW_W);
            $img1x = $this->resizeAndCrop($sourceImage, self::WIDTH_1X, $h1x);
            if ($img1x === false) {
                throw new \RuntimeException('Failed to resize/crop image for ' . $basename . '.webp');
            }

            $path1x = $outputDir . '/' . $basename . '.webp';
            if (!imagewebp($img1x, $path1x, self::QUALITY)) {
                throw new \RuntimeException('Failed to write ' . $path1x);
            }

            $img2x = $this->resizeAndCrop($sourceImage, self::WIDTH_2X, self::VIEW_H);
            if ($img2x === false) {
                throw new \RuntimeException('Failed to resize/crop image for ' . $basename . '@2x.webp');
            }

            $path2x = $outputDir . '/' . $basename . '@2x.webp';
            if (!imagewebp($img2x, $path2x, self::QUALITY)) {
                throw new \RuntimeException('Failed to write ' . $path2x);
            }
        } finally {
            $this->destroyImage($img2x);
            $this->destroyImage($img1x);
            $this->destroyImage($sourceImage);
        }
    }

    /**
     * @param mixed $image
     */
    private function destroyImage($image): void
    {
        if ($image instanceof \GdImage || is_resource($image)) {
            imagedestroy($image);
        }
    }

    /**
     * @return resource|false
     */
    private function loadImage(string $path)
    {
        $imageInfo = getimagesize($path);
        if ($imageInfo === false) {
            return false;
        }

        return match ($imageInfo['mime']) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * @param resource|\GdImage $sourceImage
     *
     * @return resource|\GdImage
     *
     * @throws \RuntimeException when GD cannot allocate an image buffer
     */
    private function resizeAndCrop($sourceImage, int $targetWidth, int $targetHeight)
    {
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        $sourceAspect = $sourceWidth / $sourceHeight;
        $targetAspect = $targetWidth / $targetHeight;

        if ($sourceAspect > $targetAspect) {
            $newHeight = $targetHeight;
            $newWidth = (int) ($targetHeight * $sourceAspect);
        } else {
            $newWidth = $targetWidth;
            $newHeight = (int) ($targetWidth / $sourceAspect);
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($resizedImage === false) {
            throw new \RuntimeException(sprintf(
                'imagecreatetruecolor() failed for resize buffer (%dx%d).',
                $newWidth,
                $newHeight,
            ));
        }
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
        imagefill($resizedImage, 0, 0, $transparent);

        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        if ($newWidth !== $targetWidth || $newHeight !== $targetHeight) {
            $croppedImage = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($croppedImage === false) {
                $this->destroyImage($resizedImage);
                throw new \RuntimeException(sprintf(
                    'imagecreatetruecolor() failed for crop buffer (%dx%d).',
                    $targetWidth,
                    $targetHeight,
                ));
            }
            imagealphablending($croppedImage, false);
            imagesavealpha($croppedImage, true);
            $transparent2 = imagecolorallocatealpha($croppedImage, 0, 0, 0, 127);
            imagefill($croppedImage, 0, 0, $transparent2);

            $cropX = (int) (($newWidth - $targetWidth) / 2);
            $cropY = (int) (($newHeight - $targetHeight) / 2);

            imagecopyresampled(
                $croppedImage,
                $resizedImage,
                0, 0, $cropX, $cropY,
                $targetWidth, $targetHeight,
                $targetWidth, $targetHeight
            );

            return $croppedImage;
        }

        return $resizedImage;
    }
}
