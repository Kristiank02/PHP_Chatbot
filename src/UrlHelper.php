<?php
declare(strict_types=1);

/**
 * Helper class for URL generation
 */
final class UrlHelper
{
    /**
     * Generate full public URL path
     * 
     * @param string $path - Relative path
     * @return string - Full path
     */
    public static function publicPath(string $path): string
    {
        // Base path to public directory
        $base = '/PHP_Chatbot/public/';

        // Remove leading "/" if existent
        $sanitized = ltrim($path, '/');

        // Combine base and path
        return $base . $sanitized;
    }
}