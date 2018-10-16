<?php 
namespace Zahirlib\Eloquent\Providers\Connections;

use Illuminate\Database\PostgresConnection as BaseConnection;
use Zahirlib\Eloquent\Providers\QueryBuilder;

class PostgresConnection extends BaseConnection {
    /**
    * Begin a fluent query against a database table.
    *
    * @param  string  $table
    * @return Zahirlib\Eloquent\Providers\QueryBuilder
    */
    public function table($table)
    {
        $processor = $this->getPostProcessor();

        $query = new QueryBuilder($this, $this->getQueryGrammar(), $processor);

        return $query->from($table);
    }
}
