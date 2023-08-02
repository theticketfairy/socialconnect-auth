<?php
/**
 * SocialConnect project
 *
 * @author: Bogdan Popa https://github.com/icex <bogdan@pixelwattstudio.com>
 */

namespace SocialConnect\OAuth2\Provider;

use SocialConnect\Common\Http\Client\Client;
use SocialConnect\Provider\AccessTokenInterface;
use SocialConnect\Provider\Exception\InvalidAccessToken;
use SocialConnect\Provider\Exception\InvalidResponse;
use SocialConnect\Common\Entity\User;
use SocialConnect\Common\Hydrator\ObjectMap;
use SocialConnect\OAuth2\AccessToken;

class LinkedIn extends \SocialConnect\OAuth2\AbstractProvider
{
    const NAME = 'linkedin';

    /**
     * {@inheritdoc}
     */
    public function getBaseUri()
    {
        return 'https://api.linkedin.com/v2/';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizeUri()
    {
        return 'https://www.linkedin.com/oauth/v2/authorization';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTokenUri()
    {
        return 'https://www.linkedin.com/oauth/v2/accessToken';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function parseToken($body)
    {
        $result = json_decode($body, true);
        if ($result) {
            return new AccessToken($result);
        }

        throw new InvalidAccessToken('AccessToken is not a valid JSON');
    }

	protected function fetchPrimaryEmail(AccessTokenInterface $accessToken, User $user)
	{
		$response = $this->httpClient->request(
			$this->getBaseUri() . 'emailAddress',
			[
				'q' => 'members',
				'projection' => '(elements*(handle~))' // (elements*(primary,type,handle~))
			],
			Client::GET,
			[
				'Authorization' => 'Bearer ' . $accessToken->getToken(),
			]
		);

		if (!$response->isSuccess()) {
			throw new InvalidResponse(
				'API response with error code (on getting email)',
				$response
			);
		}

		$result = $response->json();

		if (isset($result->elements)) {
			$element = array_shift($result->elements);

			if ($element && isset($element->{'handle~'}) && isset($element->{'handle~'}->emailAddress)) {
				$user->email = $element->{'handle~'}->emailAddress;
				$user->emailVerified = true;
			}
		}
	}

	/**
     * {@inheritdoc}
     */
    public function getIdentity(AccessTokenInterface $accessToken)
    {
        $response = $this->httpClient->request(
            $this->getBaseUri() . 'me',
            [],
            Client::GET,
            [
                'Authorization' => 'Bearer ' . $accessToken->getToken(),
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

        $hydrator = new ObjectMap([
			'id' => 'id',
			'emailAddress' => 'email',
			'localizedFirstName' => 'firstname',
			'localizedLastName' => 'lastname',
		]);

        $user = $hydrator->hydrate(new User(), $result);

		$this->fetchPrimaryEmail($accessToken, $user);

		return $user;
    }
}
