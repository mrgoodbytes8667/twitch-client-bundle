<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\TwitchResponseBundle\Exceptions\UnknownObjectException;
use Bytes\TwitchResponseBundle\Objects\Channel;
use Bytes\TwitchResponseBundle\Objects\Embed\Embed;
use Bytes\TwitchResponseBundle\Objects\Guild;
use Bytes\TwitchResponseBundle\Objects\Interfaces\ChannelIdInterface;
use Bytes\TwitchResponseBundle\Objects\Interfaces\GuildIdInterface;
use Bytes\TwitchResponseBundle\Objects\Interfaces\IdInterface;
use Bytes\TwitchResponseBundle\Objects\Member;
use Bytes\TwitchResponseBundle\Objects\Message;
use Bytes\TwitchResponseBundle\Objects\Message\AllowedMentions;
use Bytes\TwitchResponseBundle\Objects\Message\Content;
use Bytes\TwitchResponseBundle\Objects\Message\WebhookContent;
use Bytes\TwitchResponseBundle\Objects\Role;
use Bytes\TwitchResponseBundle\Objects\Slash\ApplicationCommand;
use Bytes\TwitchResponseBundle\Services\IdNormalizer;
use Bytes\ResponseBundle\Enums\HttpMethods;
use Illuminate\Support\Arr;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class TwitchBotClient
 * @package Bytes\TwitchClientBundle\HttpClient
 */
class TwitchBotClient extends TwitchClient
{
    /**
     * TwitchBotClient constructor.
     * @param HttpClientInterface $httpClient
     * @param RetryStrategyInterface|null $strategy
     * @param string $clientId
     * @param string $clientSecret
     * @param string $botToken
     * @param string|null $userAgent
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     */
    public function __construct(HttpClientInterface $httpClient, ?RetryStrategyInterface $strategy, string $clientId, string $clientSecret, string $botToken, ?string $userAgent, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $defaultOptionsByRegexp = array_merge_recursive([
            // Matches non-oauth API routes
            TwitchClient::SCOPE_API => [
                'headers' => [
                    'Authorization' => 'Bot ' . $botToken,
                ],
            ],
        ], $defaultOptionsByRegexp);
        parent::__construct($httpClient, $strategy, $clientId, $clientSecret, $botToken, $userAgent, $defaultOptionsByRegexp, $defaultRegexp);
    }

    /**
     * Create/Edit [Global] Application Command
     * Create:
     * Creating a command with the same name as an existing command for your application will overwrite the old command.
     *
     * [Global] Create a new global command. New global commands will be available in all guilds after 1 hour. Returns
     * 201 and an ApplicationCommand object.
     * [Guild] Create a new guild command. New guild commands will be available in the guild immediately. Returns 201
     * and an ApplicationCommand object.
     *
     * Edit:
     * All parameters for this endpoint are optional. options is nullable.
     *
     * [Global] Edit a global command. Updates will be available in all guilds after 1 hour. Returns 200 and an
     * ApplicationCommand object.
     * [Guild] Edit a guild command. Updates for guild commands will be available immediately. Returns 200 and an
     * ApplicationCommand object.
     *
     * Not deserializable
     * @param ApplicationCommand|callable $applicationCommand
     * @param GuildIdInterface|IdInterface|string|null $guild Guild id to create command in. Must be a string, a GuildIdInterface object (returns `getGuildId()`), an IdInterface object (return `getId()`), or null for a global command.
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#create-global-application-command
     * @link https://twitch.com/developers/docs/interactions/slash-commands#edit-global-application-command
     * @link https://twitch.com/developers/docs/interactions/slash-commands#create-guild-application-command
     * @link https://twitch.com/developers/docs/interactions/slash-commands#edit-guild-application-command
     */
    public function createCommand(ApplicationCommand|callable $applicationCommand, $guild = null): TwitchResponse
    {
        if(is_callable($applicationCommand)) {
            $applicationCommand = $applicationCommand();
        }
        $method = HttpMethods::post();
        $append = [];
        $errors = $this->validator->validate($applicationCommand);
        if (count($errors) > 0) {
            throw new ValidatorException((string)$errors);
        }

        if (!empty($applicationCommand->getId())) {
            $method = HttpMethods::patch();
            $append[] = $applicationCommand->getId();
        }

        return $this->createEditOverwriteCommands($applicationCommand, $guild, $method, ApplicationCommand::class, $append);
    }

    /**
     * Bulk Overwrite Global/Guild Application Commands
     * [Global] Takes a list of application commands, overwriting existing commands that are registered globally for
     * this application. Updates will be available in all guilds after 1 hour. Returns 200 and a list of
     * ApplicationCommand objects. Commands that do not already exist will count toward daily application command
     * create limits.
     * [Guild] Takes a list of application commands, overwriting existing commands for the guild. Returns 200 and a list
     * of ApplicationCommand objects.
     *
     * @param ApplicationCommand[]|ApplicationCommand $applicationCommands
     * @param GuildIdInterface|IdInterface|string|null $guild Guild id to overwrite commands in. Must be a string, a
     * GuildIdInterface object (returns `getGuildId()`), an IdInterface object (return `getId()`), or null for a global
     * command.
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#bulk-overwrite-global-application-commands
     * @link
     */
    public function bulkOverwriteCommands($applicationCommands, $guild = null)
    {
        foreach (Arr::wrap($applicationCommands) as $applicationCommand) {
            $errors = $this->validator->validate($applicationCommand);
            if (count($errors) > 0) {
                throw new ValidatorException((string)$errors);
            }
        }
        return $this->createEditOverwriteCommands($applicationCommands, $guild, HttpMethods::put(), '\Bytes\TwitchResponseBundle\Objects\Slash\ApplicationCommand[]');
    }

    /**
     * @param ApplicationCommand[]|ApplicationCommand $applicationCommand
     * @param GuildIdInterface|IdInterface|string|null $guild Guild id to delete command in. Must be a string, a GuildIdInterface object (returns `getGuildId()`), an IdInterface object (return `getId()`), or null for a global command.
     * @param HttpMethods $method
     * @param string $class
     * @param array $urlAppend
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     */
    protected function createEditOverwriteCommands($applicationCommand, $guild, HttpMethods $method, string $class, $urlAppend = [])
    {
        $urlParts = ['applications', $this->clientId];
        if (!empty($guild)) {
            $urlParts[] = self::ENDPOINT_GUILD;
            $guild = IdNormalizer::normalizeGuildIdArgument($guild, 'The "guildId" argument must be a string, must implement GuildIdInterface/IdInterface, or be null.');
            $urlParts[] = $guild;
        }
        $urlParts[] = 'commands';
        if(!empty($urlAppend)) {
            $urlParts = array_merge($urlParts, Arr::wrap($urlAppend));
        }

        $body = $this->serializer->serialize($applicationCommand, 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);

        return $this->request(url: $urlParts,
            type: $class,
            options: [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $body,
            ], method: $method);
    }

    /**
     * Delete [Global] Application Command
     * Deletes a global/guild command. Returns 204.
     *
     * Not deserializable
     * @param ApplicationCommand|IdInterface|string $applicationCommand
     * @param GuildIdInterface|IdInterface|string|null $guild Guild id to delete command in. Must be a string, a GuildIdInterface object (returns `getGuildId()`), an IdInterface object (return `getId()`), or null for a global command.
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#delete-global-application-command
     * @link https://twitch.com/developers/docs/interactions/slash-commands#delete-guild-application-command
     */
    public function deleteCommand($applicationCommand, $guild = null)
    {
        $commandId = IdNormalizer::normalizeIdArgument($applicationCommand, 'The "applicationCommand" argument is required and cannot be blank.');
        $urlParts = ['applications', $this->clientId];

        if (!empty($guild)) {
            $urlParts[] = self::ENDPOINT_GUILD;
            $guild = IdNormalizer::normalizeGuildIdArgument($guild, 'The "guildId" argument must be a string, must implement GuildIdInterface/IdInterface, or be null.');
            $urlParts[] = $guild;
        }
        $urlParts[] = 'commands';
        $urlParts[] = $commandId;

        return $this->request(url: $urlParts, method: HttpMethods::delete());
    }

    /**
     * Helper function that utilizes the Bulk Overwrite endpoint to remove all commands.
     * @param GuildIdInterface|IdInterface|string|null $guild Guild id to delete commands for. Must be a string, a GuildIdInterface object (returns `getGuildId()`), an IdInterface object (return `getId()`), or null for a global command.
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     */
    public function deleteAllCommands($guild = null): TwitchResponse
    {
        return $this->bulkOverwriteCommands([], $guild);
    }

    /**
     * Get Global/Guild Application Commands
     * Fetch all of the guild/global commands for your application [for a specific guild]. Returns an array of
     * ApplicationCommand objects.
     * @param GuildIdInterface|IdInterface|string|null $guild Guild id to get commands for. Must be a string, a GuildIdInterface object (returns `getGuildId()`), an IdInterface object (return `getId()`), or null for a global command.
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#get-global-application-commands
     * @link https://twitch.com/developers/docs/interactions/slash-commands#get-guild-application-commands
     */
    public function getCommands($guild = null): TwitchResponse
    {
        $urlParts = ['applications', $this->clientId];

        if (!empty($guild)) {
            $urlParts[] = self::ENDPOINT_GUILD;
            $guild = IdNormalizer::normalizeGuildIdArgument($guild, 'The "guildId" argument must be a string, must implement GuildIdInterface/IdInterface, or be null.');
            $urlParts[] = $guild;
        }
        $urlParts[] = 'commands';

        return $this->request($urlParts, 'Bytes\TwitchResponseBundle\Objects\Slash\ApplicationCommand[]');
    }

    /**
     * Get Global/Guild Application Command
     * Fetch a global/guild command for your application. Returns an ApplicationCommand object.
     * @param ApplicationCommand|IdInterface|string $applicationCommand
     * @param GuildIdInterface|IdInterface|string|null $guild Guild id to get command for. Must be a string, a GuildIdInterface object (returns `getGuildId()`), an IdInterface object (return `getId()`), or null for a global command.
     * @return TwitchResponse
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#get-global-application-command
     * @link https://twitch.com/developers/docs/interactions/slash-commands#get-guild-application-command
     */
    public function getCommand($applicationCommand, $guild = null): TwitchResponse
    {
        $commandId = IdNormalizer::normalizeIdArgument($applicationCommand, 'The "applicationCommand" argument is required and cannot be blank.');
        $urlParts = ['applications', $this->clientId];

        if (!empty($guild)) {
            $urlParts[] = self::ENDPOINT_GUILD;
            $guild = IdNormalizer::normalizeGuildIdArgument($guild, 'The "guildId" argument must be a string, must implement GuildIdInterface/IdInterface, or be null.');
            $urlParts[] = $guild;
        }
        $urlParts[] = 'commands';
        $urlParts[] = $commandId;

        return $this->request($urlParts, ApplicationCommand::class);
    }

    /**
     * Get Guild
     * Returns the guild object for the given id. If with_counts is set to true, this endpoint will also return
     * approximate_member_count and approximate_presence_count for the guild.
     * @param GuildIdInterface|IdInterface|string $guild
     * @param bool $withCounts
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/guild#get-guild
     */
    public function getGuild($guild, bool $withCounts = false): TwitchResponse
    {
        $id = IdNormalizer::normalizeGuildIdArgument($guild, 'The "guildId" argument is required and cannot be blank.');
        $url = $this->buildURL(implode('/', [self::ENDPOINT_GUILD, $id]), 'v8');
        return $this->request($url, Guild::class, [
            'query' => [
                'with_counts' => $withCounts
            ]
        ]);
    }

    /**
     * Get User
     * Returns a user object for a given user ID.
     * @param IdInterface|string $userId
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/user#get-user
     */
    public function getUser($userId): TwitchResponse
    {
        return parent::getUser($userId);
    }

    /**
     * Get Guild Channels
     * Returns a list of guild channel objects.
     * @param GuildIdInterface|IdInterface|string $guildId
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/guild#get-guild-channels
     */
    public function getChannels($guildId): TwitchResponse
    {
        $id = IdNormalizer::normalizeGuildIdArgument($guildId, 'The "guildId" argument is required and cannot be blank.');
        return $this->request([self::ENDPOINT_GUILD, $id, self::ENDPOINT_CHANNEL], '\Bytes\TwitchResponseBundle\Objects\Channel[]');
    }

    /**
     * Get Channel
     * Get a channel by ID. Returns a channel object.
     * @param IdInterface|string $channelId
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     */
    public function getChannel($channelId): TwitchResponse
    {
        $id = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
        return $this->request([self::ENDPOINT_CHANNEL, $id], Channel::class);
    }

    /**
     * Get Channel Message
     * Returns a specific message in the channel. If operating on a guild channel, this endpoint requires the
     * 'READ_MESSAGE_HISTORY' permission to be present on the current user. Returns a message object on success.
     * @param Message|IdInterface|string $messageId
     * @param ChannelIdInterface|IdInterface|string $channelId Optional if $messageId is a Message object
     *
     * @return TwitchResponse
     *
     * @throws UnknownObjectException
     * @throws TransportExceptionInterface
     */
    public function getChannelMessage($messageId, $channelId = null): TwitchResponse
    {
        // If a Message object is passed through, get the message and channel Id from it
        if ($messageId instanceof Message) {
            $ids = IdNormalizer::normalizeMessageIntoIds($messageId, 'The "channelId" argument is required and cannot be blank.', 'The "messageId" argument is required and cannot be blank.');
            $channelId = $ids['channelId'];
            $messageId = $ids['messageId'];
        } else {
            $channelId = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
            $messageId = IdNormalizer::normalizeIdArgument($messageId, 'The "messageId" argument is required and cannot be blank.');
        }
        return $this->request([self::ENDPOINT_CHANNEL, $channelId, self::ENDPOINT_MESSAGE, $messageId], Message::class);
    }

    /**
     * Get Channel Messages
     * Returns the messages for a channel. If operating on a guild channel, this endpoint requires the VIEW_CHANNEL
     * permission to be present on the current user. If the current user is missing the 'READ_MESSAGE_HISTORY'
     * permission in the channel then this will return no messages (since they cannot read the message history).
     * Returns an array of message objects on success.
     * @param IdInterface|string $channelId
     * @param string|null $filter = ['around','before','after'][$any]
     * @param IdInterface|string|null $messageId
     * @param int|null $limit 1 - 100
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/channel#get-channel-messages
     */
    public function getChannelMessages($channelId, ?string $filter = null, $messageId = null, ?int $limit = 50): TwitchResponse
    {
        $channelId = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
        $limit = self::normalizeLimit($limit, 50);
        $query['limit'] = $limit;

        if (!empty($filter)) {
            $messageId = IdNormalizer::normalizeIdArgument($messageId, '', true);
            if (!empty($messageId)) {
                switch (strtolower($filter)) {
                    case 'around':
                    case 'before':
                    case 'after':
                        $query[$filter] = $messageId;
                        break;
                }
            }
        }

        return $this->request([self::ENDPOINT_CHANNEL, $channelId, self::ENDPOINT_MESSAGE],
            '\Bytes\TwitchResponseBundle\Objects\Message[]', [
            'query' => $query
        ]);
    }

    /**
     * Create Message
     * Before using this endpoint, you must connect to and identify with a gateway at least once.
     * Twitch may strip certain characters from message content, like invalid unicode characters or characters which
     * cause unexpected message formatting. If you are passing user-generated strings into message content, consider
     * sanitizing the data to prevent unexpected behavior and utilizing allowed_mentions to prevent unexpected mentions.
     * Post a message to a guild text or DM channel. If operating on a guild channel, this endpoint requires the
     * SEND_MESSAGES permission to be present on the current user. If the tts field is set to true, the
     * SEND_TTS_MESSAGES permission is required for the message to be spoken. Returns a message object. Fires a Message
     * Create Gateway event. See message formatting for more information on how to properly format messages.
     * The maximum request size when sending a message is 8MB.
     * This endpoint supports requests with Content-Types of both application/json and multipart/form-data. You must
     * however use multipart/form-data when uploading files. Note that when sending multipart/form-data requests the
     * embed field cannot be used, however you can pass a JSON encoded body as form value for payload_json, where
     * additional request parameters such as embed can be set.
     * Note that when sending application/json you must send at least one of content or embed, and when sending
     * multipart/form-data, you must send at least one of content, embed or file. For a file attachment, the
     * Content-Disposition subpart header MUST contain a filename parameter.
     * @param ChannelIdInterface|IdInterface|string $channelId
     * @param Content|Embed|string|array $content the message contents (up to 2000 characters), an array of content, or an Embed
     * @param bool $tts true if this is a TTS message
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     */
    public function createMessage($channelId, $content, bool $tts = false): TwitchResponse
    {
        return $this->sendMessage($channelId, null, $content, $tts, HttpMethods::post());
    }

    /**
     * @param ChannelIdInterface|IdInterface|string $channelId
     * @param IdInterface|string|null $messageId
     * @param Content|Embed|string|array $content the message contents (up to 2000 characters), an array of content, or an Embed
     * @param bool $tts true if this is a TTS message
     * @param HttpMethods $method
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     * @internal
     */
    protected function sendMessage($channelId, $messageId, $content, bool $tts, HttpMethods $method): TwitchResponse
    {
        $channelId = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
        $messageId = IdNormalizer::normalizeIdArgument($messageId, '', true);

        $urlParts = [self::ENDPOINT_CHANNEL, $channelId, self::ENDPOINT_MESSAGE];
        if (!empty($messageId)) {
            $urlParts[] = $messageId;
        }

        if (!($content instanceof Content)) {
            $data = new Content();

            if (is_string($content) && !empty($content)) {
                $data->setContent($content);
            }

            if (!is_string($content) && !is_array($content)) {
                $data->setEmbed($content);
            }

            $data->setTts($tts);
        } else {
            $data = $content;
        }

        $body = $this->serializer->serialize($data, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        return $this->request($urlParts, Message::class, [
            'body' => $body
        ], $method);
    }

    /**
     * Edit Message
     * Edit a previously sent message. The fields content, embed, allowed_mentions and flags can be edited by the
     * original message author. Other users can only edit flags and only if they have the MANAGE_MESSAGES permission in
     * the corresponding channel. When specifying flags, ensure to include all previously set flags/bits in addition to
     * ones that you are modifying. Only flags documented in the table below may be modified by users (unsupported flag
     * changes are currently ignored without error).
     * Returns a message object. Fires a Message Update Gateway event.
     * All parameters to this endpoint are optional and nullable.
     * @param ChannelIdInterface|IdInterface|string $channelId
     * @param IdInterface|string $messageId
     * @param Content|Embed|string|array $content the message contents (up to 2000 characters), an array of content, or an Embed
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     */
    public function editMessage($channelId, $messageId, $content): TwitchResponse
    {
        return $this->sendMessage($channelId, $messageId, $content, false, HttpMethods::patch());
    }

    /**
     * Delete Message
     * Delete a message. If operating on a guild channel and trying to delete a message that was not sent by the current
     * user, this endpoint requires the MANAGE_MESSAGES permission. Returns a 204 empty response on success. Fires a
     * Message Delete Gateway event.
     *
     * Not deserializable
     * @param Message|IdInterface|string $messageId
     * @param ChannelIdInterface|IdInterface|string $channelId Optional if $messageId is a Message object
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/channel#delete-message
     */
    public function deleteMessage($messageId, $channelId = null): TwitchResponse
    {
        // If a Message object is passed through, get the message and channel Id from it
        if ($messageId instanceof Message) {
            $ids = IdNormalizer::normalizeMessageIntoIds($messageId, 'The "channelId" argument is required and cannot be blank.', 'The "messageId" argument is required and cannot be blank.');
            $channelId = $ids['channelId'];
            $messageId = $ids['messageId'];
        } else {
            $channelId = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
            $messageId = IdNormalizer::normalizeIdArgument($messageId, 'The "messageId" argument is required and cannot be blank.');
        }
        return $this->request(url: [self::ENDPOINT_CHANNEL, $channelId, self::ENDPOINT_MESSAGE, $messageId], method: HttpMethods::delete());
    }

    /**
     * Crosspost Message
     * Crosspost a message in a News Channel to following channels. This endpoint requires the 'SEND_MESSAGES'
     * permission, if the current user sent the message, or additionally the 'MANAGE_MESSAGES' permission, for all other
     * messages, to be present for the current user.
     * @param Message|IdInterface|string $messageId
     * @param ChannelIdInterface|IdInterface|string $channelId Optional if $messageId is a Message object
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/channel#crosspost-message
     */
    public function crosspostMessage($messageId, $channelId = null): TwitchResponse
    {
        // If a Message object is passed through, get the message and channel Id from it
        if ($messageId instanceof Message) {
            $ids = IdNormalizer::normalizeMessageIntoIds($messageId, 'The "channelId" argument is required and cannot be blank.', 'The "messageId" argument is required and cannot be blank.');
            $channelId = $ids['channelId'];
            $messageId = $ids['messageId'];
        } else {
            $channelId = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
            $messageId = IdNormalizer::normalizeIdArgument($messageId, 'The "messageId" argument is required and cannot be blank.');
        }
        return $this->request(url: [self::ENDPOINT_CHANNEL, $channelId, self::ENDPOINT_MESSAGE, $messageId, 'crosspost'], type: Message::class, method: HttpMethods::post());
    }

    /**
     * Leave Guild
     * Leave a guild. Returns a 204 empty response on success.
     *
     * Not deserializable
     * @param GuildIdInterface|IdInterface|string $guildId
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/user#leave-guild
     */
    public function leaveGuild($guildId): TwitchResponse
    {
        $id = IdNormalizer::normalizeGuildIdArgument($guildId, 'The "guildId" argument is required and cannot be blank.');
        return $this->request(url: [
            static::ENDPOINT_USER,
            static::USER_ME,
            static::ENDPOINT_GUILD,
            $id,
        ], method: HttpMethods::delete());
    }

    /**
     * Get Guild Member
     * Returns a guild member object for the specified user.
     * @param GuildIdInterface|IdInterface|string $guildId
     * @param IdInterface|string $userId
     *
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/guild#get-guild-member
     */
    public function getGuildMember($guildId, $userId): TwitchResponse
    {
        $guildId = IdNormalizer::normalizeGuildIdArgument($guildId, 'The "guildId" argument is required and cannot be blank.');
        $userId = IdNormalizer::normalizeIdArgument($userId, 'The "userId" argument is required and cannot be blank.');
        return $this->request([
            static::ENDPOINT_GUILD,
            $guildId,
            static::ENDPOINT_MEMBER,
            $userId
        ], Member::class);
    }

    /**
     * Get Guild Roles
     * Returns a list of role objects for the guild.
     * @param GuildIdInterface|IdInterface|string $guildId
     *
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/guild#get-guild-roles
     */
    public function getGuildRoles($guildId): TwitchResponse
    {
        $guildId = IdNormalizer::normalizeGuildIdArgument($guildId, 'The "guildId" argument is required and cannot be blank.');
        return $this->request([
            self::ENDPOINT_GUILD,
            $guildId,
            'roles'
        ], '\Bytes\TwitchResponseBundle\Objects\Role[]');
    }

    /**
     * Create Guild Role
     * Create a new role for the guild. Requires the MANAGE_ROLES permission. Returns the new role object on success. Fires a Guild Role Create Gateway event. All JSON params are optional.
     * @param GuildIdInterface|IdInterface|string $guildId
     * @param string|null $name name of the role, "new role" if left blank
     * @param int|null $permissions bitwise value of the enabled/disabled permissions, defaults to `@everyone` permissions
     * @param int|null $color RGB color value
     * @param bool|null $hoist whether the role should be displayed separately in the sidebar
     * @param bool|null $mentionable whether the role should be mentionable
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/guild#create-guild-role
     */
    public function createGuildRole($guildId, ?string $name = null, ?int $permissions = null, ?int $color = null, ?bool $hoist = null, ?bool $mentionable = null): TwitchResponse
    {
        $guildId = IdNormalizer::normalizeGuildIdArgument($guildId, 'The "guildId" argument is required and cannot be blank.');
        $errors = $this->validator->validate($name, new Assert\AtLeastOneOf([
            'constraints' => [
                new Assert\Blank(),
                new Assert\Length([
                    'min' => 1,
                    'max' => 100,
                    'minMessage' => "Name must be at least {{ limit }} characters long",
                    'maxMessage' => "Name cannot be longer than {{ limit }} characters",
                ]),
            ],
        ]));
        if (count($errors) > 0) {
            throw new ValidatorException((string)$errors);
        }
        $options = [];
        if (!empty($name)) {
            $options['name'] = $name;
        }
        if (!is_null($permissions)) {
            $options['permissions'] = $permissions;
        }
        if (!is_null($color)) {
            $options['color'] = $color;
        }
        if (!is_null($hoist)) {
            $options['hoist'] = $hoist;
        }
        if (!is_null($mentionable)) {
            $options['mentionable'] = $mentionable;
        }
        return $this->request([
            self::ENDPOINT_GUILD,
            $guildId,
            'roles'
        ], Role::class, [
            'json' => $options
        ], HttpMethods::post());
    }

    /**
     * Create Reaction
     * Create a reaction for the message. This endpoint requires the 'READ_MESSAGE_HISTORY' permission to be present on
     * the current user. Additionally, if nobody else has reacted to the message using this emoji, this endpoint
     * requires the 'ADD_REACTIONS' permission to be present on the current user. Returns a 204 empty response on
     * success. The emoji must be URL Encoded or the request will fail with 10014: Unknown Emoji.
     *
     * Not deserializable
     * @param Message|IdInterface|string $messageId
     * @param string $emoji
     * @param ChannelIdInterface|IdInterface|string $channelId Optional if $messageId is a Message object
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/resources/channel#create-reaction
     */
    public function createReaction($messageId, string $emoji, $channelId = null): TwitchResponse
    {
        // If a Message object is passed through, get the message and channel Id from it
        if ($messageId instanceof Message) {
            $ids = IdNormalizer::normalizeMessageIntoIds($messageId, 'The "channelId" argument is required and cannot be blank.', 'The "messageId" argument is required and cannot be blank.');
            $channelId = $ids['channelId'];
            $messageId = $ids['messageId'];
        } else {
            $channelId = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
            $messageId = IdNormalizer::normalizeIdArgument($messageId, 'The "messageId" argument is required and cannot be blank.');
        }
        return $this->request(url: [
            static::ENDPOINT_CHANNEL,
            $channelId,
            static::ENDPOINT_MESSAGE,
            $messageId,
            'reactions',
            urlencode($emoji),
            static::USER_ME
        ], options: [
            'headers' => [
                'Content-Length' => 0,
            ]
        ], method: HttpMethods::put());
    }

    /**
     * Get Reactions
     * Get a list of users that reacted with this emoji. Returns an array of user objects on success. The emoji must be
     * URL Encoded or the request will fail with 10014: Unknown Emoji.
     * @param Message|IdInterface|string $messageId
     * @param string $emoji
     * @param null $channelId Optional if $messageId is a Message object
     * @param string|null $before
     * @param string|null $after
     * @param int|null $limit
     *
     * @return TwitchResponse
     *
     * @throws TransportExceptionInterface
     */
    public function getReactions($messageId, string $emoji, $channelId = null, ?string $before = null, ?string $after = null, ?int $limit = 25): TwitchResponse
    {
        // If a Message object is passed through, get the message and channel Id from it
        if ($messageId instanceof Message) {
            $ids = IdNormalizer::normalizeMessageIntoIds($messageId, 'The "channelId" argument is required and cannot be blank.', 'The "messageId" argument is required and cannot be blank.');
            $channelId = $ids['channelId'];
            $messageId = $ids['messageId'];
        } else {
            $channelId = IdNormalizer::normalizeChannelIdArgument($channelId, 'The "channelId" argument is required and cannot be blank.');
            $messageId = IdNormalizer::normalizeIdArgument($messageId, 'The "messageId" argument is required and cannot be blank.');
        }
        $query = [
            'limit' => self::normalizeLimit($limit, 25)
        ];
        if (!empty($before)) {
            $query['before'] = $before;
        }
        if (!empty($after)) {
            $query['after'] = $after;
        }
        return $this->request([
            static::ENDPOINT_CHANNEL,
            $channelId,
            static::ENDPOINT_MESSAGE,
            $messageId,
            'reactions',
            urlencode($emoji)
        ], '\Bytes\TwitchResponseBundle\Objects\User[]', [
            'query' => $query
        ]);
    }

    /**
     * @param int|null $limit
     * @param int $default
     * @param int $max
     * @return int|null
     */
    protected static function normalizeLimit(?int $limit, int $default, int $max = 100)
    {
        if (empty($limit)) {
            $limit = $default;
        }
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > $max) {
            $limit = $max;
        }
        return $limit;
    }

    /**
     * Edit Original Interaction Response
     * Edits the initial Interaction response. Functions the same as Edit Webhook Message.
     * @param string $token Interaction token
     * @param WebhookContent|string $content the message contents (up to 2000 characters)
     * @param Embed[]|Embed|null $embeds
     * @param AllowedMentions|null $allowedMentions
     * @param bool|null $tts true if this is a TTS message
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#edit-original-interaction-response
     */
    public function editOriginalInteractionResponse(string $token, $content = null, $embeds = [], ?AllowedMentions $allowedMentions = null, ?bool $tts = null): TwitchResponse
    {
        return $this->editWebhookMessage(id: $this->clientId, token: $token, messageId: '@original', content: $content, embeds: $embeds, allowedMentions: $allowedMentions, tts: $tts);
    }

    /**
     * Delete Original Interaction Response
     * Deletes the initial Interaction response. Returns 204 on success.
     * @param string $token Interaction token
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#delete-original-interaction-response
     */
    public function deleteOriginalInteractionResponse(string $token)
    {
        return $this->deleteWebhookMessage(id: $this->clientId, token: $token, messageId: '@original');
    }

    /**
     * Create Followup Message
     * Create a followup message for an Interaction. Functions the same as Execute Webhook, but wait is always true.
     * @param string $token Interaction token
     * @param WebhookContent|string $content the message contents (up to 2000 characters)
     * @param Embed[]|Embed|null $embeds
     * @param AllowedMentions|null $allowedMentions
     * @param bool|null $tts
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#create-followup-message
     */
    public function createFollowupMessage(string $token, $content = null, $embeds = [], ?AllowedMentions $allowedMentions = null, ?bool $tts = null): TwitchResponse
    {
        return $this->executeWebhook(id: $this->clientId, token: $token, wait: true, content: $content, embeds: $embeds, allowedMentions: $allowedMentions, tts: $tts);
    }

    /**
     * Edit Followup Message
     * Edits a followup message for an Interaction. Functions the same as Edit Webhook Message.
     * @param string $token Interaction token
     * @param IdInterface|string $messageId Message Id to edit
     * @param WebhookContent|string $content the message contents (up to 2000 characters)
     * @param Embed[]|Embed|null $embeds
     * @param AllowedMentions|null $allowedMentions
     * @param bool|null $tts true if this is a TTS message
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#edit-followup-message
     */
    public function editFollowupMessage(string $token, $messageId, $content = null, $embeds = [], ?AllowedMentions $allowedMentions = null, ?bool $tts = null): TwitchResponse
    {
        return $this->editWebhookMessage(id: $this->clientId, token: $token, messageId: $messageId, content: $content, embeds: $embeds, allowedMentions: $allowedMentions, tts: $tts);
    }

    /**
     * Delete Followup Message
     * Deletes a followup message for an Interaction. Returns 204 on success.
     * @param string $token Interaction token
     * @param IdInterface|string $messageId Message Id to delete
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     *
     * @link https://twitch.com/developers/docs/interactions/slash-commands#delete-followup-message
     */
    public function deleteFollowupMessage(string $token, $messageId)
    {
        return $this->deleteWebhookMessage(id: $this->clientId, token: $token, messageId: $messageId);
    }


}