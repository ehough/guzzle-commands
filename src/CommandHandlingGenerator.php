<?php
namespace Hough\Guzzle\Command;

use Hough\Generators\AbstractGenerator;
use Hough\Guzzle\Command\Exception\CommandException;
use Hough\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CommandHandlingGenerator extends AbstractGenerator
{
    /**
     * @var CommandInterface
     */
    private $_command;

    /**
     * @var callable
     */
    private $_commandToRequestConverter;

    /**
     * @var callable
     */
    private $_requestToPromiseConverter;

    /**
     * @var callable
     */
    private $_responseToResultConverter;

    /**
     * @var array
     */
    private $_opts;

    /**
     * @var RequestInterface
     */
    private $_request;

    /**
     * @var PromiseInterface
     */
    private $_promise;

    public function __construct(CommandInterface $command,
                                $commandToRequestConverter,
                                $requestToPromiseConverter,
                                $responseToResultConverter)
    {
        $this->_command                   = $command;
        $this->_commandToRequestConverter = $commandToRequestConverter;
        $this->_requestToPromiseConverter = $requestToPromiseConverter;
        $this->_responseToResultConverter = $responseToResultConverter;
    }

    /**
     * Resume execution of the generator.
     *
     * @param int $position The zero-based "position" of execution.
     *
     * @return null|array Return null to indicate completion. Otherwise return an array of up to two elements. If two
     *                    elements in the array, the first will be considered to be the yielded key and the second the
     *                    yielded value. If one element in the array, it will be considered to be the yielded value and
     *                    the yielded key will be $position.
     */
    protected function resume($position)
    {
        if ($position === 0) {

            $this->_opts = $this->_command['@http'] ?: array();
            unset($this->_command['@http']);

            try {

                $this->_request = call_user_func($this->_commandToRequestConverter, $this->_command);
                $this->_promise = call_user_func($this->_requestToPromiseConverter, $this->_request);

                return array($this->_promise);

            } catch (\Exception $e) {

                $this->onExceptionThrownIn($e, $position);
            }
        }

        if ($position === 1) {

            try {

                return array(

                    call_user_func(
                        $this->_responseToResultConverter,
                        $this->getLastValueSentIn(),
                        $this->_request,
                        $this->_command
                    )
                );

            } catch (\Exception $e) {

                $this->onExceptionThrownIn($e, $position);
            }
        }

        return null;
    }

    protected function onExceptionThrownIn(\Exception $e, $position)
    {
        throw CommandException::fromPrevious($this->_command, $e);
    }
}

//// Prepare the HTTP options.
//$opts = $command['@http'] ?: array();
//unset($command['@http']);
//
//try {
//    // Prepare the request from the command and send it.
//    $request = $this->transformCommandToRequest($command);
//    $promise = $this->httpClient->sendAsync($request, $opts);
//
//    // Create a result from the response.
//    $response = (yield $promise);
//    yield $this->transformResponseToResult($response, $request, $command);
//} catch (\Exception $e) {
//    throw CommandException::fromPrevious($command, $e);
//}