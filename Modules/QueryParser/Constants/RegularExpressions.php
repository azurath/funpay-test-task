<?php

namespace FpDbTest\Modules\QueryParser\Constants;

/**
 *
 */
class RegularExpressions
{
    public const string REGEXP_SPLIT_TO_BLOCKS = '/(?<clear>[^\{\}]*+)\{(?<block>[^\}]*)\}/mi';

    public const string REGEXP_PARSE_PARAMETERS = '/(?<params>\?[dfa#]?)([\s})]|$){1}/mi';
    public const string REGEXP_GLOBAL_BRACES_PARENTHESES = '/^(((?:[^\{\}\(\)\[\]\']|(\{(?1)\}|\((?1)\)|\[(?1)\]|\'(?1)\'))*+))$/';
    public const string REGEXP_SYNTAX_PARAMETERS = '((\?[dfa#]?([^\s})]{2,})|([^\s${(]{1,}\?[dfa#]?))|\?((?:[^dfa#\s\}\)])+))';
    public const string REGEXP_SYNTAX_IN = '((IN(\s+\(\?[^a]\)|((\s|$)[^\(]))))|(\?[^\s#]+\s+IN)';
    public const string REGEXP_SYNTAX_SET = '(SET\s+\?[^a\s]+)';
    public const string REGEXP_SYNTAX_ASSIGNMENT = '(\`?[a-zA-Z0-9\_]+\`?\s?=\s?)((\?((?:[^dfa#\s]|$)+(?:[^\s]|$)+))|(\?([dfa#]+[^\s}]+)))';

}
