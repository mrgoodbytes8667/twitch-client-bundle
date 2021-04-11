<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\HttpClient\TwitchResponse;
use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchResponseBundle\Objects\Slash\ApplicationCommand;
use Bytes\TwitchResponseBundle\Objects\Slash\ApplicationCommandOption;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class CreateCommandTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class CreateCommandTest extends TestTwitchBotClientCase
{
    /**
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testCreateCommand()
    {
        $command = $this
            ->setupResponse('HttpClient/add-command-success.json', type: ApplicationCommand::class)
            ->deserialize();

        $this->validateCommand($command);
    }

    /**
     * @param $command
     */
    protected function validateCommand($command)
    {
        $this->assertInstanceOf(ApplicationCommand::class, $command);
        $this->assertEquals('846542216677566910', $command->getId());
        $this->assertEquals('sample', $command->getName());
        $this->assertEquals('Sample', $command->getDescription());
        $this->assertCount(1, $command->getOptions());
        $options = $command->getOptions();
        $option = array_shift($options);
        $this->assertInstanceOf(ApplicationCommandOption::class, $option);
        $this->assertCount(3, $option->getChoices());
    }
}


