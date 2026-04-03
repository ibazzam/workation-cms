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
            'accommodation'    => ['icon' => 'fa-solid fa-hotel',           'label' => 'Accommodation',    'color' => '#0f6179'],
            'transport'        => ['icon' => 'fa-solid fa-ship',            'label' => 'Transport',         'color' => '#1d7a8f'],
            'excursion'        => ['icon' => 'fa-solid fa-compass',         'label' => 'Excursion',         'color' => '#2a8a95'],
            'remote_workspace' => ['icon' => 'fa-solid fa-laptop',          'label' => 'Remote Workspace',  'color' => '#1a6f7f'],
            'resort_day_visit' => ['icon' => 'fa-solid fa-umbrella-beach',  'label' => 'Resort Day Visit',  'color' => '#0f6179'],
            'restaurant'       => ['icon' => 'fa-solid fa-utensils',        'label' => 'Restaurant',        'color' => '#2d7f8a'],
            'vehicle_rental'   => ['icon' => 'fa-solid fa-car',             'label' => 'Vehicle Rental',    'color' => '#1b7885'],
        ];

        $key = strtolower(trim($category));
        return $categories[$key] ?? ['icon' => 'fa-solid fa-location-dot', 'label' => ucfirst($key), 'color' => '#0f6179'];
    }

    /**
     * Amenity icons mapping (used on property pages, room details, etc.)
     * Maps amenity keywords to emojis for consistency
     */
    public static function getAmenityIcon(string $amenity): string
    {
        $amenityLower = strtolower(trim($amenity));
        $amenityNormalized = preg_replace('/[_-]+/', ' ', $amenityLower) ?? $amenityLower;
        $amenityNormalized = preg_replace('/\s+/', ' ', $amenityNormalized) ?? $amenityNormalized;

        // Food & Dining
        if (preg_match('/(restaurant|dining|bar|cafe|kitchen|breakfast)/i', $amenityNormalized)) {
            return 'fa-solid fa-utensils';
        }

        // Pool & Water
        if (preg_match('/(pool|swim|water|jacuzzi|spa|sauna|steam)/i', $amenityNormalized)) {
            return 'fa-solid fa-person-swimming';
        }

        // Fitness & Health
        if (preg_match('/(gym|fitness|yoga|wellness|massage|health)/i', $amenityNormalized)) {
            return 'fa-solid fa-dumbbell';
        }
        
        // WiFi & Technology
        if (preg_match('/(wifi|wi-fi|internet|broadband|connection)/i', $amenityNormalized)) {
            return 'fa-solid fa-wifi';
        }

        // Parking
        if (preg_match('/(parking|car|vehicle|garage)/i', $amenityNormalized)) {
            return 'fa-solid fa-square-parking';
        }

        // Family & Kids
        if (preg_match('/(kids|family|children|toy|playground)/i', $amenityNormalized)) {
            return 'fa-solid fa-children';
        }

        // Accessibility (must run before AC checks to avoid false positives like "wheelchair_access")
        if (preg_match('/(wheelchair|accessible|ada|disabled|accessibility)/i', $amenityNormalized)) {
            return 'fa-solid fa-wheelchair';
        }

        // Air Conditioning
        if (preg_match('/\b(a\/c|ac|air\s*condition(?:ing)?|aircon|cooling)\b/i', $amenityNormalized)) {
            return 'fa-solid fa-snowflake';
        }

        // Heating
        if (preg_match('/(heating|warm|hot water)/i', $amenityNormalized)) {
            return 'fa-solid fa-fire';
        }

        // Laundry
        if (preg_match('/(laundry|wash|iron|clothes)/i', $amenityNormalized)) {
            return 'fa-solid fa-shirt';
        }

        // TV & Entertainment
        if (preg_match('/(tv|television|entertainment|movie|streaming)/i', $amenityNormalized)) {
            return 'fa-solid fa-tv';
        }

        // Workspace
        if (preg_match('/(desk|workspace|office|work|table|chair|work space)/i', $amenityNormalized)) {
            return 'fa-solid fa-desktop';
        }

        // Bedroom & Bedding
        if (preg_match('/(bed|bedroom|sleep|mattress)/i', $amenityNormalized)) {
            return 'fa-solid fa-bed';
        }

        // Bathroom
        if (preg_match('/(bathroom|shower|bath|toilet|sink)/i', $amenityNormalized)) {
            return 'fa-solid fa-shower';
        }

        // View
        if (preg_match('/(view|vista|ocean|sea|landscape|scenic)/i', $amenityNormalized)) {
            return 'fa-solid fa-binoculars';
        }

        // Security & Safety
        if (preg_match('/(safe|security|lock|cctv|alarm)/i', $amenityNormalized)) {
            return 'fa-solid fa-lock';
        }

        // Outdoor & Garden
        if (preg_match('/(garden|outdoor|patio|balcony|terrace|deck)/i', $amenityNormalized)) {
            return 'fa-solid fa-leaf';
        }

        // Beach & Water Access
        if (preg_match('/(beach|sand|ocean|water access)/i', $amenityNormalized)) {
            return 'fa-solid fa-umbrella-beach';
        }

        // Transportation
        if (preg_match('/(airport|transfer|transport|shuttle|pickup)/i', $amenityNormalized)) {
            return 'fa-solid fa-taxi';
        }

        // Pets
        if (preg_match('/(pet|dog|cat|animal)/i', $amenityNormalized)) {
            return 'fa-solid fa-paw';
        }

        // Smoking & Non-smoking
        if (preg_match('/(smoking|non-smoking|smoke-free)/i', $amenityNormalized)) {
            return 'fa-solid fa-ban';
        }

        // Outdoor Activities
        if (preg_match('/(diving|snorkel|surf|watersport|adventure|activity)/i', $amenityNormalized)) {
            return 'fa-solid fa-person-surfing';
        }

        // Default icon
        return 'fa-solid fa-star';
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
            'speedboat'       => 'fa-solid fa-ship',
            'ferry'           => 'fa-solid fa-ferry',
            'dhoni'           => 'fa-solid fa-ship',
            'seaplane'        => 'fa-solid fa-plane',
            'helicopter'      => 'fa-solid fa-helicopter',
            'car'             => 'fa-solid fa-car',
            'van'             => 'fa-solid fa-van-shuttle',
            'taxi'            => 'fa-solid fa-taxi',
            'bike'            => 'fa-solid fa-motorcycle',
            'motorcycle'      => 'fa-solid fa-motorcycle',
            'scooter'         => 'fa-solid fa-motorcycle',
            'domestic_flight' => 'fa-solid fa-plane',
            'flight'          => 'fa-solid fa-plane',
            'air'             => 'fa-solid fa-plane',
        ];

        foreach ($transports as $key => $iconClass) {
            if (strpos($modeLower, $key) !== false) {
                return $iconClass;
            }
        }

        return 'fa-solid fa-car';
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
    public static function renderIcon(string $icon, string $class = ''): string
    {
        $extra = trim($class);
        $classList = 'uniform-icon' . ($extra ? ' ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') : '');
        return sprintf(
            '<i class="%s %s" aria-hidden="true"></i>',
            htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
            $classList
        );
    }

    /**
     * Render icon with label wrapper
     */
    public static function renderIconWithLabel(string $icon, string $label, string $class = ''): string
    {
        $extra = trim($class);
        $classList = 'uniform-icon-label' . ($extra ? ' ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') : '');
        return sprintf(
            '<span class="%s"><i class="%s uniform-icon" aria-hidden="true"></i><span class="uniform-label">%s</span></span>',
            $classList,
            htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'),
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
