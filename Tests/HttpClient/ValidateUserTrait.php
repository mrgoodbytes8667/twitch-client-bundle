<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient;


use Bytes\TwitchResponseBundle\Objects\User;

/**
 * Trait ValidateUserTrait
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 *
 * @method assertInstanceOf(string $expected, $actual, string $message = '')
 * @method assertEquals($expected, $actual, string $message = '')
 * @method assertNull($actual, string $message = '')
 */
trait ValidateUserTrait
{

    /**
     * @param $user
     * @param $id
     * @param $username
     * @param $avatar
     * @param $discriminator
     * @param $flags
     * @param $bot
     */
    protected function validateUser($user, $id, $username, $avatar, $discriminator, $flags, $bot = null)
    {
        if(!empty($user)) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertEquals($id, $user->getId());
            $this->assertEquals($username, $user->getUsername());
            $this->assertEquals($avatar, $user->getAvatar());
            $this->assertEquals($discriminator, $user->getDiscriminator());
            $this->assertEquals($flags, $user->getPublicFlags());
            $this->assertEquals($bot, $user->getBot());
        } else {
            $this->assertNull($id);
            $this->assertNull($username);
            $this->assertNull($avatar);
            $this->assertNull($discriminator);
            $this->assertNull($flags);
            $this->assertNull($bot);
        }
    }
}