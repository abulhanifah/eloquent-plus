<?php

namespace Zahirlib\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Str;
use Zahirlib\Eloquent\Providers\QueryBuilder;
use Zahirlib\Eloquent\Exceptions\PermissionException;
use Zahirlib\Eloquent\Exceptions\ValidationException;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
abstract class Model extends BaseModel {
	/**
     * The table alias associated with the model.
     *
     * @var string
     */
    static protected $table_alias;

    /**
     * The relations to queries.
     *
     * @var array
     */
    static protected $table_relations = [];

    /**
     * The fields to queries.
     *
     * @var array
     */
    static protected $fields = [];


    /**
     * The validaation rules.
     *
     * @var array
     */
    static protected $rules = [];

    /**
     * The map results.
     *
     * @var array
     */
    static protected $maps = [];

    function __get($var) {
	    if(isset(static::$$var)) {
            return static::$$var;
        } else {
            return $this->$var;
        }
	}

	function __set($name,$value) {
        static::$$name = $value;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Begin querying the model.
     *
     * @return Zahirlib\Eloquent\Providers\QueryBuilder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return Zahirlib\Eloquent\Providers\QueryBuilder
     */
    public function newQuery()
    {
        $builder = $this->newQueryWithoutScopes();

        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }

        return $builder;
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return Zahirlib\Eloquent\Providers\QueryBuilder|static
     */
    public function newQueryWithoutScopes()
    {
        $builder = $this->newEloquentBuilder($this->newBaseQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        return $builder->setModel($this)
                    ->with($this->with)
                    ->withCount($this->withCount);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return Zahirlib\Eloquent\Providers\QueryBuilder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        return new QueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    protected function getInitial($str) {
		$snake = Str::snake($str);
		$res = "";
		foreach (explode("_", $snake) as $s) {
			$res .= substr($s, 0, 1);
		}
		return $res;
    }

    protected function getAlias() {
    	if(!isset($this->table_alias)) {
    		$this->table_alias = $this->getInitial($this->table);
    	}
    	return $this->table_alias;
    }

    public function setMapTable($maps=[]) {
    	if(!isset($maps['table'])) {
    		$maps['table'] = ['name' => $this->table, 'as' => $this->getAlias(), 'primary_key' => $this->getKey(), 'key' => $this->getKey()];
    	} else {
    		$table = $maps['table'];
    		if(!isset($table['name'])) {
    			$table['name'] = $this->table;
    		} else {
                $this->table = $table['name'];
            }
    		if(!isset($table['as'])) {
    			$table['as'] = $this->getAlias();
    		} else {
    			$this->table_alias = $table['as'];
    		}
    		if(!isset($table['primary_key'])) {
    			$table['primary_key'] = $this->getKey();
    		}
    		if(!isset($table['key'])) {
    			$table['key'] = $this->getKey();
    		}
    		$maps['table'] = $table;
    	}
    	$this->maps = $maps;
    }

    public function getMapTable() {
        return $this->maps['table'];
    }

    public function setMapRelations($maps=[]) {
        if(!isset($maps['relations'])) {
            $maps['relations'] = $this->table_relations;
        }

        foreach ($maps['relations'] as $krel => $rel) {
            if(!isset($rel['table']) && isset($rel['model'])) {
                $n = new $rel['model'];
                $rel['table'] = $n->table;
            }
            if(!isset($rel['as'])) {
                $rel['as'] = $this->getInitial($rel['table']);
            }
            if(!isset($rel['on'])) {
                if(!isset($rel['foreign_key']) && isset($rel['model'])) {
                    $n = new $rel['model'];
                    $rel['foreign_key'] = $rel['as'].".".$n->getKey();
                }
                if(!isset($rel['local_key'])) {
                    $rel['local_key'] = $this->getAlias().".".$this->getKey();
                }
                $rel['on'] = [[$rel['foreign_key'],'=',$rel['local_key']]];
            } else {
                reset($rel['on']);
                $first_key = key($rel['on']);
                if(gettype($first_key) != 'integer') {
                    $rel['on'] = [$rel['on']];
                }
            }
            if(!isset($rel['type'])) {
                $rel['type'] = 'hasMany';
            }
            $maps['relations'][$krel] = $rel;
        }
        if(isset($maps['relations'])) {
            $this->table_relations = $maps['relations'];
        }
        $this->maps = $maps;
    }

    public function setMapFields($maps=[]) {
        if(!isset($maps['fields'])) {
            $columns = $this->newQuery()->getConnection()->getSchemaBuilder()->getColumnListing($this->table);
            $fields = [];
            foreach ($columns as $col) {
                $fields[$col] = ['name' => $this->table_alias.'.'.$col,'type' => 'string'];
            }
            $maps['fields'] = $fields;
        }

        if(isset($maps['fields'])) {
            $rules = array_merge([],(isset($maps['rules']) ? $maps['rules'] : []));
            foreach ($maps['fields'] as $key => $value) {
                if(isset($value['validate'])) {
                    $rules['POST'][$key] = $value['validate'];
                    $rules['PUT'][$key] = "required|".$value['validate'];
                    $rules['PATCH'][$key] = $value['validate'];
                }
            }
            $this->fields = $maps['fields'];
            $this->rules = $rules;
        }

        $this->maps = $maps;
    }

    public function getMapFields() {
        return $this->maps['fields'];
    }

    public function getMapRules($method) {
        return $this->maps['fields'][$method] ?: [] ;
    }

    public function setMaps($maps=[]) {
    	$this->setMapTable($maps);
        $this->setMapRelations($maps);
        $this->setMapFields($maps);
    }

   	public function getMaps() {
   		return $this->setMaps($this->maps);
   	}

   	public static function collect($maps,$params=[], $type='paginated') {
   		(new static())->setMaps($maps);
        $method = $type."Map";
   		return static::query()->$method($params);
   	}

    public static function validate($maps,$params,$method) {
        (new static())->setMaps($maps);
        ValidationException::validate($params,$this->getMapRules($method));
    }

    public static function authorize($roles,$user_roles,$filter=[]) {
        PermissionException::checkPermission($roles,$user_roles,$filter);
    }
}
