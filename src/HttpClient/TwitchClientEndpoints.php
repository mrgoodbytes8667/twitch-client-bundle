<?php


namespace Bytes\TwitchClientBundle\HttpClient;


/**
 * Interface TwitchClientEndpoints
 * @package Bytes\TwitchClientBundle\HttpClient
 */
interface TwitchClientEndpoints
{
    /**
     * Matches OAuth API token revoke route
     * @var string
     */
    const SCOPE_OAUTH_TOKEN = 'https://id\.twitch\.tv/oauth2/token';

    /**
     * Matches OAuth API token revoke route
     * @var string
     */
    const SCOPE_OAUTH_TOKEN_REVOKE = 'https://id\.twitch\.tv/oauth2/revoke';

    /**
     * Matches OAuth API token revoke route
     * @var string
     */
    const SCOPE_OAUTH_VALIDATE = 'https://id\.twitch\.tv/oauth2/validate';

    /**
     * Matches remaining OAuth API routes
     * @var string
     */
    const SCOPE_OAUTH = 'https://id\.twitch\.tv/oauth2';

    /**
     * Matches Kraken API routes
     * @var string
     */
    const SCOPE_KRAKEN = 'https://api\.twitch\.tv/kraken';

    /**
     * Matches Helix API routes
     * @var string
     */
    const SCOPE_HELIX = 'https://api\.twitch\.tv/helix';

    /**
     * Base URL for all Twitch OAuth calls
     * @var string
     */
    const ENDPOINT_TWITCH_OAUTH = 'https://id.twitch.tv/';

    /**
     * Base URL for all Twitch API calls
     * @var string
     */
    const ENDPOINT_TWITCH_API = 'https://api.twitch.tv/';
}