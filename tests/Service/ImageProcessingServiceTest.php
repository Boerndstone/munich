<?php

namespace App\Service {
    final class ImageWebpTestBehavior
    {
        public static bool $fail2x = false;
        public static bool $fail3x = false;
    }

    function imagewebp($image, ?string $file = null, int $quality = -1): bool
    {
        if (\is_string($file)) {
            if (ImageWebpTestBehavior::$fail2x && str_contains($file, '@2x.webp')) {
                return false;
            }

            if (ImageWebpTestBehavior::$fail3x && str_contains($file, '@3x.webp')) {
                return false;
            }
        }

        return \imagewebp($image, $file, $quality);
    }
}

namespace App\Tests\Service {
    use App\Service\ImageProcessingService;
    use App\Service\ImageWebpTestBehavior;
    use PHPUnit\Framework\TestCase;

    class ImageProcessingServiceTest extends TestCase
    {
        private ImageProcessingService $service;
        private string $tmpDir;

        protected function setUp(): void
        {
            if (!extension_loaded('gd') || !function_exists('imagewebp')) {
                $this->markTestSkipped('GD with WebP support is required for ImageProcessingService tests.');
            }

            $this->service = new ImageProcessingService();
            $this->tmpDir = sys_get_temp_dir() . '/image-processing-test-' . uniqid('', true);
            mkdir($this->tmpDir, 0755, true);

            ImageWebpTestBehavior::$fail2x = false;
            ImageWebpTestBehavior::$fail3x = false;
        }

        protected function tearDown(): void
        {
            ImageWebpTestBehavior::$fail2x = false;
            ImageWebpTestBehavior::$fail3x = false;

            if (!is_dir($this->tmpDir)) {
                return;
            }

            $files = scandir($this->tmpDir);
            if (\is_array($files)) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $fullPath = $this->tmpDir . '/' . $file;
                    if (is_file($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            rmdir($this->tmpDir);
        }

        public function testProcessUploadedImageGeneratesAllVariantsSuccessfully(): void
        {
            $sourcePath = $this->createSourceImage();
            $result = $this->service->processUploadedImage($sourcePath, 'sample', $this->tmpDir);

            $this->assertSame('sample.webp', $result['main']);
            $this->assertSame('sample_thumb.webp', $result['thumb']);
            $this->assertSame('sample@2x.webp', $result['2x']);
            $this->assertSame('sample@3x.webp', $result['3x']);

            $mainPath = $this->tmpDir . '/sample.webp';
            $thumbPath = $this->tmpDir . '/sample_thumb.webp';
            $path2x = $this->tmpDir . '/sample@2x.webp';
            $path3x = $this->tmpDir . '/sample@3x.webp';

            $this->assertFileExists($mainPath);
            $this->assertFileExists($thumbPath);
            $this->assertFileExists($path2x);
            $this->assertFileExists($path3x);

            $mainSize = getimagesize($mainPath);
            $thumbSize = getimagesize($thumbPath);
            $size2x = getimagesize($path2x);
            $size3x = getimagesize($path3x);

            $this->assertSame(1000, $mainSize[0]);
            $this->assertSame(563, $mainSize[1]);
            $this->assertSame(110, $thumbSize[0]);
            $this->assertSame(56, $thumbSize[1]);
            $this->assertSame(2000, $size2x[0]);
            $this->assertSame(1126, $size2x[1]);
            $this->assertSame(3000, $size3x[0]);
            $this->assertSame(1689, $size3x[1]);
        }

        public function testProcessUploadedImageFallsBackToMainWhen2xAnd3xSaveFails(): void
        {
            ImageWebpTestBehavior::$fail2x = true;
            ImageWebpTestBehavior::$fail3x = true;

            $sourcePath = $this->createSourceImage();
            $this->service->processUploadedImage($sourcePath, 'fallback', $this->tmpDir);

            $mainPath = $this->tmpDir . '/fallback.webp';
            $path2x = $this->tmpDir . '/fallback@2x.webp';
            $path3x = $this->tmpDir . '/fallback@3x.webp';

            $this->assertFileExists($mainPath);
            $this->assertFileExists($path2x);
            $this->assertFileExists($path3x);

            $this->assertSame(hash_file('sha1', $mainPath), hash_file('sha1', $path2x));
            $this->assertSame(hash_file('sha1', $mainPath), hash_file('sha1', $path3x));
        }

        public function testProcessUploadedImageCreatesLegacy3xAliasFile(): void
        {
            $sourcePath = $this->createSourceImage();
            $this->service->processUploadedImage($sourcePath, 'legacy', $this->tmpDir);

            $path3x = $this->tmpDir . '/legacy@3x.webp';
            $legacyPath3 = $this->tmpDir . '/legacy@3.webp';

            $this->assertFileExists($path3x);
            $this->assertFileExists($legacyPath3);
            $this->assertSame(hash_file('sha1', $path3x), hash_file('sha1', $legacyPath3));
        }

        private function createSourceImage(): string
        {
            $image = imagecreatetruecolor(1600, 900);
            $background = imagecolorallocate($image, 40, 120, 200);
            imagefilledrectangle($image, 0, 0, 1600, 900, $background);

            $sourcePath = $this->tmpDir . '/source.jpg';
            imagejpeg($image, $sourcePath, 90);
            imagedestroy($image);

            return $sourcePath;
        }
    }
}
