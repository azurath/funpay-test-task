<?php
declare(strict_types=1);

namespace FpDbTest\Modules\QueryParser;

use FpDbTest\Modules\QueryParser\Components\Escaper\EscapeStringInterface;
use FpDbTest\Modules\QueryParser\Constants\ErrorMessages;
use FpDbTest\Modules\QueryParser\Constants\ParameterType;
use FpDbTest\Modules\QueryParser\Constants\RegularExpressions;
use FpDbTest\Modules\QueryParser\Constants\VariableType;
use FpDbTest\Modules\QueryParser\Exceptions\QueryParameterUnknownActualTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryParameterUnknownTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryUnknownActualTypeException;
use FpDbTest\Modules\QueryParser\Exceptions\QueryWrongIdentifierException;
use FpDbTest\Modules\QueryParser\Structures\QueryParameter;

/**
 *
 */
class QueryParametersBuilder
{
    private EscapeStringInterface $escaper;

    private const array ALLOWED_PARAMETER_TYPES = [
        ParameterType::PARAMETER_TYPE_D => [
            VariableType::TYPE_INT,
            VariableType::TYPE_NULL,
            VariableType::TYPE_BOOL,
        ],
        ParameterType::PARAMETER_TYPE_F => [
            VariableType::TYPE_INT,
            VariableType::TYPE_FLOAT,
            VariableType::TYPE_NULL,
        ],
        ParameterType::PARAMETER_TYPE_A => [
            VariableType::TYPE_ARRAY,
        ],
        ParameterType::PARAMETER_TYPE_IDENTIFIER => [
            VariableType::TYPE_STRING,
            VariableType::TYPE_ARRAY,
        ],
        ParameterType::PARAMETER_TYPE_MIXED => [
            VariableType::TYPE_INT,
            VariableType::TYPE_FLOAT,
            VariableType::TYPE_STRING,
            VariableType::TYPE_BOOL,
            VariableType::TYPE_NULL,
        ],
    ];

    /**
     * @param EscapeStringInterface $escaper
     */
    public function __construct(EscapeStringInterface $escaper)
    {
        $this->escaper = $escaper;
    }

    /**
     * @param string $query
     * @return array
     */
    public function parseParameters(string $query): array
    {
        $matches = [];
        preg_match_all(RegularExpressions::REGEXP_PARSE_PARAMETERS, $query, $matches);
        return $matches['params'];
    }

    /**
     * @param array $parameters
     * @param array $args
     * @return array
     * @throws QueryParameterUnknownActualTypeException
     * @throws QueryParameterUnknownTypeException
     * @throws QueryUnknownActualTypeException
     * @throws QueryWrongIdentifierException
     */
    public function validateAndBuild(array $parameters, array $args = []): array
    {
        $result = [];
        foreach ($parameters as $key => $parameter) {
            $result[] = new QueryParameter($parameter, $this->buildValue($parameter, $args[$key]));
        }
        return $result;
    }

    /**
     * @param string $parameter
     * @param int|float|string|bool|array|null $value
     * @return string
     * @throws QueryParameterUnknownActualTypeException
     * @throws QueryParameterUnknownTypeException
     * @throws QueryUnknownActualTypeException
     * @throws QueryWrongIdentifierException
     */
    private function buildValue(string $parameter, int|float|string|bool|array|null $value): string
    {
        $this->checkIfParameterTypeAllowed($parameter, $value);

        return match ($parameter) {
            ParameterType::PARAMETER_TYPE_F,
            ParameterType::PARAMETER_TYPE_D,
            ParameterType::PARAMETER_TYPE_MIXED => $this->renderEscapedValue($value),
            ParameterType::PARAMETER_TYPE_A => $this->buildValuesArray($value),
            ParameterType::PARAMETER_TYPE_IDENTIFIER => $this->buildIdentifierList($value),
            default => $this->throwParameterUnknownTypeException($parameter),
        };
    }

    /**
     * @param int|float|string|bool|array|null $value
     * @return string
     */
    private function getActualType(int|float|string|bool|array|null $value): string
    {
        return gettype($value);
    }

    /**
     * @param string $parameter
     * @param int|float|string|bool|array|null $value
     * @return void
     * @throws QueryParameterUnknownActualTypeException
     * @throws QueryParameterUnknownTypeException
     */
    private function checkIfParameterTypeAllowed(string $parameter, int|float|string|bool|array|null $value): void
    {
        if (!isset(self::ALLOWED_PARAMETER_TYPES[$parameter])) {
            $this->throwParameterUnknownTypeException($parameter);
        }

        $actualType = $this->getActualType($value);
        if (!in_array($actualType, self::ALLOWED_PARAMETER_TYPES[$parameter])) {
            $this->throwParameterUnsupportedActualTypeException($parameter, $actualType);
        }
    }

    /**
     * @param array|string $values
     * @return string
     * @throws QueryWrongIdentifierException
     */
    private function buildIdentifierList(array|string $values): string
    {
        return is_array($values)
            ? implode(', ', array_map(function (string $value) {
                return $this->checkAndEscapeIdentifier($value);
            }, $values))
            : $this->checkAndEscapeIdentifier($values);
    }

    /**
     * @param string $identifier
     * @return string
     * @throws QueryWrongIdentifierException
     */
    private function checkAndEscapeIdentifier(string $identifier): string
    {
        $checkResult = preg_match('/^[0-9a-zA-Z$_]+$/', $identifier);
        if ($checkResult === 0) {
            $this->throwWrongIdentifierException($identifier);
        }
        return '`' . $identifier . '`';
    }

    /**
     * @param array $values
     * @return string
     * @throws QueryUnknownActualTypeException
     * @throws QueryWrongIdentifierException
     */
    private function buildValuesArray(array $values): string
    {
        $result = array_is_list($values)
            ? array_map(function (int|float|string|bool|null $value) {
                return $this->renderEscapedValue($value);
            }, $values)
            : array_map(function (string $identifier, int|float|string|bool|null $value) {
                return $this->checkAndEscapeIdentifier($identifier) . ' = ' . $this->renderEscapedValue($value);
            }, array_keys($values), array_values($values));
        return implode(', ', $result);
    }

    /**
     * @param int|float|string|bool|null $value
     * @return string
     * @throws QueryUnknownActualTypeException
     */
    private function renderEscapedValue(int|float|string|bool|null $value): string
    {
        $actualType = $this->getActualType($value);
        return match ($actualType) {
            VariableType::TYPE_INT,
            VariableType::TYPE_FLOAT => (string)$value,
            VariableType::TYPE_STRING => $this->escapeString($value),
            VariableType::TYPE_BOOL => (string)(int)$value,
            VariableType::TYPE_NULL => 'NULL',
            default => $this->throwUnsupportedActualTypeException($actualType),
        };
    }

    /**
     * @param string $string
     * @return string
     */
    private function escapeString(string $string): string
    {
        return '\'' . $this->escaper->escape($string) . '\'';
    }

    /**
     * @param string $parameter
     * @param string $actualType
     * @return void
     * @throws QueryParameterUnknownActualTypeException
     */
    private function throwParameterUnsupportedActualTypeException(string $parameter, string $actualType): void
    {
        throw new QueryParameterUnknownActualTypeException(
            sprintf(
                ErrorMessages::UNSUPPORTED_PARAMETER_ACTUAL_TYPE_ERROR_MESSAGE,
                $actualType,
                $parameter,
            )
        );
    }

    /**
     * @param string $actualType
     * @return void
     * @throws QueryUnknownActualTypeException
     */
    private function throwUnsupportedActualTypeException(string $actualType): void
    {
        throw new QueryUnknownActualTypeException(
            sprintf(
                ErrorMessages::UNSUPPORTED_ACTUAL_TYPE_ERROR_MESSAGE,
                $actualType,
            )
        );
    }

    /**
     * @param string $parameter
     * @return void
     * @throws QueryParameterUnknownTypeException
     */
    private function throwParameterUnknownTypeException(string $parameter): void
    {
        throw new QueryParameterUnknownTypeException(
            sprintf(
                ErrorMessages::UNKNOWN_PARAMETER_TYPE_ERROR_MESSAGE,
                $parameter,
            )
        );
    }

    /**
     * @param string $identifier
     * @return void
     * @throws QueryWrongIdentifierException
     */
    private function throwWrongIdentifierException(string $identifier): void
    {
        throw new QueryWrongIdentifierException(
            sprintf(
                ErrorMessages::WRONG_IDENTIFIER_ERROR_MESSAGE,
                $identifier,
            )
        );
    }
}
