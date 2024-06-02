<?php
declare(strict_types=1);

namespace FpDbTest;

use FpDbTest\Modules\QueryParser\Components\Escaper\EscapeString;
use FpDbTest\Modules\QueryParser\Exceptions\QueryBracesOrQuotesException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryParameterUnknownActualTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryParameterUnknownTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QuerySyntaxException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryUnknownActualTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryWrongArgumentsCountException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryWrongIdentifierException;
use FpDbTest\Modules\QueryParser\QueryBuilder;
use FpDbTest\Modules\QueryParser\QueryValidator;
use mysqli;
use Random\RandomException;

/**
 *
 */
class Database implements DatabaseInterface
{
    private mysqli $mysqli;
    private QueryValidator $queryValidator;
    private QueryBuilder $queryPartsBuilder;
    private string $skip;
    private const string SKIP_PREFIX = '_SYSTEM_SKIP_';

    /**
     * @param mysqli $mysqli
     * @throws RandomException
     */
    public function __construct(mysqli $mysqli)
    {
        $this->skip = $this->generateSkipValue();
        $this->mysqli = $mysqli;
        $this->queryValidator = new QueryValidator();
        $this->queryPartsBuilder = new QueryBuilder($this->skip, new EscapeString($this->mysqli));
    }

    /**
     * @param string $query
     * @param array $args
     * @return string
     * @throws QueryBracesOrQuotesException
     * @throws QueryParameterUnknownActualTypeException
     * @throws QueryParameterUnknownTypeException
     * @throws QuerySyntaxException
     * @throws QueryUnknownActualTypeException
     * @throws QueryWrongIdentifierException
     * @throws QueryWrongArgumentsCountException
     */
    public function buildQuery(string $query, array $args = []): string
    {
        $this->queryValidator->validate($query);
        return $this->queryPartsBuilder->build($query, $args);
    }

    /**
     * @return string
     */
    public function skip(): string
    {
        return $this->skip;
    }

    /**
     * @return string
     * @throws RandomException
     */
    private function generateSkipValue(): string
    {
        return self::SKIP_PREFIX . md5(random_bytes(64)) . md5(random_bytes(64));
    }


}
