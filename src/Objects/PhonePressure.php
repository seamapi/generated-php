<?php

namespace Seam\Objects;

class PhonePressure
{
    
    public static function from_json(mixed $json): PhonePressure|null
    {
        if (!$json) {
            return null;
        }
        return new self(
            time: $json->time,
            value: $json->value,
        );
    }
  

    
    public function __construct(
        public string $time,
        public float $value,
    ) {
    }
  
}
