<?php

namespace BoxedCode\BinaryUuid;

use Illuminate\Database\Schema\Grammars\SQLiteGrammar as IlluminateSQLiteGrammar;
use Illuminate\Support\Fluent;

class SQLiteGrammar extends IlluminateSQLiteGrammar
{
    protected function typeUuid(Fluent $column)
    {
        return 'blob(256)';
    }
}
