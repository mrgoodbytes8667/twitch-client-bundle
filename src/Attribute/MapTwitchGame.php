<?php

namespace Bytes\TwitchClientBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapTwitchGame extends MapTwitchName
{
    /**
     * @param bool $useIgdbId If true, game will be retrieved by igdb_id
     * @param bool $useName If true, game will be retrieved by name, else will retrieve by id if numeric
     */
    public function __construct(public readonly bool $useIgdbId = false, bool $useName = false)
    {
        parent::__construct($useName);
    }
}
