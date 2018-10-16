<?php 
namespace Zahirlib\Eloquent\Providers\Connections;

use Illuminate\Database\SQLiteConnection as BaseConnection;
use Zahirlib\Eloquent\Providers\QueryBuilder;

class SQLiteConnection extends BaseConnection {
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
