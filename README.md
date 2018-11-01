## EXAMPLE

第一步：
实现RedisInterface，获取redis对象
$redisClient = new RedisClient($this->redisHandle);

第二步：
通过TokenClient获取登录信息，
如果获取失败：
    1、当前请求是ajax请求则返回空
    2、当前请求是非ajax请求则跳转登录页面
$tokenClient = new TokenClient($redisClient, $this->redisAuth, $this->redisDb, $this->consoleUrl, $this->requestUri);
$tokenClient->get();
$tokenClient->expire();
$this->tokenData = $tokenClient->getTokenData();