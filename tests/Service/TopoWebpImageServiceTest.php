<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TopoWebpImageService;
use PHPUnit\Framework\TestCase;

final class TopoWebpImageServiceTest extends TestCase
{
    public function testWritesWebpVariants(): void
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            self::markTestSkipped('GD with WebP required.');
        }

        $dir = sys_get_temp_dir() . '/topo_webp_test_' . uniqid('', true);
        self::assertTrue(mkdir($dir) || is_dir($dir));

        try {
            $src = $dir . '/src.png';
            $im = imagecreatetruecolor(2000, 1600);
            self::assertNotFalse($im);
            imagecolorallocate($im, 200, 100, 50);
            self::assertTrue(imagepng($im, $src));

            $service = new TopoWebpImageService();
            $service->writeTopoVariantsFromFile($src, 'unittest-topo', $dir);

            self::assertFileExists($dir . '/unittest-topo.webp');
            self::assertFileExists($dir . '/unittest-topo@2x.webp');
            self::assertGreaterThan(100, filesize($dir . '/unittest-topo.webp'));
        } finally {
            foreach (glob($dir . '/*') ?: [] as $f) {
                if (is_file($f)) {
                    unlink($f);
                }
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }
}
