<?php
declare(strict_types=1);

namespace House\Model;

/**
 * Class Gpio
 * @package House\Model
 */
class Gpio
{
    const OUT	= 'OUT';
    const IN	= 'IN';
    const PWM	= 'PWM';
    const READ  = 'READ';
    const WRITE = 'WRITE';

    /**
     * @var int
     */
    protected $pin;

    /**
     * Gpio constructor.
     * @param int $pin
     */
    public function __construct(int $pin)
    {
        $this->pin = $pin;
    }

    /**
     * @return int
     */
    public function getPin() : int
    {
        return $this->pin;
    }
}
