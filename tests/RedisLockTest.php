<?php

require '../src/RedisLock.php';

class RedisLockTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->predis = new Predis\Client();
        $this->predis->flushdb();
    }

    public function testConnect()
    {
        RedisLock::connect(array(
            'host' => 'localhost'
        ));

        RedisLock::connect('tcp://localhost:6379');
    }

    public function testConnectPredisClient()
    {
        $client = new Predis\Client();
        RedisLock::connect($client);
    }

    public function testLock()
    {
        RedisLock::connect();
        $lock = RedisLock::lock('test-resource');

        $this->assertEquals(
            $lock->getToken(),
            $this->predis->get($lock->getKey())
        );
    }

    public function testLockWithExpiration()
    {
        RedisLock::connect();
        $lock = RedisLock::lock('test-resource', 1);

        $this->assertEquals(
            $lock->getToken(),
            $this->predis->get($lock->getKey())
        );

        sleep(2);

        $this->assertNull($this->predis->get($lock->getKey()));
    }

    public function testCouldNotLock()
    {
        RedisLock::connect();
        $goodLock = RedisLock::lock('test-resource');
        $badLock = RedisLock::lock('test-resource');

        $this->assertFalse($badLock);
    }

    public function testRelease()
    {
        RedisLock::connect();
        $lock = RedisLock::lock('test-resource');
        RedisLock::release($lock);

        $this->assertFalse($this->predis->exists($lock->getKey()));
    }

    public function testReleaseDefunctLock()
    {
        RedisLock::connect();
        $oldLock = new RedisLock('test-resource');

        // pretend old lock has expired and gone away, and another client has
        // created a new lock
        $newLock = RedisLock::lock('test-resource');

        // shouldn't be able to release my old lock
        RedisLock::release($oldLock);

        // new lock should still be there
        $this->assertEquals(
            $newLock->getToken(),
            $this->predis->get($newLock->getKey())
        );
    }

    public function testSetPrefix()
    {
        RedisLock::connect();
        RedisLock::setPrefix('test');

        $lock = RedisLock::lock('test-resource');
        $this->assertEquals(
            $lock->getToken(),
            $this->predis->get('test:test-resource')
        );

    }

    protected function tearDown()
    {
        RedisLock::disconnect();
    }
}
