<?php
declare(strict_types=1);

namespace House\Model;

use House\Exception\MethodNotAllowedException;

/**
 * Class WriteOnlyGpio
 * @package House\Model
 */
class WriteOnlyGpio extends Gpio
{
    public function read()
    {
        throw new MethodNotAllowedException('READ not allowed');
    }
}
