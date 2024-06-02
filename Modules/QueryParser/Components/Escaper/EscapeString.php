<?php
declare(strict_types=1);

namespace FpDbTest\Modules\QueryParser\Components\Escaper;

use mysqli;

/**
 *
 */
class EscapeString implements EscapeStringInterface
{
    private mysqli $mysqli;

    /**
     * @param mysqli $mysqli
     */
    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * @param string $string
     * @return string
     */
    public function escape(string $string): string
    {
        return $this->mysqli->real_escape_string($string);
    }
}
