<?php
declare(strict_types=1);

namespace FpDbTest\Modules\QueryParser;

use FpDbTest\Modules\QueryParser\Constants\ErrorMessages;
use FpDbTest\Modules\QueryParser\Constants\RegularExpressions;
use FpDbTest\Modules\QueryParser\Exceptions\QueryBracesOrQuotesException;
use FpDbTest\Modules\QueryParser\Exceptions\QuerySyntaxException;

/**
 *
 */
class QueryValidator
{
    private string $query;

    /**
     * @param string $query
     * @throws QueryBracesOrQuotesException
     * @throws QuerySyntaxException
     */
    public function validate(string $query): void
    {
        $this->query = $query;
        if ($this->validateQuery(RegularExpressions::REGEXP_GLOBAL_BRACES_PARENTHESES) !== 1) {
            throw new QueryBracesOrQuotesException($this->formatQueryBracesErrorMessage($query));
        }
        $errors = [];
        $validationResult = $this->validateQuery($this->buildSyntaxCheckRegexp(), $errors, true);
        if ($validationResult > 0) {
            throw new QuerySyntaxException($this->formatSyntaxErrorMessage($query, $errors));
        }
    }

    /**
     * @param string $regexp
     * @param array $matches
     * @param bool $captureOffset
     * @return false|int
     */
    private function validateQuery(string $regexp, array &$matches = [], bool $captureOffset = false): false|int
    {
        return preg_match_all($regexp, $this->query, $matches, $captureOffset ? PREG_OFFSET_CAPTURE : 0);
    }

    /**
     * @return string
     */
    private function buildSyntaxCheckRegexp(): string
    {
        return sprintf(
            '/(%s)|(%s)|(%s)|(%s)/mi',
            RegularExpressions::REGEXP_SYNTAX_PARAMETERS,
            RegularExpressions::REGEXP_SYNTAX_IN,
            RegularExpressions::REGEXP_SYNTAX_SET,
            RegularExpressions::REGEXP_SYNTAX_ASSIGNMENT,
        );
    }

    /**
     * @param string $query
     * @param array $errors
     * @return string
     */
    private function formatSyntaxErrorMessage(string $query, array $errors): string
    {
        return sprintf(
            ErrorMessages::REGEXP_SYNTAX_ERROR_MESSAGE,
            $errors[0][0][0],
            $errors[0][0][1],
            $query,
        );
    }

    /**
     * @param string $query
     * @return string
     */
    private function formatQueryBracesErrorMessage(string $query): string
    {
        return sprintf(
            ErrorMessages::REGEXP_GLOBAL_BRACES_ERROR_MESSAGE,
            $query,
        );
    }
}
