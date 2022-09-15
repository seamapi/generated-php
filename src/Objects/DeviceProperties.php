<?php

namespace Seam\Objects;

class DeviceProperties
{
    public static function from_json(mixed $json): DeviceProperties|null
    {
        if (!$json) {
            return null;
        }
        return new self(
            online: $json->online ?? null,
            locked: $json->locked ?? null,
            name: $json->name ?? null,
            door_open: $json->door_open ?? null,
            battery_level: $json->battery_level ?? null,
            schlage_metadata: $json->schlage_metadata ?? null,
            august_metadata: $json->august_metadata ?? null,
            smartthings_metadata: $json->smartthings_metadata ?? null
        );
    }

    public function __construct(
        public bool $online,
        public bool $locked,
        public bool $door_open,
        public float $battery_level,
        public string $name,

        public mixed $august_metadata,
        public mixed $schlage_metadata,
        public mixed $smartthings_metadata
    ) {
    }
}
