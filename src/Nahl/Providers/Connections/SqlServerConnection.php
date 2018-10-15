<?php 
namespace Nahl\Providers\Connections;

use Illuminate\Database\SqlServerConnection as BaseConnection;
use Nahl\Providers\QueryBuilder;

class SqlServerConnection extends BaseConnection {
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
