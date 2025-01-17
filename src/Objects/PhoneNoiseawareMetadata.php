<?php

namespace Seam\Objects;

class PhoneNoiseawareMetadata
{
    
    public static function from_json(mixed $json): PhoneNoiseawareMetadata|null
    {
        if (!$json) {
            return null;
        }
        return new self(
            device_model: $json->device_model,
            noise_level_nrs: $json->noise_level_nrs,
            noise_level_decibel: $json->noise_level_decibel,
            device_name: $json->device_name,
            device_id: $json->device_id,
        );
    }
  

    
    public function __construct(
        public string $device_model,
        public float $noise_level_nrs,
        public float $noise_level_decibel,
        public string $device_name,
        public string $device_id,
    ) {
    }
  
}
