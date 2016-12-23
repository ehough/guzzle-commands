<?php
namespace Hough\Guzzle\Tests\Command;

use Hough\Guzzle\Command\Command;
use Hough\Guzzle\HandlerStack;

/**
 * @covers \Hough\Guzzle\Command\Command
 */
class CommandTest extends \PHPUnit_Framework_TestCase
{
    public function testHasData()
    {
        $c = new Command('foo', array('baz' => 'bar'));
        $this->assertSame('bar', $c['baz']);
        $this->assertTrue($c->hasParam('baz'));
        $this->assertFalse($c->hasParam('boo'));
        $this->assertSame(array('baz' => 'bar'), $c->toArray());
        $this->assertEquals('foo', $c->getName());
        $this->assertCount(1, $c);
        $this->assertInstanceOf('Traversable', $c->getIterator());
    }

    public function testCanInjectHandlerStack()
    {
        $handlerStack = new HandlerStack();
        $c = new Command('foo', array(), $handlerStack);
        $this->assertSame($handlerStack, $c->getHandlerStack());
    }

    public function testCloneUsesDifferentHandlerStack()
    {
        $originalStack = new HandlerStack();
        $command = new Command('foo', array(), $originalStack);
        $this->assertSame($originalStack, $command->getHandlerStack());
        $command2 = clone $command;
        $this->assertNotSame($originalStack, $command2->getHandlerStack());
    }
}
