<?php
declare(strict_types=1);

namespace FpDbTest\Modules\QueryParser;

use FpDbTest\Modules\QueryParser\Components\Escaper\EscapeStringInterface;
use FpDbTest\Modules\QueryParser\Constants\ErrorMessages;
use FpDbTest\Modules\QueryParser\Constants\RegularExpressions;
use FpDbTest\Modules\QueryParser\Exceptions\QueryParameterUnknownActualTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryParameterUnknownTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryUnknownActualTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryWrongArgumentsCountException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryWrongIdentifierException;
use FpDbTest\Modules\QueryParser\Structures\QueryPart;

/**
 *
 */
class QueryBuilder
{
    private string $skipValue;
    private QueryParametersBuilder $queryParametersBuilder;
    private int $queryOffset;
    private string $query;

    /**
     * @param string $skipValue
     * @param EscapeStringInterface $escaper
     */
    public function __construct(string $skipValue, EscapeStringInterface $escaper)
    {
        $this->skipValue = $skipValue;
        $this->queryParametersBuilder = new QueryParametersBuilder($escaper);
    }

    /**
     * @param string $query
     * @param array $args
     * @return string
     * @throws QueryParameterUnknownActualTypeException
     * @throws QueryParameterUnknownTypeException
     * @throws QueryUnknownActualTypeException
     * @throws QueryWrongIdentifierException
     * @throws QueryWrongArgumentsCountException
     */
    public function build(string $query, array $args = []): string
    {
        $this->query = $query;
        $result = [];
        $matches = [];
        $count = preg_match_all(RegularExpressions::REGEXP_SPLIT_TO_BLOCKS, $this->query, $matches);
        if ($count > 0) {
            unset($matches[0]);
            $result = $this->processMatchedQueryParts($matches, $args);
        } else {
            $parameters = $this->queryParametersBuilder->parseParameters($this->query);
            $this->checkArgumentsCount($parameters, $args);
            $builtParameters = $this->queryParametersBuilder->validateAndBuild($parameters, $args);
            $result[] = $this->buildQueryPart($this->query, $builtParameters);
        }
        return implode('', array_map(function (QueryPart $queryPart) {
            return $queryPart->renderQuery();
        }, array_filter($result)));
    }

    /**
     * @param string $part
     * @param array $builtParameters
     * @return QueryPart
     */
    private function buildQueryPart(string $part, array $builtParameters = []): QueryPart
    {
        return new QueryPart($part, $builtParameters);
    }

    /**
     * @param array $matches
     * @param array $args
     * @return array
     * @throws QueryParameterUnknownActualTypeException
     * @throws QueryParameterUnknownTypeException
     * @throws QueryUnknownActualTypeException
     * @throws QueryWrongIdentifierException
     * @throws QueryWrongArgumentsCountException
     */
    private function processMatchedQueryParts(array $matches, array $args = []): array
    {
        $result = [];
        $this->queryOffset = 0;
        foreach ($matches['clear'] as $key => $match) {
            if (!empty(trim($match))) {
                $result[] = $this->processQueryPart($match, $args);
                if (!empty(trim($matches['block'][$key]))) {
                    $result[] = $this->processQueryPart($matches['block'][$key], $args, true);
                }
            }
        }
        return $result;
    }

    /**
     * @param string $query
     * @param array $args
     * @param bool $isBlock
     * @return QueryPart|null
     * @throws QueryParameterUnknownActualTypeException
     * @throws QueryParameterUnknownTypeException
     * @throws QueryUnknownActualTypeException
     * @throws QueryWrongIdentifierException
     * @throws QueryWrongArgumentsCountException
     */
    private function processQueryPart(string $query, array $args, bool $isBlock = false): ?QueryPart
    {
        $parameters = $this->queryParametersBuilder->parseParameters($query);
        $localArgs = $this->getSlice($args, $this->queryOffset, sizeof($parameters));
        $this->checkArgumentsCount($parameters, $localArgs);
        if ($isBlock && $this->shouldHideBlock($args)) {
            return null;
        }
        $builtParameters = $this->queryParametersBuilder->validateAndBuild($parameters, $localArgs);
        $this->queryOffset += sizeof($parameters);
        return $this->buildQueryPart($query, $builtParameters);
    }

    /**
     * @param array $args
     * @return bool
     */
    private function shouldHideBlock(array $args = []): bool
    {
        return in_array($this->skipValue, $args, true);
    }

    /**
     * @param array $array
     * @param int $start
     * @param int $count
     * @return array
     */
    private function getSlice(array $array, int $start, int $count): array
    {
        return array_slice($array, $start, $count);
    }

    /**
     * @param array $parameters
     * @param array $args
     * @return void
     * @throws QueryWrongArgumentsCountException
     */
    private function checkArgumentsCount(array $parameters, array $args = []): void
    {
        if (sizeof($parameters) !== sizeof($args)) {
            throw new QueryWrongArgumentsCountException(
                sprintf(
                    ErrorMessages::WRONG_ARGUMENTS_COUNT_ERROR_MESSAGE,
                    sizeof($args),
                    sizeof($parameters),
                    $this->query,
                )
            );
        }
    }
}
