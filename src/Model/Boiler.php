<?php
declare(strict_types=1);

namespace House\Model;

/**
 * Class Boiler
 * @package House\Model
 */
class Boiler
{
    /**
     * @var string
     */
    protected $id;

    /**
     * Boiler constructor.
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }
}
