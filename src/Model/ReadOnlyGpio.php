<?php
declare(strict_types=1);

namespace House\Model;

use House\Exception\MethodNotAllowedException;

/**
 * Class ReadOnlyGpio
 * @package House\Model
 */
class ReadOnlyGpio extends Gpio
{
    public function write()
    {
        throw new MethodNotAllowedException('WRITE not allowed');
    }
}
