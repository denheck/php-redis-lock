php-redis-lock
==============

## Connecting to Redis:
    RedisLock::connect('tcp://host:port');
Or to just connect to localhost and default port:

    RedisLock::connect();

## Acquiring a Lock:
    $lock = RedisLock::lock('resource');
    if($lock) {
        doSomething();
    }

This will attempt to acquire a lock for the named resource. If successful,
the return value is a RedisLock object. If the resource was already locked,
the return value will be +false+.

## Releasing the Lock:
    RedisLock::release($lock);
Make sure to release the lock once you're done with it, so another client can
acquire it.

## Lock Expiration
If your client acquires a lock and then dies before releasing it, the lock will
expire after a certain amount of time (default 5 minutes). You can set your own
lock expiration when acquiring the lock like so:
    $lock = RedisLock::lock('resource', $expiration_in_seconds);

