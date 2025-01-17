<?php

namespace Seam\Objects;

class PhoneWyzeMetadata
{
    
    public static function from_json(mixed $json): PhoneWyzeMetadata|null
    {
        if (!$json) {
            return null;
        }
        return new self(
            device_id: $json->device_id,
            device_name: $json->device_name,
            product_name: $json->product_name,
            product_type: $json->product_type,
            product_model: $json->product_model,
            device_info_model: $json->device_info_model,
        );
    }
  

    
    public function __construct(
        public string $device_id,
        public string $device_name,
        public string $product_name,
        public string $product_type,
        public string $product_model,
        public string $device_info_model,
    ) {
    }
  
}
