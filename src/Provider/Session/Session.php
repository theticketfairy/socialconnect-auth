<?php
/**
 * @author Patsura Dmitry https://github.com/ovr <talk@dmtry.me>
 */

namespace SocialConnect\Provider\Session;

class Session implements SessionInterface
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

	/**
     * @inheritdoc
     */
    public function get($key, $provider = null)
    {
    	if ($provider !== null) {
    		$key = $key . '_' . $provider;
		}

        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }

	/**
	 * @inheritdoc
	 */
    public function set($key, $value, $provider = null)
    {
		if ($provider !== null) {
			$key = $key . '_' . $provider;
		}

        $_SESSION[$key] = $value;
    }

	/**
	 * @inheritdoc
	 */
    public function delete($key, $provider = null)
    {
		if ($provider !== null) {
			$key = $key . '_' . $provider;
		}

        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
}
