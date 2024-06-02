<?php

namespace FpDbTest\Modules\QueryParser\Constants;

/**
 *
 */
class ErrorMessages
{
    public const string WRONG_ARGUMENTS_COUNT_ERROR_MESSAGE = 'Wrong arguments count. Arguments count: %s, parameters count: %s. Query: "%s"';
    public const string UNSUPPORTED_PARAMETER_ACTUAL_TYPE_ERROR_MESSAGE = 'Unsupported type %s for parameter %s.';
    public const string UNSUPPORTED_ACTUAL_TYPE_ERROR_MESSAGE = 'Unsupported argument type %s.';
    public const string UNKNOWN_PARAMETER_TYPE_ERROR_MESSAGE = 'Unknown parameter type %s.';
    public const string WRONG_IDENTIFIER_ERROR_MESSAGE = 'Wrong identifier "%s".';
    public const string REGEXP_GLOBAL_BRACES_ERROR_MESSAGE = 'Unclosed braces or quotes found. Query: "%s"';
    public const string REGEXP_SYNTAX_ERROR_MESSAGE = 'Syntax error near "%s" at position %s. Query: "%s"';
}
