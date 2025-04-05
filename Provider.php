<?php

namespace SocialiteProviders\Instagram;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Instagram OAuth2 Provider
 *
 * @see https://developers.facebook.com/docs/instagram-basic-display-api
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'INSTAGRAM';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * The user fields being requested.
     *
     * @var array<string>
     */
    protected $fields = [
        'id',
        'user_id',
        'username',
        'name',
        'account_type',
        'profile_picture_url',
        'followers_count',
        'follows_count',
        'media_count',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['instagram_business_basic'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.instagram.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function parseApprovedScopes($body)
    {
        $scopesRaw = Arr::get($body, 'permissions', null);

        if (! is_array($scopesRaw) && ! is_string($scopesRaw)) {
            return [];
        }

        if (is_array($scopesRaw)) {
            return $scopesRaw;
        }

        return explode($this->scopeSeparator, (string) Arr::get($body, 'permissions', ''));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $queryParameters = [
            'access_token' => $token,
            'fields'       => implode(',', $this->fields),
        ];

        if (! empty($this->clientSecret)) {
            $queryParameters['appsecret_proof'] = hash_hmac('sha256', $token, $this->clientSecret);
        }

        $response = $this->getHttpClient()->get('https://graph.instagram.com/me', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::QUERY => $queryParameters,
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'user_id' => $user['user_id'] ?? null,
            'nickname' => $user['username'] ?? null,
            'name' => $user['name'] ?? null,
            'account_type' => $user['account_type'] ?? null,
            'avatar' => $user['profile_picture_url'] ?? null,
            'followers_count' => $user['followers_count'] ?? null,
            'follows_count' => $user['follows_count'] ?? null,
            'media_count' => $user['media_count'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
