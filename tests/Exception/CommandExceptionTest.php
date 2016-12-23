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
        $command = $this->getMockForAbstractClass(CommandInterface::class);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $response = $this->getMockForAbstractClass(ResponseInterface::class);

        $exception = new CommandException('error', $command, null, $request, $response);
        $this->assertSame($command, $exception->getCommand());
        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
    }

    public function testFactoryReturnsExceptionIfAlreadyCommandException()
    {
        $command = $this->getMockForAbstractClass(CommandInterface::class);
        $previous = CommandException::fromPrevious($command, new \Exception);

        $exception = CommandException::fromPrevious($command, $previous);
        $this->assertSame($previous, $exception);
    }

    public function testFactoryReturnsClientExceptionFor400LevelStatusCode()
    {
        $command = $this->getMockForAbstractClass(CommandInterface::class);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $response = $this->getMockForAbstractClass(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $previous = new RequestException('error', $request, $response);

        $exception = CommandException::fromPrevious($command, $previous);
        $this->assertInstanceOf(CommandClientException::class, $exception);
    }

    public function testFactoryReturnsServerExceptionFor500LevelStatusCode()
    {
        $command = $this->getMockForAbstractClass(CommandInterface::class);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $response = $this->getMockForAbstractClass(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);
        $previous = new RequestException('error', $request, $response);

        $exception = CommandException::fromPrevious($command, $previous);
        $this->assertInstanceOf(CommandServerException::class, $exception);
    }
}
