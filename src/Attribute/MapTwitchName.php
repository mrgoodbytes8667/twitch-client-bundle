<?php

namespace Bytes\TwitchClientBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapTwitchName
{
    /**
     * @param bool $useName
     */
    public function __construct(
        public readonly bool $useName = false
    )
    {
    }
}
