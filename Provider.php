<?php

namespace SocialiteProviders\Instagram;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'INSTAGRAM';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user_profile'];

    protected $fields = 'username,account_type,media_count,media';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://api.instagram.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    protected function getLongTermTokenUrl()
    {
        return 'https://graph.instagram.com/access_token';
    }

    protected function getRefreshLongTermTokenUrl()
    {
        return 'https://graph.instagram.com/refresh_access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $query = [
            'access_token' => $token,
            'fields' => $this->fields,
        ];

        $response = $this->getHttpClient()->get(
            'https://graph.instagram.com/me',
            [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
            [
                'id' => $user['id'],
                'nickname' => $user['username'],
                'name' => $user['username'],
                'account_type' => $user['account_type'],
                'media' => $user['media']['data'],
                'avatar' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post(
            $this->getTokenUrl(),
            [
                'form_params' => $this->getTokenFields($code),
            ]
        );

        $this->credentialsResponseBody = json_decode($response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    public function refreshLongTermAccessToken($token) {
        $query = [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $token,
        ];

        $response = $this->getHttpClient()->get(
            $this->getRefreshLongTermTokenUrl(),
            [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getLongTermAccessToken($oldToken)
    {
        $query = array_merge(
            parent::getTokenFields(null),
            [
                'grant_type' => 'ig_exchange_token',
                'access_token' => $oldToken,
            ]
        );

        $response = $this->getHttpClient()->get(
            $this->getLongTermTokenUrl(),
            [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(
            parent::getTokenFields($code),
            [
                'grant_type' => 'authorization_code',
            ]
        );
    }

}
