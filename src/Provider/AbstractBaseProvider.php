<?php
/**
 * SocialConnect project
 * @author: Patsura Dmitry https://github.com/ovr <talk@dmtry.me>
 */
declare(strict_types=1);

namespace SocialConnect\Provider;

use SocialConnect\Common\Http\Client\ClientInterface;
use SocialConnect\Provider\Session\SessionInterface;

abstract class AbstractBaseProvider
{
    /**
     * @var Consumer
     */
    protected $consumer;

    /**
     * @var array
     */
    protected $scope = [];

    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $redirectUri;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param ClientInterface $httpClient
     * @param SessionInterface $session
     * @param Consumer $consumer
     * @param array $parameters
     */
    public function __construct(ClientInterface $httpClient, SessionInterface $session, Consumer $consumer, array $parameters)
    {
        $this->httpClient = $httpClient;
        $this->session = $session;
        $this->consumer = $consumer;

        if (isset($parameters['scope'])) {
            $this->setScope($parameters['scope']);
        }

        if (isset($parameters['redirectUri'])) {
            $this->redirectUri = $parameters['redirectUri'];
        }

        if (isset($parameters['options'])) {
            $this->options = $parameters['options'];
        }
    }

    /**
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getBoolOption($key, $default): bool
    {
        if (array_key_exists($key, $this->options)) {
            return (bool) $this->options[$key];
        }

        return $default;
    }

    /**
     * @param string $key
     * @param array $default
     * @return array
     */
    public function getArrayOption($key, array $default = []): array
    {
        if (array_key_exists($key, $this->options)) {
            return (array) $this->options[$key];
        }

        return $default;
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return str_replace('${provider}', $this->getName(), $this->redirectUri);
    }

    /**
     * Default parameters for auth url, can be redeclared inside implementation of the Provider
     *
     * @return array
     */
    public function getAuthUrlParameters(): array
    {
        return $this->getArrayOption('auth.parameters', []);
    }

    /**
     * @return string
     */
    abstract public function getBaseUri();

    /**
     * Return Provider's name
     *
     * @return string
     */
    abstract public function getName();

    /**
     * @param array $requestParameters
     * @return \SocialConnect\Provider\AccessTokenInterface
     */
    abstract public function getAccessTokenByRequestParameters(array $requestParameters);

	/**
	 * @param string $refreshToken
	 * @return \SocialConnect\Provider\AccessTokenInterface
	 */
	abstract public function refreshAccessToken(string $refreshToken);

    /**
	 * @param string $callbackUrl
	 * @param string $stateSuffix
	 *
     * @return string
     */
    abstract public function makeAuthUrl($callbackUrl = null, $stateSuffix = null): string;

    /**
     * Get current user identity from social network by $accessToken
     *
     * @param AccessTokenInterface $accessToken
     * @return \SocialConnect\Common\Entity\User
     *
     * @throws \SocialConnect\Provider\Exception\InvalidResponse
     */
    abstract public function getIdentity(AccessTokenInterface $accessToken);

    /**
     * @return array
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param array $scope
     */
    public function setScope(array $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getScopeInline()
    {
        return implode(',', $this->scope);
    }

    /**
     * @return \SocialConnect\Provider\Consumer
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

	public function setCallbackUrl($url)
	{
		if ($url === null) {
			$this->session->delete('oauth_callback_url', $this->getName());
		} else {
			$this->session->set('oauth_callback_url', $url, $this->getName());
		}

		return $this;
	}

	public function getCallbackUrl($remove = true)
	{
		$url = $this->session->get('oauth_callback_url', $this->getName());

		if ($remove) {
			$this->session->delete('oauth_callback_url', $this->getName());
		}

		return $url;
	}
}
