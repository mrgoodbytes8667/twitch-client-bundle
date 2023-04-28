<?php

namespace Bytes\TwitchClientBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapTwitchUser
{
    /**
     * @param bool $useLogin
     */
    public function __construct(
        public readonly bool $useLogin = false
    )
    {
    }
}
