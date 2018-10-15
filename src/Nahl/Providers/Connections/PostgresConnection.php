<?php 
namespace Nahl\Providers\Connections;

use Illuminate\Database\PostgresConnection as BaseConnection;
use Nahl\Providers\QueryBuilder;

class PostgresConnection extends BaseConnection {
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
