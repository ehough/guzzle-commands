<?php
namespace Hough\Guzzle\Tests\Command\Guzzle;

use Hough\Generators\ArrayGenerator;
use Hough\Guzzle\Client as HttpClient;
use Hough\Guzzle\Command\Command;
use Hough\Guzzle\Command\CommandInterface;
use Hough\Guzzle\Command\Exception\CommandException;
use Hough\Guzzle\Command\Result;
use Hough\Guzzle\Command\ServiceClient;
use Hough\Guzzle\Exception\BadResponseException;
use Hough\Guzzle\Handler\MockHandler;
use Hough\Guzzle\HandlerStack;
use Hough\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Hough\Psr7\Request;

/**
 * @covers \Hough\Guzzle\Command\ServiceClient
 */
class ServiceClientTest extends \PHPUnit_Framework_TestCase
{
    private function getServiceClient(array $responses)
    {
        return new ServiceClient(
            new HttpClient(array(
                'handler' => new MockHandler($responses)
            )),
            function (CommandInterface $command) {
                $data = $command->toArray();
                $data['action'] = $command->getName();
                return new Request('POST', '/', array(), http_build_query($data));
            },
            function (ResponseInterface $response, RequestInterface $request) {
                $data = json_decode($response->getBody(), true);
                parse_str($request->getBody(), $data['_request']);
                return new Result($data);
            }
        );
    }

    public function testCanGetHttpClientAndHandlers()
    {
        $httpClient = new HttpClient();
        $handlers = new HandlerStack();
        $fn = function () {};
        $serviceClient = new ServiceClient($httpClient, $fn, $fn, $handlers);
        $this->assertSame($httpClient, $serviceClient->getHttpClient());
        $this->assertSame($handlers, $serviceClient->getHandlerStack());
    }

    public function testExecuteCommandViaMagicMethod()
    {
        $client = $this->getServiceClient(array(
            new Response(200, array(), '{"foo":"bar"}'),
            new Response(200, array(), '{"foofoo":"barbar"}'),
        ));

        // Synchronous
        $result1 = $client->doThatThingYouDo(array('fizz' => 'buzz'));
        $this->assertEquals('bar', $result1['foo']);
        $this->assertEquals('buzz', $result1['_request']['fizz']);
        $this->assertEquals('doThatThingYouDo', $result1['_request']['action']);

        // Asynchronous
        $result2 = $client->doThatThingOtherYouDoAsync(array('fizz' => 'buzz'))->wait();
        $this->assertEquals('barbar', $result2['foofoo']);
        $this->assertEquals('doThatThingOtherYouDo', $result2['_request']['action']);
    }

    public function testCommandExceptionIsThrownWhenAnErrorOccurs()
    {
        $client = $this->getServiceClient(array(
            new BadResponseException(
                'Bad Response',
                $this->getMockForAbstractClass('\Psr\Http\Message\RequestInterface'),
                $this->getMockForAbstractClass('\Psr\Http\Message\ResponseInterface')
            ),
        ));

        $this->setExpectedException('\Hough\Guzzle\Command\Exception\CommandException');
        $client->execute($client->getCommand('foo'));
    }

    public function testExecuteMultipleCommands()
    {
        // Set up commands to execute concurrently.
        $generateCommands = new ArrayGenerator(array(
            new Command('capitalize', array('letter' => 'a')),
            new Command('capitalize', array('letter' => '2')),
            new Command('capitalize', array('letter' => 'z')),
        ));

        // Setup a client with mock responses for the commands.
        // Note: the second one will be a failed request.
        $client = $this->getServiceClient(array(
            new Response(200, array(), '{"letter":"A"}'),
            new BadResponseException(
                'Bad Response',
                $this->getMockForAbstractClass('\Psr\Http\Message\RequestInterface'),
                new Response(200, array(), '{"error":"Not a letter"}')
            ),
            new Response(200, array(), '{"letter":"Z"}'),
        ));

        // Setup fulfilled/rejected callbacks, just to confirm they are called.
        $fulfilledFnCalled = false;
        $rejectedFnCalled = false;
        $options = array(
            'fulfilled' => function () use (&$fulfilledFnCalled) {
                $fulfilledFnCalled = true;
            },
            'rejected' => function () use (&$rejectedFnCalled) {
                $rejectedFnCalled = true;
            },
        );

        // Execute multiple commands.
        $results = $client->executeAll($generateCommands(), $options);

        // Make sure the callbacks were called
        $this->assertTrue($fulfilledFnCalled);
        $this->assertTrue($rejectedFnCalled);

        // Validate that the results are as expected.
        $this->assertCount(3, $results);
        $this->assertInstanceOf('\Hough\Guzzle\Command\Result', $results[0]);
        $this->assertEquals('A', $results[0]['letter']);
        $this->assertInstanceOf('\Hough\Guzzle\Command\Exception\CommandException', $results[1]);
        $this->assertContains(
            'Not a letter',
            (string) $results[1]->getResponse()->getBody()
        );
        $this->assertInstanceOf('\Hough\Guzzle\Command\Result', $results[2]);
        $this->assertEquals('Z', $results[2]['letter']);
    }

    public function testMultipleCommandsFailsForNonCommands()
    {
        $generateCommands = function () {
            yield 'foo';
        };

        $this->setExpectedException('\InvalidArgumentException');

        $client = $this->getServiceClient(array());
        $client->executeAll($generateCommands());
    }
}
