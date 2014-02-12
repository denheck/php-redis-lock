<?php

require '../vendor/autoload.php';

class RedisLock
{
    protected static $predis;
    protected $token;
    protected $resource;

    public static function connect($config = null)
    {
        self::$predis = new Predis\Client($config);
    }

    public static function lock($resource, $expiration = 300)
    {
        $lock = new RedisLock($resource);
        $predis = self::$predis;

        $reply = self::$predis->set($resource, $lock->getToken(), 'NX', 'EX', $expiration);

        return (1 == $reply) ? $lock : false;
    }

    public static function release($lock)
    {
        $script = <<<LUA
if redis.call("get",KEYS[1]) == ARGV[1]
then
    return redis.call("del",KEYS[1])
else
    return 0
end
LUA;

        self::$predis->eval($script, 1, $lock->getResource(), $lock->getToken());
    }

    public static function disconnect()
    {
        self::$predis = null;
    }

    public function __construct($resource)
    {
        $this->setResource($resource);
        $this->generateToken();
    }

    public function generateToken()
    {
        $this->token = (string) mt_rand();
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }
}
