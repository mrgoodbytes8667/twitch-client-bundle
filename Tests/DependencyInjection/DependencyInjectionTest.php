<?php


namespace Bytes\TwitchClientBundle\Tests\DependencyInjection;


use Bytes\ResponseBundle\Event\RevokeTokenEvent;
use Bytes\TwitchClientBundle\Tests\Kernel;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DependencyInjectionTest
 * @package Bytes\TwitchClientBundle\Tests\DependencyInjection
 */
class DependencyInjectionTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @var Kernel|null
     */
    protected $kernel;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @group Legacy
     */
    public function testEventListener()
    {
        $this->expectDeprecation('Since mrgoodbytes8667/twitch-response-bundle 0.3.0: This "%s" normalizer is deprecated');

        $kernel = $this->kernel;
        $kernel->boot();
        $container = $kernel->getContainer();
        $dispatcher = $container->get('event_dispatcher');

        $event = new RevokeTokenEvent(Token::createFromAccessToken('abc123'));
        $this->assertInstanceOf(RevokeTokenEvent::class, $dispatcher->dispatch($event));
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->kernel = new Kernel();
    }

    /**
     * This method is called after each test.
     */
    protected function tearDown(): void
    {
        if (is_null($this->fs)) {
            $this->fs = new Filesystem();
        }
        $this->fs->remove($this->kernel->getCacheDir());
        $this->kernel = null;
    }
}