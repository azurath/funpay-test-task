<?php
declare(strict_types=1);

namespace FpDbTest\Modules\QueryParser\Structures;

/**
 *
 */
class QueryParameter
{
    private string $parameter;
    private string $value;

    /**
     * @param string $parameter
     * @param string $value
     */
    public function __construct(string $parameter, string $value)
    {
        $this->parameter = $parameter;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getParameter(): string
    {
        return $this->parameter;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
