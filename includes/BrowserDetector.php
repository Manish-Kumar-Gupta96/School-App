<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * VIC School ERP Enterprise v2.0
 * Browser Detector
 * -------------------------------------------------------------
 */

class BrowserDetector
{
    private string $userAgent;

    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function getBrowser(): string
    {
        $browsers = [
            'Edge' => '/Edg/i',
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Opera' => '/Opera|OPR/i',
            'Internet Explorer' => '/MSIE|Trident/i'
        ];

        foreach ($browsers as $name => $regex) {
            if (preg_match($regex, $this->userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }
}
