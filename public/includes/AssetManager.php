<?php
/**
 * Asset Manager Class
 * 
 * Handles asset paths and versioning for the application.
 */

class AssetManager {
    private static ?array $manifest = null;
    private string $manifestPath;
    private string $publicPath;

    /**
     * Constructor
     * 
     * @param string $publicPath Path to the public directory
     */
    public function __construct(string $publicPath) {
        $this->publicPath = $publicPath;
        $this->manifestPath = $publicPath . '/assets/manifest.json';
    }

    /**
     * Get the URL for an asset
     * 
     * @param string $path Path to the asset relative to the assets directory
     * @return string Full URL to the asset
     */
    public function asset(string $path): string {
        if (self::$manifest === null) {
            self::$manifest = $this->loadManifest();
        }

        $assetPath = self::$manifest[$path] ?? $path;
        return $this->baseUrl('/assets/' . $assetPath);
    }

    /**
     * Load the manifest file
     * 
     * @return array Manifest data
     */
    private function loadManifest(): array {
        if (file_exists($this->manifestPath)) {
            return json_decode(file_get_contents($this->manifestPath), true) ?? [];
        }
        return [];
    }

    /**
     * Generate a base URL with the correct base path
     * 
     * @param string $path Path to append to the base URL
     * @return string Full URL
     */
    private function baseUrl(string $path): string {
        return APP_BASE_URI . $path;
    }
}
