<?php
namespace Hough\Guzzle\Command;

use Hough\Guzzle\ClientInterface as HttpClient;
use Hough\Guzzle\Command\Exception\CommandException;
use Hough\Guzzle\HandlerStack;
use Hough\Promise;
use Hough\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The Guzzle ServiceClient serves as the foundation for creating web service
 * clients that interact with RPC-style APIs.
 */
class ServiceClient implements ServiceClientInterface
{
    /** @var HttpClient HTTP client used to send requests */
    private $httpClient;

    /** @var HandlerStack */
    private $handlerStack;
    
    /** @var callable */
    private $commandToRequestTransformer;

    /** @var callable */
    private $responseToResultTransformer;

    /**
     * Instantiates a Guzzle ServiceClient for making requests to a web service.
     *
     * @param HttpClient $httpClient A fully-configured Guzzle HTTP client that
     *     will be used to perform the underlying HTTP requests.
     * @param callable $commandToRequestTransformer A callable that transforms
     *     a Command into a Request. The function should accept a
     *     `Hough\Guzzle\Command\CommandInterface` object and return a
     *     `Psr\Http\Message\RequestInterface` object.
     * @param callable $responseToResultTransformer A callable that transforms a
     *     Response into a Result. The function should accept a
     *     `Psr\Http\Message\ResponseInterface` object (and optionally a
     *     `Psr\Http\Message\RequestInterface` object) and return a
     *     `Hough\Guzzle\Command\ResultInterface` object.
     * @param HandlerStack $commandHandlerStack A Guzzle HandlerStack, which can
     *     be used to add command-level middleware to the service client.
     */
    public function __construct(
        HttpClient $httpClient,
        $commandToRequestTransformer,
        $responseToResultTransformer,
        HandlerStack $commandHandlerStack = null
    ) {
        $this->httpClient = $httpClient;
        $this->commandToRequestTransformer = $commandToRequestTransformer;
        $this->responseToResultTransformer = $responseToResultTransformer;
        $this->handlerStack = $commandHandlerStack ?: new HandlerStack();
        $this->handlerStack->setHandler($this->createCommandHandler());
    }

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function getHandlerStack()
    {
        return $this->handlerStack;
    }

    public function getCommand($name, array $params = array())
    {
        return new Command($name, $params, clone $this->handlerStack);
    }

    public function execute(CommandInterface $command)
    {
        return $this->executeAsync($command)->wait();
    }

    public function executeAsync(CommandInterface $command)
    {
        $stack = $command->getHandlerStack() ?: $this->handlerStack;
        $handler = $stack->resolve();

        return $handler($command);
    }

    public function executeAll($commands, array $options = array())
    {
        // Modify provided callbacks to track results.
        $results = array();
        $options['fulfilled'] = function ($v, $k) use (&$results, $options) {
            if (isset($options['fulfilled'])) {
                $options['fulfilled']($v, $k);
            }
            $results[$k] = $v;
        };
        $options['rejected'] = function ($v, $k) use (&$results, $options) {
            if (isset($options['rejected'])) {
                $options['rejected']($v, $k);
            }
            $results[$k] = $v;
        };

        // Execute multiple commands synchronously, then sort and return the results.
        return $this->executeAllAsync($commands, $options)
            ->then(function () use (&$results) {
                ksort($results);
                return $results;
            })
            ->wait();
    }

    public function executeAllAsync($commands, array $options = array())
    {
        // Apply default concurrency.
        if (!isset($options['concurrency'])) {
            $options['concurrency'] = 25;
        }

        // Convert the iterator of commands to a generator of promises.
        $commands = Promise\iter_for($commands);
        $promises = new AsyncExecutingGenerator($commands, array($this, 'executeAsync'));

        // Execute the commands using a pool.
        $eachPromise = new Promise\EachPromise($promises(), $options);
        return $eachPromise->promise();
    }

    /**
     * Creates and executes a command for an operation by name.
     *
     * @param string $name Name of the command to execute.
     * @param array $args Arguments to pass to the getCommand method.
     *
     * @return ResultInterface|PromiseInterface
     * @see \Hough\Guzzle\Command\ServiceClientInterface::getCommand
     */
    public function __call($name, array $args)
    {
        $args = isset($args[0]) ? $args[0] : array();
        if (substr($name, -5) === 'Async') {
            $command = $this->getCommand(substr($name, 0, -5), $args);
            return $this->executeAsync($command);
        } else {
            return $this->execute($this->getCommand($name, $args));
        }
    }

    /**
     * Defines the main handler for commands that uses the HTTP client.
     *
     * @return callable
     */
    private function createCommandHandler()
    {
        $convertCommandToRequest = array($this, '__transformCommandToRequest');
        $convertRequestToPromise = array($this->httpClient, 'sendAsync');
        $convertResponseToResult = array($this, '__transformResponseToResult');

        return function (CommandInterface $command) use ($convertCommandToRequest, $convertRequestToPromise, $convertResponseToResult) {

            return Promise\coroutine(new CommandHandlingGenerator(
                $command,
                $convertCommandToRequest,
                $convertRequestToPromise,
                $convertResponseToResult
            ));
        };
    }

    /**
     * Transforms a Command object into a Request object.
     *
     * @internal
     *
     * @param CommandInterface $command
     * @return RequestInterface
     */
    public function __transformCommandToRequest(CommandInterface $command)
    {
        $transform = $this->commandToRequestTransformer;

        return $transform($command);
    }


    /**
     * Transforms a Response object, also using data from the Request object,
     * into a Result object.
     *
     * @param ResponseInterface $response
     * @param RequestInterface $request
     * @param CommandInterface $command
     * @return ResultInterface
     */
    public function __transformResponseToResult(
        ResponseInterface $response,
        RequestInterface $request,
        CommandInterface $command
    ) {
        $transform = $this->responseToResultTransformer;

        return $transform($response, $request, $command);
    }
}
