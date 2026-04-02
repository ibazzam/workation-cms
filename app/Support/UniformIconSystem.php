<?php

namespace App\Support;

/**
 * Uniform Icon System for Workation CMS
 * 
 * Provides consistent icons across all pages:
 * - Homepage categories
 * - Property amenities
 * - Facility listings
 * - Room features
 * - Service indicators
 * 
 * Uses HTML5 semantic icons (emojis) for consistency
 */
class UniformIconSystem
{
    /**
     * Category icons mapping (used on homepage, category filters, breadcrumbs)
     */
    public static function getCategoryIcon(string $category): array
    {
        $categories = [
            'accommodation' => ['emoji' => '🏨', 'label' => 'Accommodation', 'color' => '#0f6179'],
            'transport' => ['emoji' => '🚤', 'label' => 'Transport', 'color' => '#1d7a8f'],
            'excursion' => ['emoji' => '🌊', 'label' => 'Excursion', 'color' => '#2a8a95'],
            'remote_workspace' => ['emoji' => '💻', 'label' => 'Remote Workspace', 'color' => '#1a6f7f'],
            'resort_day_visit' => ['emoji' => '🏝️', 'label' => 'Resort Day Visit', 'color' => '#0f6179'],
            'restaurant' => ['emoji' => '🍽️', 'label' => 'Restaurant', 'color' => '#2d7f8a'],
            'vehicle_rental' => ['emoji' => '🚗', 'label' => 'Vehicle Rental', 'color' => '#1b7885'],
        ];

        $key = strtolower(trim($category));
        return $categories[$key] ?? ['emoji' => '📌', 'label' => ucfirst($key), 'color' => '#0f6179'];
    }

    /**
     * Amenity icons mapping (used on property pages, room details, etc.)
     * Maps amenity keywords to emojis for consistency
     */
    public static function getAmenityIcon(string $amenity): string
    {
        $amenityLower = strtolower(trim($amenity));

        // Food & Dining
        if (preg_match('/(restaurant|dining|bar|cafe|kitchen|breakfast)/i', $amenityLower)) {
            return '🍽️';
        }
        
        // Pool & Water
        if (preg_match('/(pool|swim|water|jacuzzi|spa|sauna|steam)/i', $amenityLower)) {
            return '🏊';
        }
        
        // Fitness & Health
        if (preg_match('/(gym|fitness|yoga|wellness|massage|health)/i', $amenityLower)) {
            return '💪';
        }
        
        // WiFi & Technology
        if (preg_match('/(wifi|wi-fi|internet|broadband|connection)/i', $amenityLower)) {
            return '📶';
        }
        
        // Parking
        if (preg_match('/(parking|car|vehicle|garage)/i', $amenityLower)) {
            return '🅿️';
        }
        
        // Family & Kids
        if (preg_match('/(kids|family|children|toy|playground)/i', $amenityLower)) {
            return '👨‍👩‍👧‍👦';
        }
        
        // Air Conditioning
        if (preg_match('/(ac|air condition|cooling)/i', $amenityLower)) {
            return '❄️';
        }
        
        // Heating
        if (preg_match('/(heating|warm|hot water)/i', $amenityLower)) {
            return '🔥';
        }
        
        // Laundry
        if (preg_match('/(laundry|wash|iron|clothes)/i', $amenityLower)) {
            return '👕';
        }
        
        // TV & Entertainment
        if (preg_match('/(tv|television|entertainment|movie|streaming)/i', $amenityLower)) {
            return '📺';
        }
        
        // Workspace
        if (preg_match('/(desk|workspace|office|work|table|chair|work space)/i', $amenityLower)) {
            return '🖥️';
        }
        
        // Bedroom & Bedding
        if (preg_match('/(bed|bedroom|sleep|mattress)/i', $amenityLower)) {
            return '🛏️';
        }
        
        // Bathroom
        if (preg_match('/(bathroom|shower|bath|toilet|sink)/i', $amenityLower)) {
            return '🚿';
        }
        
        // View
        if (preg_match('/(view|vista|ocean|sea|garden|landscape|scenic)/i', $amenityLower)) {
            return '🌅';
        }
        
        // Security & Safety
        if (preg_match('/(safe|security|lock|cctv|alarm|security)/i', $amenityLower)) {
            return '🔒';
        }
        
        // Outdoor & Garden
        if (preg_match('/(garden|outdoor|patio|balcony|terrace|deck)/i', $amenityLower)) {
            return '🌿';
        }
        
        // Beach & Water Access
        if (preg_match('/(beach|sand|ocean|water access)/i', $amenityLower)) {
            return '🏖️';
        }
        
        // Transportation
        if (preg_match('/(airport|transfer|transport|shuttle|pickup)/i', $amenityLower)) {
            return '🚕';
        }
        
        // Pets
        if (preg_match('/(pet|dog|cat|animal)/i', $amenityLower)) {
            return '🐕';
        }
        
        // Smoking & Non-smoking
        if (preg_match('/(smoking|non-smoking|smoke-free)/i', $amenityLower)) {
            return '🚭';
        }
        
        // Accessibility
        if (preg_match('/(wheelchair|accessible|ada|disabled)/i', $amenityLower)) {
            return '♿';
        }
        
        // Outdoor Activities
        if (preg_match('/(diving|snorkel|surf|watersport|adventure|activity)/i', $amenityLower)) {
            return '🏄';
        }
        
        // Default icon
        return '✨';
    }

    /**
     * Facility icons (legacy - maps to emoji for uniformity)
     */
    public static function getFacilityIcon(string $facility): string
    {
        return self::getAmenityIcon($facility);
    }

    /**
     * Room feature icons
     */
    public static function getRoomFeatureIcon(string $feature): string
    {
        return self::getAmenityIcon($feature);
    }

    /**
     * Transport mode icons
     */
    public static function getTransportIcon(string $mode): string
    {
        $modeLower = strtolower(trim($mode));
        
        $transports = [
            'speedboat' => '🚤',
            'ferry' => '⛴️',
            'dhoni' => '🛥️',
            'seaplane' => '✈️',
            'helicopter' => '🚁',
            'car' => '🚗',
            'van' => '🚐',
            'taxi' => '🚕',
            'bike' => '🏍️',
            'motorcycle' => '🏍️',
            'scooter' => '🛵',
            'domestic_flight' => '✈️',
            'flight' => '✈️',
            'air' => '✈️',
        ];

        foreach ($transports as $key => $emoji) {
            if (strpos($modeLower, $key) !== false) {
                return $emoji;
            }
        }

        return '🚗';
    }

    /**
     * Service category icons
     */
    public static function getServiceIcon(string $service): string
    {
        return self::getAmenityIcon($service);
    }

    /**
     * Get all category icons with info
     */
    public static function getAllCategoryIcons(): array
    {
        return [
            'accommodation' => self::getCategoryIcon('accommodation'),
            'transport' => self::getCategoryIcon('transport'),
            'excursion' => self::getCategoryIcon('excursion'),
            'remote_workspace' => self::getCategoryIcon('remote_workspace'),
            'resort_day_visit' => self::getCategoryIcon('resort_day_visit'),
            'restaurant' => self::getCategoryIcon('restaurant'),
            'vehicle_rental' => self::getCategoryIcon('vehicle_rental'),
        ];
    }

    /**
     * Render emoji icon with styling
     */
    public static function renderIcon(string $emoji, string $class = ''): string
    {
        $class = trim($class);
        $classList = 'uniform-icon' . ($class ? ' ' . $class : '');
        return sprintf(
            '<span class="%s" role="img" aria-hidden="true">%s</span>',
            htmlspecialchars($classList, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Render emoji with label wrapper
     */
    public static function renderIconWithLabel(string $emoji, string $label, string $class = ''): string
    {
        $class = trim($class);
        $classList = 'uniform-icon-label' . ($class ? ' ' . $class : '');
        return sprintf(
            '<span class="%s"><span class="uniform-icon" role="img" aria-hidden="true">%s</span><span class="uniform-label">%s</span></span>',
            htmlspecialchars($classList, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Get icon info with styling data
     */
    public static function getIconInfo(string $category, string $type = 'category'): array
    {
        switch ($type) {
            case 'category':
                return self::getCategoryIcon($category);
            case 'amenity':
            case 'facility':
                return [
                    'emoji' => self::getAmenityIcon($category),
                    'label' => ucwords(str_replace('_', ' ', $category)),
                ];
            case 'transport':
                return [
                    'emoji' => self::getTransportIcon($category),
                    'label' => ucwords(str_replace('_', ' ', $category)),
                ];
            default:
                return ['emoji' => '✨', 'label' => $category];
        }
    }
}
