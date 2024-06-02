<?php
declare(strict_types=1);

namespace FpDbTest\Modules\QueryParser\Structures;

/**
 *
 */
class QueryPart
{

    private string $query;
    private array $buildParameters;

    /**
     * @param string $queryPart
     * @param array $builtParameters
     */
    public function __construct(string $queryPart, array $builtParameters)
    {
        $this->query = $queryPart;
        $this->buildParameters = $builtParameters;
    }

    /**
     * @return string
     */
    public function renderQuery(): string
    {
        $renderedQuery = $this->query;

        /** @var QueryParameter $parameter */
        foreach ($this->buildParameters as $parameter) {
            $renderedQuery = preg_replace('/\\' . $parameter->getParameter() . '/i', $parameter->getValue(), $renderedQuery, 1);
        }

        return $renderedQuery;
    }
}
