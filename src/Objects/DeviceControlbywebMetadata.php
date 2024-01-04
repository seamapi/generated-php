<?php

namespace Seam\Objects;

class DeviceControlbywebMetadata
{
    
    public static function from_json(mixed $json): DeviceControlbywebMetadata|null
    {
        if (!$json) {
            return null;
        }
        return new self(
            device_id: $json->device_id,
            device_name: $json->device_name,
            relay_name: $json->relay_name,
        );
    }
  

    
    public function __construct(
        public string $device_id,
        public string $device_name,
        public string $relay_name,
    ) {
    }
  
}
