<?php
/**
 * SocialConnect project
 */

namespace SocialConnect\OAuth2\Provider;

use SocialConnect\OAuth2\AccessToken;
use SocialConnect\Provider\AccessTokenInterface;
use SocialConnect\Provider\Exception\InvalidAccessToken;
use SocialConnect\Provider\Exception\InvalidResponse;
use SocialConnect\Common\Entity\User;
use SocialConnect\Common\Http\Client\Client;
use SocialConnect\Common\Hydrator\ObjectMap;

class Theticketfairy extends \SocialConnect\OAuth2\AbstractProvider
{
    const NAME = 'theticketfairy';

    const HOST = 'https://www.theticketfairy.com';

    public function getBaseUri()
    {
        return self::HOST . '/api/resource/';
    }

    public function getAuthorizeUri()
    {
        return self::HOST . '/oauth/authorize';
    }

    public function getRequestTokenUri()
    {
        return self::HOST . '/api/oauth/access_token';
    }

    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function parseToken($body)
    {
        if (empty($body)) {
            throw new InvalidAccessToken('Provider response with empty body');
        }

        $result = json_decode($body, true);
        if ($result) {
            return new AccessToken($result);
        }

        throw new InvalidAccessToken('Provider response with not valid JSON');
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(AccessTokenInterface $accessToken)
    {
		$response = $this->httpClient->request(
			$this->getBaseUri() . 'profile',
			[],
			Client::GET,
            [
				'Authorization' => 'Bearer ' . $accessToken->getToken()
			]
		);

		if (!$response->isSuccess()) {
			throw new InvalidResponse(
				'API response with error code',
				$response
			);
		}

		$result = $response->json();
		if (!$result) {
			throw new InvalidResponse(
				'API response is not a valid JSON object',
				$response
			);
		}

		$hydrator = new ObjectMap(
			[
				'id' => 'id',
				'first_name' => 'firstname',
				'last_name' => 'lastname',
				'full_name' => 'fullname',
				'email' => 'email'
			]
		);

		return $hydrator->hydrate(new User(), $result);
    }

}
