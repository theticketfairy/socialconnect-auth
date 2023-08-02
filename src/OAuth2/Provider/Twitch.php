<?php
/**
 * SocialConnect project
 * @author: Patsura Dmitry https://github.com/ovr <talk@dmtry.me>
 */

namespace SocialConnect\OAuth2\Provider;

use SocialConnect\Common\Http\Client\Client;
use SocialConnect\Provider\AccessTokenInterface;
use SocialConnect\Provider\Exception\InvalidAccessToken;
use SocialConnect\Provider\Exception\InvalidResponse;
use SocialConnect\OAuth2\AccessToken;
use SocialConnect\Common\Entity\User;
use SocialConnect\Common\Hydrator\ObjectMap;

class Twitch extends \SocialConnect\OAuth2\AbstractProvider
{
    const NAME = 'twitch';

    /**
     * {@inheritdoc}
     */
    public function getBaseUri()
    {
        return 'https://api.twitch.tv/helix/';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizeUri()
    {
        return 'https://id.twitch.tv/oauth2/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTokenUri()
    {
        return 'https://id.twitch.tv/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getScopeInline()
    {
        /**
		 * @link https://dev.twitch.tv/docs/authentication#scopes
		 */
        return implode('+', $this->scope);
    }

    /**
     * {@inheritdoc}
     */
    public function parseToken($body)
    {
        $response = json_decode($body, true);
        if ($response) {
            return new AccessToken($response);
        }

        throw new InvalidAccessToken('AccessToken is not a valid JSON');
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(AccessTokenInterface $accessToken)
    {
        $response = $this->httpClient->request(
            $this->getBaseUri() . 'users',
            [],
            Client::GET,
            [
				'Authorization' => 'Bearer ' . $accessToken->getToken(),
				'Client-Id' => $this->consumer->getKey()
            ]
        );

        if (!$response->isSuccess()) {
            throw new InvalidResponse(
                'API response with error code',
                $response
            );
        }

        $result = $response->json();

        if (!$result || !isset($result->data) || !isset($result->data[0])) {
            throw new InvalidResponse(
                'API response is not a valid JSON object',
                $response
            );
        }

        $hydrator = new ObjectMap(
            [
                'id' => 'id',
                'display_name' => 'fullname', // Custom Capitalized Users name
                'login' => 'username',
				'profile_image_url' => 'pictureURL'
            ]
        );

        $user = $hydrator->hydrate(new User(), $result->data[0]);

        if ($user->email) {
			$user->emailVerified = true;
		}

        return $user;
    }
}
