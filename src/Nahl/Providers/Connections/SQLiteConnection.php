<?php 
namespace Nahl\Providers\Connections;

use Illuminate\Database\SQLiteConnection as BaseConnection;
use Nahl\Providers\QueryBuilder;

class SQLiteConnection extends BaseConnection {
    /**
    * Begin a fluent query against a database table.
    *
    * @param  string  $table
    * @return MySql\Query\Builder
    */
    public function table($table)
    {
        $processor = $this->getPostProcessor();

        $query = new QueryBuilder($this, $this->getQueryGrammar(), $processor);

        return $query->from($table);
    }
}
