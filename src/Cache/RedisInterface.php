<?php

namespace TokenClient\Cache;

/**
 * Class RedisInterface
 * @package Cache
 */
interface RedisInterface {

    /**
     * redis get
     * @param string $key
     * @return string
     */
    public function get(string $key);

    /**
     * redis expire
     * @param string $key
     * @param int    $timeSec
     * @return int
     */
    public function expire(string $key, int $timeSec);

    /**
     * redis auth
     * @param string $password
     * @return bool
     */
    public function auth(string $password);

    /**
     * redis db
     * @param int $db
     * @return bool
     */
    public function select(int $db);

    /**
     * redis db
     * @param string $key
     */
    public function delete(string $key);

}