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

        $sourceImage = $this->loadImage($sourcePath);
        if ($sourceImage === false) {
            throw new \RuntimeException('Failed to load image: ' . $sourcePath);
        }

        $h1x = (int) round(self::WIDTH_1X * self::VIEW_H / self::VIEW_W);
        $img1x = $this->resizeAndCrop($sourceImage, self::WIDTH_1X, $h1x);
        $path1x = $outputDir . '/' . $basename . '.webp';
        if (!imagewebp($img1x, $path1x, self::QUALITY)) {
            throw new \RuntimeException('Failed to write ' . $path1x);
        }

        $img2x = $this->resizeAndCrop($sourceImage, self::WIDTH_2X, self::VIEW_H);
        $path2x = $outputDir . '/' . $basename . '@2x.webp';
        if (!imagewebp($img2x, $path2x, self::QUALITY)) {
            throw new \RuntimeException('Failed to write ' . $path2x);
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
     * @param resource $sourceImage
     *
     * @return resource
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
