<?php

namespace Nahl;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Str;

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
    protected $table_alias;

    /**
     * The relations to queries.
     *
     * @var array
     */
    static protected $relations = [];

    /**
     * The fields to queries.
     *
     * @var array
     */
    static protected $fields = [];

    /**
     * The map results.
     *
     * @var array
     */
    static protected $maps = [];

    function __get($var) {
	    return static::$$var;
	}

	function __set($var) {
	    static::$$var = $var;
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
    		$maps['relations'] = $this->relations;
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
			$maps['relations'][$krel] = $rel;
		}

        $this->relations = $maps['relations'] ?: [];
    	$this->maps = $maps;
    }

    public function setMapFields($maps=[]) {
        if(!isset($maps['fields'])) {
            //todo get from attributes
        }

        $this->fields = $maps['fields'] ?: [];
        $this->maps = $maps;
    }

    public function getMapFields() {
        return $this->maps['fields'];
    }

    public function setMaps($maps=[]) {
    	$this->setMapTable($maps);
        $this->setMapRelations($maps);
    	$this->setMapFields($maps);
    	$this->maps = $maps;
    }

   	public function getMaps() {
   		return $this->setMaps($this->maps);
   	}

   	public static function collect($params, $type='paginated') {
   		$method = $type."Map";
   		return static::query()->$method($params);
   	}

   	/**
     * Overide method newEloquentBuilder.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }
}
