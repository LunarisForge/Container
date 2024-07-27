<?php

namespace Container;

use Exception;
use LunarisForge\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testBindAndResolve(): void
    {
        $container = new Container();
        $container->bind('foo', function () {
            return 'bar';
        });

        $result = $container->resolve('foo');

        $this->assertEquals('bar', $result);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testSingleton(): void
    {
        $container = new Container();
        $instance = new stdClass();
        $container->singleton('foo', $instance);

        $result1 = $container->resolve('foo');
        $result2 = $container->resolve('foo');

        $this->assertSame($instance, $result1);
        $this->assertSame($result1, $result2);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAutomaticDependencyResolution(): void
    {
        $container = new Container();
        $container->bind(Foo::class, Foo::class);
        $container->bind(Bar::class, Bar::class);

        $foo = $container->resolve(Foo::class);

        $this->assertInstanceOf(Foo::class, $foo);
        $this->assertInstanceOf(Bar::class, $foo->bar);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testNonClassDependencyResolution(): void
    {
        $container = new Container();
        $container->bind(FooWithParam::class, FooWithParam::class);
        $container->singleton('param', 'some value');

        $foo = $container->resolve(FooWithParam::class);

        $this->assertInstanceOf(FooWithParam::class, $foo);
        $this->assertEquals('some value', $foo->param);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testExceptionForUnresolvableDependency(): void
    {
        $this->expectException(Exception::class);

        $container = new Container();
        $container->resolve('nonexistent');
    }
}

class Foo
{
    public function __construct(public Bar $bar)
    {
    }
}

class Bar
{
}

class FooWithParam
{
    public function __construct(public string $param)
    {
    }
}
