<?php
namespace Hough\Guzzle\Command;

/**
 * Default command implementation.
 */
class Result extends HasData implements ResultInterface
{
    /**
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
    }
}
