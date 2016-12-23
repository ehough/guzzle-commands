<?php
namespace Hough\Guzzle\Tests\Command\CommandException;

use Hough\Guzzle\Command\CommandInterface;
use Hough\Guzzle\Command\Exception\CommandClientException;
use Hough\Guzzle\Command\Exception\CommandException;
use Hough\Guzzle\Command\Exception\CommandServerException;
use Hough\Guzzle\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \Hough\Guzzle\Command\Exception\CommandException
 */
class CommandExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCanGetDataFromException()
    {
        $command = $this->getMockForAbstractClass('\Hough\Guzzle\Command\CommandInterface');
        $request = $this->getMockForAbstractClass('\Psr\Http\Message\RequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');

        $exception = new CommandException('error', $command, null, $request, $response);
        $this->assertSame($command, $exception->getCommand());
        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
    }

    public function testFactoryReturnsExceptionIfAlreadyCommandException()
    {
        $command = $this->getMockForAbstractClass('\Hough\Guzzle\Command\CommandInterface');
        $previous = CommandException::fromPrevious($command, new \Exception);

        $exception = CommandException::fromPrevious($command, $previous);
        $this->assertSame($previous, $exception);
    }

    public function testFactoryReturnsClientExceptionFor400LevelStatusCode()
    {
        $command = $this->getMockForAbstractClass('\Hough\Guzzle\Command\CommandInterface');
        $request = $this->getMockForAbstractClass('\Psr\Http\Message\RequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $response->method('getStatusCode')->willReturn(404);
        $previous = new RequestException('error', $request, $response);

        $exception = CommandException::fromPrevious($command, $previous);
        $this->assertInstanceOf('\Hough\Guzzle\Command\Exception\CommandClientException', $exception);
    }

    public function testFactoryReturnsServerExceptionFor500LevelStatusCode()
    {
        $command = $this->getMockForAbstractClass('\Hough\Guzzle\Command\CommandInterface');
        $request = $this->getMockForAbstractClass('\Psr\Http\Message\RequestInterface');
        $response = $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface');
        $response->method('getStatusCode')->willReturn(500);
        $previous = new RequestException('error', $request, $response);

        $exception = CommandException::fromPrevious($command, $previous);
        $this->assertInstanceOf('\Hough\Guzzle\Command\Exception\CommandServerException', $exception);
    }
}
