<?php

namespace SocialConnect\Provider\Session;

interface SessionInterface
{
    /**
     * @param string $key
     * @param string|null $provider
     *
     * @return mixed
     */
    public function get($key, $provider = null);

    /**
     * @param string $key
     * @param mixed $value
     * @param string|null $provider
     */
    public function set($key, $value, $provider = null);

    /**
     * @param string $key
     * @param string|null $provider
     */
    public function delete($key, $provider = null);
}
