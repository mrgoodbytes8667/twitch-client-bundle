<?php

namespace Bytes\TwitchClientBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapTwitchName
{
    /**
     * @param bool $useName If true, game will be retrieved by name, else will retrieve by id if numeric
     */
    public function __construct(public readonly bool $useName = false)
    {
    }
}
