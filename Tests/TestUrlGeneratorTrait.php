<?php

namespace Bytes\TwitchClientBundle\Tests;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Trait TestUrlGeneratorTrait
 * @package Bytes\TwitchClientBundle\Tests
 *
 * @method MockBuilder getMockBuilder(string $className)
 */
trait TestUrlGeneratorTrait
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @before
     * @return UrlGeneratorInterface
     */
    public function createUrlGenerator()
    {
        $urlGenerator = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();

        $urlGenerator->method('generate')
            ->willReturn('https://www.example.com');

        return $this->urlGenerator = $urlGenerator;
    }

    /**
     * @after
     */
    public function tearDownUrlGenerator()
    {
        $this->urlGenerator = null;
    }
}