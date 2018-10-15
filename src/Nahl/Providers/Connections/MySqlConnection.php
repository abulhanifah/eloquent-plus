<?php 
namespace Nahl\Providers\Connections;

use Illuminate\Database\MySqlConnection as BaseConnection;
use Nahl\Providers\QueryBuilder;

class MySqlConnection extends BaseConnection {
    /**
    * Begin a fluent query against a database table.
    *
    * @param  string  $table
    * @return Nahl\Providers\QueryBuilder
    */
    public function table($table)
    {
        $processor = $this->getPostProcessor();

        $query = new QueryBuilder($this, $this->getQueryGrammar(), $processor);

        return $query->from($table);
    }
}
