<?php

namespace Support\Socialite;

use GuzzleHttp\Client;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface {

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'AASANDHA';

    protected $stateless = true;


        /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client(['verify' => false ]);
        }

        return $this->httpClient;
    }


    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(env('OAUTH_AUTHCODE_URI'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return env('OAUTH_ACCESSTOKEN_URI');
    }
    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(env('OAUTH_RESOURCE_URI'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return json_decode($response->getBody(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['data']['user']['id'],
            'email'    => $user['data']['user']['attributes']['email_address'],
            'name'     => $user['data']['user']['attributes']['full_name'],
            'avatar'   => $user['data']['user']['attributes']['photo']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_add(
            parent::getTokenFields($code), 'grant_type', 'authorization_code'
        );
    }

    public function logout()
    {
        $logout = \Auth::logout();
        $authUrl = urlencode($this->getAuthUrl(null));
        $url = env('OAUTH_LOGOUT_URI') . '?redirect_uri=' . $authUrl;
        header('Location: ' . $url);
        exit;
    }

}