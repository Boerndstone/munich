<?php

namespace App\Tests\Service;

use App\Service\ImageSeoService;
use PHPUnit\Framework\TestCase;

class ImageSeoServiceTest extends TestCase
{
    private ImageSeoService $service;

    protected function setUp(): void
    {
        $this->service = new ImageSeoService();
    }

    public function testGetSocialMediaImageUrl(): void
    {
        $result = $this->service->getSocialMediaImageUrl('test-rock', 'rock');
        $this->assertEquals('https://www.munichclimbs.de/build/images/rock/test-rock.jpg', $result);
    }

    public function testGetSocialMediaImageUrlWithArea(): void
    {
        $result = $this->service->getSocialMediaImageUrl('test-area', 'area');
        $this->assertEquals('https://www.munichclimbs.de/build/images/areas/test-area.webp', $result);
    }

    public function testGetSocialMediaImageUrlWithNull(): void
    {
        $result = $this->service->getSocialMediaImageUrl(null, 'rock');
        $this->assertNull($result);
    }

    public function testGetSocialMediaImageUrlWithEmptyString(): void
    {
        $result = $this->service->getSocialMediaImageUrl('', 'rock');
        $this->assertEquals('https://www.munichclimbs.de/build/images/rock/.jpg', $result);
    }

    public function testGetSocialMediaImageDimensions(): void
    {
        $dimensions = $this->service->getSocialMediaImageDimensions('rock');
        $this->assertEquals(['width' => 1200, 'height' => 630], $dimensions);
    }

    public function testGenerateImageAltText(): void
    {
        $altText = $this->service->generateImageAltText('Test Rock', 'Test Area', 'rock');
        $this->assertEquals('Kletterfels Test Rock in Test Area - Klettergebiet München', $altText);
    }

    public function testGenerateImageAltTextWithArea(): void
    {
        $altText = $this->service->generateImageAltText('Test Rock', 'Test Area', 'area');
        $this->assertEquals('Klettergebiet Test Area - Klettern rund um München', $altText);
    }

    public function testIsImageAccessible(): void
    {
        $result = $this->service->isImageAccessible('test-rock', 'rock');
        $this->assertTrue($result);
    }

    public function testIsImageAccessibleWithNull(): void
    {
        $result = $this->service->isImageAccessible(null, 'rock');
        $this->assertFalse($result);
    }
} 