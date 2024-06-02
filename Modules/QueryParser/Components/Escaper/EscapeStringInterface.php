<?php
declare(strict_types=1);

namespace FpDbTest\Modules\QueryParser\Components\Escaper;

/**
 *
 */
interface EscapeStringInterface
{
    /**
     * @param string $string
     * @return string
     */
    public function escape(string $string): string;
}
