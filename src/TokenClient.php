<?php

namespace TokenClient;

use Exception;
use TokenClient\Exception\TokenException;
use TokenClient\Cache\RedisInterface;
/**
 * Token服务
 * Class TokenClient
 */
class TokenClient
{
    /**
     * cookie name
     */
    const COOKIE_NAME = 'st';

    /**
     * redis key 分隔符:(:)
     */
    const DELIMITER_COLON = ':';

    /**
     * jwt的唯一身份标识，主要用来作为一次性token
     * 对应cookie里面的st
     */
    protected $tokenKey;

    /**
     * Token 信息
     */
    protected $tokenData;

    /**
     * $redisHandle
     */
    protected $redisHandle;

    /**
     * $redisAuth
     */
    protected $redisAuth;

    /**
     * $redisPrefix
     */
    protected $redisTokenPrefix = 'session';

    /**
     * $redisDb
     */
    protected $redisDb;

    /**
     * $redisKey
     */
    protected $redisKey;

    /**
     * $redisExpire
     */
    protected $redisExpire = 1000;

    /**
     * $consoleURL
     */
    protected $consoleURL;

    /**
     * $locationService
     */
    protected $locationService;

    /**
     * @param RedisInterface $redisHandle redis对象
     * @param string $redisAuth redis密码
     * @param int    $redisDb
     * @param string $consoleURL
     * @param string $locationService
     */
    public function __construct(RedisInterface $redisHandle, $redisAuth, $redisDb, $consoleURL, $locationService)
    {
        $this->redisHandle = $redisHandle;
        $this->redisAuth = $redisAuth;
        $this->redisDb = $redisDb;
        $this->tokenKey = $_COOKIE[self::COOKIE_NAME];
        $this->consoleURL = $consoleURL;
        $this->locationService = $locationService;
        if (!empty($this->tokenKey)) {
            $this->redisKey = $this->redisTokenPrefix . self::DELIMITER_COLON . $this->tokenKey;
        } else {
            $this->locationConsoleUrl();
        }
    }

    /**
     * 根据token获取用户信息
     * @throws Exception
     * @return mixed
     */
    public function get()
    {
        if (empty($this->redisKey)) {
            throw new Exception('cahce key is null', TokenException::CODE_PARAMS_ERROR);
        }
        if (!empty($this->redisAuth)) {
            $this->redisHandle->auth($this->redisAuth);
        }
        if (!empty($this->redisDb)) {
            $this->redisHandle->select($this->redisDb);
        }
        $data = $this->redisHandle->get($this->redisKey);
        $this->tokenData = json_decode($data, true);
        if (empty($this->tokenData)) {
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                return $this;
            }
            $this->locationConsoleUrl();
        }
        return $this;
    }

    /**
     * 设置有效期
     * @param $redisExpire
     * @throws Exception
     * @return mixed
     */
    public function setExpire($redisExpire)
    {
        $this->redisExpire = $redisExpire;
        return $this;
    }

    /**
     * 更新token的有效期
     * @throws Exception
     * @return mixed
     */
    public function expire()
    {
        if (empty($this->redisKey)) {
            throw new Exception('cahce key is null', TokenException::CODE_PARAMS_ERROR);
        }
        if (!empty($this->redisAuth)) {
            $this->redisHandle->auth($this->redisAuth);
        }
        if (!empty($this->redisDb)) {
            $this->redisHandle->select($this->redisDb);
        }
        return $this->redisHandle->expire($this->redisKey, $this->redisExpire);
    }

    /**
     * 删除token信息
     * @throws Exception
     * @return mixed
     */
    public function delete()
    {
        if (empty($this->redisKey)) {
            throw new Exception('cahce key is null', TokenException::CODE_PARAMS_ERROR);
        }
        if (!empty($this->redisAuth)) {
            $this->redisHandle->auth($this->redisAuth);
        }
        if (!empty($this->redisDb)) {
            $this->redisHandle->select($this->redisDb);
        }
        return $this->redisHandle->delete($this->redisKey);
    }

    public function __call($methodName, $args)
    {
        if (preg_match('~^(set|get)([A-Z])(.*)$~', $methodName, $matches)) {
            $property = strtolower($matches[2]) . $matches[3];
            if (!property_exists($this, $property)) {
                throw new Exception('Property ' . $property . ' not exists');
            }
            switch($matches[1]) {
                case 'get':
                    return $this->getProperty($property);
                case 'default':
                    throw new Exception('Method ' . $methodName . ' not exists');
            }
        }
    }

    public function getProperty($property) {
        return $this->$property;
    }

    /**
     *Location to console page
     */
    public function locationConsoleUrl()
    {
        $query_arr = [
            'service' => $this->locationService,
        ];
        $consoleUrl = self::buildQueryURL($this->consoleURL, $query_arr);
        header('Location:' . $consoleUrl);
        exit;
    }
    
    /**
     * @param string $locationService
     */
    public function setLocationService(string $locationService)
    {
        $this->locationService = trim($locationService, '/');
        return $this;
    }

    /**
     * @param string $url
     * @param array $query
     * @return string
     */
    public static function buildQueryURL(string $url, array $query)
    {
        $query_str = '';
        foreach ($query as $k => $v) {
            $query_str .= $k . '=' . urlencode($v) . '&';
        }
        $query_str = rtrim($query_str, '&');
        return $url . '?' . $query_str;
    }
}