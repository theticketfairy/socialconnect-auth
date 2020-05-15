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

class Zoom extends \SocialConnect\OAuth2\AbstractProvider
{
	const NAME = 'zoom';

	/**
	 * {@inheritdoc}
	 */
	public function getBaseUri()
	{
		return 'https://api.zoom.us/v2/';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAuthorizeUri()
	{
		return 'https://zoom.us/oauth/authorize';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequestTokenUri()
	{
		return 'https://zoom.us/oauth/token';
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

	/**
	 * {@inheritdoc}
	 */
	public function getIdentity(AccessTokenInterface $accessToken)
	{
		$response = $this->httpClient->request(
			$this->getBaseUri() . 'users/me',
			[
				'format' => 'json'
			],
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

		$hydrator = new ObjectMap(
			[
				'id' => 'id',
				'email' => 'email',
				'first_name' => 'firstname',
				'last_name' => 'lastname',
				'pic_url' => 'pictureURL',
				'verified' => 'emailVerified',
				'account_id' => 'username',
			]
		);

		return $hydrator->hydrate(new User(), $result);
	}
}
