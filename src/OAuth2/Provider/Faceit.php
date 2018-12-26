<?php
/**
 * SocialConnect project
 * @author: Taron Saribekyan https://github.com/TaronSaribekyan <saribekyantaron@gmail.com>
 */

namespace SocialConnect\OAuth2\Provider;

use SocialConnect\Provider\AccessTokenInterface;
use SocialConnect\Provider\Exception\InvalidAccessToken;
use SocialConnect\Provider\Exception\InvalidResponse;
use SocialConnect\OAuth2\AccessToken;
use SocialConnect\Common\Entity\User;
use SocialConnect\Common\Hydrator\ObjectMap;

class Faceit extends \SocialConnect\OAuth2\AbstractProvider
{
    const NAME = 'faceit';

    /**
     * {@inheritdoc}
     */
    public function getBaseUri()
    {
        return 'https://api.faceit.com/auth/';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizeUri()
    {
    	// According to documentation: http://assets1.faceit.com/third_party/docs/Faceit_Connect.pdf
        return 'https://cdn.faceit.com/widgets/sso/index.html';
        // return 'https://api.faceit.com/auth/v1/api/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTokenUri()
    {
        return 'https://api.faceit.com/auth/v1/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $code
     * @return \SocialConnect\Common\Http\Request
     */
    protected function makeAccessTokenRequest($code)
    {
        $parameters = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getRedirectUrl()
        ];

        return new \SocialConnect\Common\Http\Request(
            $this->getRequestTokenUri(),
            $parameters,
            $this->requestHttpMethod,
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->consumer->getKey() . ':' . $this->consumer->getSecret())
            ]
        );
    }

    /**
     * @param string $refresh_token
     * @return \SocialConnect\Common\Http\Request
     */
    protected function makeRefreshTokenRequest($refresh_token)
    {
        $parameters = [
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ];

        return new \SocialConnect\Common\Http\Request(
            $this->getRequestTokenUri(),
            $parameters,
            $this->requestHttpMethod,
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->consumer->getKey() . ':' . $this->consumer->getSecret())
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthUrlParameters(): array
    {
        $parameters = parent::getAuthUrlParameters();

        // special parameters only required for FACEIT
        $parameters['redirect_popup'] = 'true';

        return $parameters;
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

        throw new InvalidAccessToken('Server response with not valid/empty JSON');
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(AccessTokenInterface $accessToken)
    {
        $response = $this->httpClient->request(
            $this->getBaseUri() . 'v1/resources/userinfo',
            [
                'access_token' => $accessToken->getToken()
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
                'uuid' => 'id',
                'display_name' => 'fullname',
            ]
        );

        /** @var User $user */
        $user = $hydrator->hydrate(new User(), $result);

        return $user;
    }
}
