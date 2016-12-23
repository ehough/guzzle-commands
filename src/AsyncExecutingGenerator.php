<?php
namespace Hough\Guzzle\Command;

use Hough\Generators\AbstractGenerator;

class AsyncExecutingGenerator extends AbstractGenerator
{
    /**
     * @var \Iterator
     */
    private $_commands;

    /**
     * @var callable
     */
    private $_asyncExecutor;

    public function __construct(\Iterator $commands, $asyncExecutor)
    {
        $this->_commands      = $commands;
        $this->_asyncExecutor = $asyncExecutor;
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

            $this->_commands->rewind();
        }

        if (!$this->_commands->valid()) {

            return null;
        }

        $command = $this->_commands->current();
        $key     = $this->_commands->key();

        if (!$command instanceof CommandInterface) {

            throw new \InvalidArgumentException('The iterator must yield instances of \Hough\Guzzle\Command\CommandInterface');
        }

        $toReturn = array($key, call_user_func($this->_asyncExecutor, $command));

        $this->_commands->next();

        return $toReturn;
    }
}

//if (!$command instanceof CommandInterface) {
//    throw new \InvalidArgumentException('The iterator must '
//        . 'yield instances of \Hough\Guzzle\Command\CommandInterface');
//}
//yield $key => $this->executeAsync($command);