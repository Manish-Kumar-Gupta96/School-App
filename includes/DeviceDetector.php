<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * VIC School ERP Enterprise v2.0
 * Device Detector
 * -------------------------------------------------------------
 */

class DeviceDetector
{
    private string $userAgent;

    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = strtolower($userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    public function getDeviceType(): string
    {
        if (preg_match('/tablet|ipad/', $this->userAgent)) {
            return 'Tablet';
        }

        if (preg_match('/mobile|android|iphone/', $this->userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    public function getOperatingSystem(): string
    {
        $systems = [
            'Windows 11' => '/windows nt 10.0/i',
            'Windows 10' => '/windows nt 10.0/i',
            'Windows 8.1' => '/windows nt 6.3/i',
            'Windows 8' => '/windows nt 6.2/i',
            'Windows 7' => '/windows nt 6.1/i',
            'Linux' => '/linux/i',
            'Ubuntu' => '/ubuntu/i',
            'Mac OS' => '/macintosh|mac os x/i',
            'iPhone' => '/iphone/i',
            'iPad' => '/ipad/i',
            'Android' => '/android/i'
        ];

        foreach ($systems as $name => $regex) {
            if (preg_match($regex, $this->userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }
}
