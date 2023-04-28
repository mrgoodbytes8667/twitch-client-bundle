<?php

namespace Bytes\TwitchClientBundle\Tests\Objects;

use Bytes\Common\Faker\TestFakerTrait;
use Bytes\TwitchClientBundle\Objects\TwitchImageResize;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * Class TwitchImageResizeTest
 * @package Bytes\TwitchClientBundle\Tests\Objects
 */
class TwitchImageResizeTest extends TestCase
{
    use TestFakerTrait;

    /**
     * @dataProvider provideFakeImageTests
     * @param string $url
     * @param int|string $width
     * @param int|string $height
     * @param string $combined
     * @return void
     */
    public function testFakeImageTest($url, $width, $height, $combined)
    {
        self::assertStringContainsString($width, $url);
        self::assertStringContainsString($height, $url);
        self::assertStringContainsString($combined, $url);
    }

    public function provideFakeImageTests(): Generator
    {
        $this->setupFaker();
        yield 'fakerImageUrl' => [ 'url' => $this->faker->imageUrl(width: 123, height: 456), 'width' => 123, 'height' => 456, 'combined' => '123/456' ];
        yield 'getFakeImage' => [ 'url' => $this->getFakeImage(), 'width' => '{width}', 'height' => '{height}', 'combined' => '{width}/{height}' ];
    }

    /**
     *
     */
    public function testSixteenByNine()
    {
        $this->assertStringContainsString('480/270', TwitchImageResize::sixteenByNine($this->getFakeImage()));
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
        $this->assertStringContainsString('480/270', TwitchImageResize::resize($this->getFakeImage(), 480, 270));
        $this->assertEmpty(TwitchImageResize::resize('', 0, 0));
    }

    /**
     *
     */
    public function testThumbnail()
    {
        $this->assertStringContainsString('50/50', TwitchImageResize::thumbnail($this->getFakeImage()));
        $this->assertEmpty(TwitchImageResize::thumbnail(''));
    }

    /**
     *
     */
    public function testTwitchGameThumbnail()
    {
        $this->assertStringContainsString('85/113', TwitchImageResize::twitchGameThumbnail($this->getFakeImage()));
        $this->assertEmpty(TwitchImageResize::twitchGameThumbnail(''));
    }
}