<?php

namespace App\Utils;

use Symfony\Component\Console\Exception\RuntimeException;

/**
 * This class is used to provide validation helpers for the current services
 *
 */
class Validator
{
    /**
     * @var int Maximum value for Temperature validation
     */
    const MAX = 30;

    /**
     * @var int Minimum value for temperature against which we validate on
     */
    const MIN = 5;

    /**
     * Validates a value (temperature) as integer and a range
     *
     * @param $temperature
     * @param bool $inclusive
     * @return bool
     */
    public function validateTemperatureValue($temperature, $inclusive = false)
    {
        // ToDo: Maybe move the max and min to helper call and make this more generic: validateNumericIsInRange
        if (!is_numeric($temperature)) {
            throw new RuntimeException(sprintf('The provided "%s"value is not a valid one', $temperature));
        }

        return $inclusive
            ? ($temperature >= self::MIN && $temperature <= self::MAX)
            : ($temperature > self::MIN && $temperature < self::MAX);
    }
}
