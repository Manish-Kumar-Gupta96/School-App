<?php
class BrowserDetector {
    /**
     * Parsing user agent patterns cleanly without heavyweight dependencies
     */
    public static function detect($userAgent) {
        $browser = "Unknown Browser";
        
        if (empty($userAgent)) {
            return $browser;
        }

        $userAgent = strtoupper($userAgent);

        if (strpos($userAgent, 'OPR/') !== false || strpos($userAgent, 'OPERA') !== false) {
            $browser = 'Opera';
        } elseif (strpos($userAgent, 'EDGE') !== false || strpos($userAgent, 'EDG/') !== false) {
            $browser = 'Microsoft Edge';
        } elseif (strpos($userAgent, 'CHROME') !== false) {
            $browser = 'Google Chrome';
        } elseif (strpos($userAgent, 'SAFARI') !== false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'FIREFOX') !== false) {
            $browser = 'Mozilla Firefox';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'TRIDENT/') !== false) {
            $browser = 'Internet Explorer';
        }

        return $browser;
    }
}
?>
