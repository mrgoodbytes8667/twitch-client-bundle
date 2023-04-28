<?php


namespace Bytes\TwitchClientBundle\Tests\Fixtures;


/**
 * Class Fixture
 * @package Bytes\TwitchClientBundle\Tests\Fixtures
 */
class Fixture
{
    /**
     * A randomly generated client ID
     * @var string
     */
    const CLIENT_ID = '586568566858692924';

    /**
     * A randomly generated client secret
     * @var string
     */
    const CLIENT_SECRET = 'RsgAvyEhVKD9Qrjeg2J9iFw1u5fQswmw';

    /**
     * A randomly generated client public key
     * @var string
     */
    const HUB_SECRET = '3c0550fa220b400914edabf283ac1174bf4f99d55781d1ff8ff0e96b02583aec';

    /**
     * A randomly generated bot token
     * @var string
     */
    const BOT_TOKEN = '84B.cGe2KLFHp6MWtMetEm5m7-mmDcYKuNr3XeuoKvkwBfPhunm.o.BTtNk';

    /**
     * A generic user agent
     * @var string
     */
    const USER_AGENT = 'Twitch Client Bundle PHPUnit Test (https://www.github.com, 0.0.1)';

    /**
     * @param string $file
     * @return string
     */
    public static function getFixturesFile(string $file)
    {
        return __DIR__ . '/' . $file;
    }

    /**
     * @param string|null $file
     * @return string|null
     */
    public static function getFixturesData(?string $file): ?string
    {
        if(empty($file))
        {
            return null;
        }
        
        return file_get_contents(self::getFixturesFile($file));
    }
}