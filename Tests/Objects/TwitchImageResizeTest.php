<?php

namespace Bytes\TwitchClientBundle\Tests\Objects;

use Bytes\Common\Faker\TestFakerTrait;
use Bytes\TwitchClientBundle\Objects\TwitchImageResize;
use PHPUnit\Framework\TestCase;

/**
 * Class TwitchImageResizeTest
 * @package Bytes\TwitchClientBundle\Tests\Objects
 */
class TwitchImageResizeTest extends TestCase
{
    use TestFakerTrait;

    /**
     *
     */
    public function testSixteenByNine()
    {
        $this->assertStringContainsString('480x270', TwitchImageResize::sixteenByNine($this->getFakeImage()));
        $this->assertEmpty(TwitchImageResize::sixteenByNine(''));
    }

    /**
     * @return array|string|string[]
     */
    private function getFakeImage()
    {
        return str_replace('456', '{height}', str_replace('123', '{width}', $this->faker->imageUrl(width: 123, height: 456) ?? ''));
    }

    /**
     *
     */
    public function testResize()
    {
        $this->assertStringContainsString('480x270', TwitchImageResize::resize($this->getFakeImage(), 480, 270));
        $this->assertEmpty(TwitchImageResize::resize('', 0, 0));
    }

    /**
     *
     */
    public function testThumbnail()
    {
        $this->assertStringContainsString('50x50', TwitchImageResize::thumbnail($this->getFakeImage()));
        $this->assertEmpty(TwitchImageResize::thumbnail(''));
    }

    /**
     *
     */
    public function testTwitchGameThumbnail()
    {
        $this->assertStringContainsString('85x113', TwitchImageResize::twitchGameThumbnail($this->getFakeImage()));
        $this->assertEmpty(TwitchImageResize::twitchGameThumbnail(''));
    }
}