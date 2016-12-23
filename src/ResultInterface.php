<?php
namespace Hough\Guzzle\Command;

/**
 * An array-like object that represents the result of executing a command.
 */
interface ResultInterface extends \ArrayAccess, \IteratorAggregate, \Countable, ToArrayInterface
{
}
