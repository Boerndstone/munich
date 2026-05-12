<?php

namespace App\Service;

class ImageSeoService
{
    private string $baseUrl = 'https://www.munichclimbs.de';
    
    /**
     * Get optimized image URL for social media sharing
     */
    public function getSocialMediaImageUrl(?string $imageName, string $type = 'rock'): ?string
    {
        if (empty($imageName)) {
            return null;
        }
        
        $imagePath = match($type) {
            'rock' => "/build/images/rock/{$imageName}.jpg",
            'area' => "/build/images/areas/{$imageName}.webp",
            'header' => "/build/images/headerImages/{$imageName}.webp",
            default => "/build/images/rock/{$imageName}.jpg"
        };
        
        return $this->baseUrl . $imagePath;
    }
    
    /**
     * Get image dimensions for social media
     */
    public function getSocialMediaImageDimensions(string $type = 'rock'): array
    {
        return match($type) {
            'rock' => ['width' => 1200, 'height' => 630],
            'area' => ['width' => 1200, 'height' => 630],
            'header' => ['width' => 1200, 'height' => 630],
            default => ['width' => 1200, 'height' => 630]
        };
    }
    
    /**
     * Generate alt text for images
     */
    public function generateImageAltText(string $rockName, string $areaName, string $type = 'rock'): string
    {
        return match($type) {
            'rock' => "Kletterfels {$rockName} in {$areaName} - Klettergebiet München",
            'area' => "Klettergebiet {$areaName} - Klettern rund um München",
            'header' => "Header Bild {$rockName} in {$areaName}",
            default => "Kletterfels {$rockName} in {$areaName}"
        };
    }
    
    /**
     * Check if image exists and is accessible
     */
    public function isImageAccessible(?string $imageName, string $type = 'rock'): bool
    {
        if (empty($imageName)) {
            return false;
        }
        
        $imageUrl = $this->getSocialMediaImageUrl($imageName, $type);
        
        // Basic validation - in production you might want to actually check the file
        return !empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL);
    }
} 