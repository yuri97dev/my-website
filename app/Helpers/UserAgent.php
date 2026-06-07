<?php

namespace App\Helpers;

use DeviceDetector\DeviceDetector;

class UserAgent
{
    public static function parse(?string $user_agent): array
    {
        if( empty($user_agent) ) return [
            "browser" => null,
            "os" => null,
            "device" => null,
            "brand" => null,
            "model" => null,
        ];

        $detector = new DeviceDetector($user_agent);
        $detector->parse();

        if( $detector->isBot() ) {
            $bot = $detector->getBot();

            return [
                "browser" => $bot["name"] ?? null,
                "os" => null,
                "device" => "bot",
                "brand" => null,
                "model" => null,
            ];
        }

        $client = $detector->getClient();
        $os = $detector->getOs();

        $os_name = $os["name"] ?? null;
        $os_version = $os["version"] ?? null;

        $os_description = $os_name
            ? trim($os_name . ($os_version ? " {$os_version}" : ""))
            : null;

        return [
            "browser" => $client["name"] ?? null,
            "os" => $os_description,
            "device" => $detector->getDeviceName(),
            "brand" => $detector->getBrandName() ?: null,
            "model" => $detector->getModel() ?: null,
        ];
    }
}
