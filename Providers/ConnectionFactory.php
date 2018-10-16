<?php

namespace Zahirlib\Eloquent\Providers;

use InvalidArgumentException;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use Zahirlib\Eloquent\Providers\Connections\MySqlConnection;
use Zahirlib\Eloquent\Providers\Connections\SQLiteConnection;
use Zahirlib\Eloquent\Providers\Connections\PostgresConnection;
use Zahirlib\Eloquent\Providers\Connections\SqlServerConnection;

class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * Create a new connection instance.
     *
     * @param  string   $driver
     * @param  \PDO|\Closure     $connection
     * @param  string   $database
     * @param  string   $prefix
     * @param  array    $config
     * @return \Illuminate\Database\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $prefix, $config);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [$driver]");
    }
}
