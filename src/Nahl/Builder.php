<?php

namespace Nahl;

use Illuminate\Database\Eloquent\Builder as BaseBuilder;
use Nahl\Exceptions\NotFoundException;
use Nahl\Mapper;

class Builder extends BaseBuilder {
    public static $pagination = [
        'default' => 10,
        'max' => 100,
    ];

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    public static $operators = [
        '$ne' => '<>',
        '$gt' => '>',
        '$gte' => '>=',
        '$lte' => '<=',
        '$lt' => '<',
        '$like' => 'like',
        '$nlike' => 'not like',
        '$in' => 'in',
        '$nin' => 'not in',
        '$regexp' => 'regexp',
        '$nregexp' => 'not regexp',
    ];

    /**
     * Exclude for column filters.
     *
     * @var array
     */
    public static $except = [
        'fields',
        'sort',
        'page',
        'per_page',
        'or',
        'search',
        'count',
        'min',
        'max',
        'avg',
        'sum',
    ];

    public $params = [];

    /**
     * Specifies a set of params.
     *
     * @param  array $params
     */
    public function setParams($params=[])
    {
        $ret['fields'] = isset($params['fields']) ? $params['fields'] : [];
        $ret['sorts'] = isset($params['sort']) ? $params['sort'] : [];
        $ret['page'] = (isset($params['page']) && is_numeric($params['page'])) ? (int)$params['page'] : 1;
        $ret['per_page'] = (isset($params['per_page']) && is_numeric($params['per_page'])) ? (int)$params['per_page'] : get_class($this)::$pagination['default'];
        $filters = [];
        foreach ($params as $key => $value) {
            if (!in_array(strtolower($key), get_class($this)::$except)) {
                $filters[$key] = $value;
            }
        }
        $ret['filters'] = $filters;
        $ret['or'] = isset($params['or']) ? $params['or'] : [];
        $ret['search'] = isset($params['search']) ? $params['search'] : [];
        $aggregates = [];
        foreach ($params as $key => $value) {
            if (in_array(strtolower($key), ['count', 'min', 'max', 'avg', 'sum'])) {
                $aggregates[$key] = $value;
            }
        }
        $ret['aggregates'] = $aggregates;

        $this->params = $ret;
    }

    public function getParams() {
        return $this->params;
    }

    public function singleMap($request=[]) {
        $res = $this->multiMap($request);
        return ($res) ? $res[0] : [];
    }

    public function lookupMap($request=[]) {
        $res = $this->multiMap($request);
        if($res) {
            return $res[0];
        } else {
            throw new NotFoundException($this->params['filter']);
        }
    }

    public function paginatedMap($request=[]) {
        $res = $this->multiMap($request);
        $count = $this->countMap($request);
        return $this->getPagination($count,$res,$request);
    }

    public function multiMap($request=[]) {
        $this->setParams($request);
        $query = $this->query;
        $subquery = $this->getSubQuery($query);
        
        $query = $query->table($this->model->table)
            ->fromSub($subquery." as ".$this->model->table_alias);

        if(isset($this->model->relations)) {
            foreach ($this->model->relations as $rel) {
                $this->getJoin($query,$rel);
            }
        }       

        return Mapper::getMapResult($query, $this->model->getMapTable(), $this->model->getMapFields()); 
    }

    public function countMap($request=[]) {
        $this->setParams($request);
        $query = $this->query;
        $query = $this->getSubQuery($query);
        return $this->getCountQuery($query);
    }

    protected function getSubQuery($query) {
        $query = $query->table($this->model->table." as ".$this->model->table_alias);

        if(isset($this->model->relations)) {
            foreach ($this->model->relations as $rel) {
                if($rel['type'] == 'inner'){
                    $this->getJoin($query,$rel);
                }
            }
        }
        return $query;
    }

    /**
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  array $map
     * @param  array $params
     * @param  boolean $allowed_raw
     */
    public static function getFields($query=[], $map=[], $params=[],$allowed_raw=false)
    {
        if (count($query)>0) {
            $ret = [];
            if (count($params)>0) {
                $fields = Convert::arrayToDot($params, self::$operators);
                foreach (array_keys($fields) as $field) {
                    if(!isset($map[$field]['value'])){
                        continue;
                    } else {
                        $query->addSelect($map[$field]['value'] . ' as ' . $map[$field]['value']);
                    }
                }
                if($allowed_raw) {
                    foreach ($map as $key => $value) {
                        if (isset($value['raw'])) {
                            $query->selectRaw($value['raw'] . ' as ' . $value['value']);
                        }
                    }    
                }
            } else {
                foreach ($map as $key => $value) {
                    if (isset($value['is_hide']) && $value['is_hide']) {
                        continue;
                    } else if (isset($value['raw'])) {
                        $query->selectRaw($value['raw'] . ' as ' . $value['value']);
                    } else {
                        $query->addSelect($value['value'] . ' as ' . $value['value']);
                    }
                }
            }
        }
    }

    /**
     * Specifies a set of logical conditions to match with "and" condition.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $total
     * @param  array $map
     * @param  array $params
     */
    public static function getWhere($query=[], $map=[], $params=[], $type='and')
    {
        if (count($query)>0) {
            if (count($params)>0) {
                if (count($map)>0) {
                    if ($type=='and') {
                        $filters = [];
                        $filters = Mapper::getMapWhere($map, $params, self::$operators);
                        foreach ($filters as $filter) {
                            $query->where($filter[0], $filter[1], $filter[2]);
                        }
                    } else if ($type=='or') {
                        foreach ($params as $or_params) {
                            $filters = [];
                            $filters = Mapper::getMapWhere($map, $or_params, self::$operators);
                            $query->where(function ($query) use ($filters) {
                                foreach ($filters as $or_filter) {
                                    $query->where($or_filter[0], $or_filter[1], $or_filter[2], 'or');
                                }
                            });
                        }
                    }
                } else {
                    if (isset($params['raw'])) {
                        $query->whereRaw($params['raw']);
                    }
                    if (isset($params['or'])){
                        $query->where(function ($query) use ($params) {
                            foreach ($params['or'] as $or) {
                                $query->where($or[0], $or[1], $or[2], 'or');
                            }
                        });
                    }
                    $where_params = [];
                    foreach ($params as $temp) {
                        if (is_array($temp)) {
                            $param_temp = [];
                            foreach ($temp as $key => $value) {
                                if (!is_array($value)) {
                                    $param_temp[$key] = $value;
                                }
                            }
                            if (count($param_temp)>0) {
                                $where_params[] = $param_temp;
                            }
                        }
                    }
                    foreach ($where_params as $where) {
                        if (isset($where['raw'])) {
                            $query->whereRaw($where['raw']);
                        }
                        if (isset($where[0])) {
                            $query->where($where[0], $where[1], $where[2]);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  array $map
     * @param  array $params
     */
    public static function getOrder($query=[], $map=[], $params=[])
    {
        if (count($query)>0) {
            if (count($map)>0) {
                $ret = [];
                if (count($params)>0) {
                    $sorts = Convert::arrayToDot($params, self::$operators);
                    foreach ($sorts as $sort => $value) {
                        if(!isset($map[$sort])){
                            continue;
                        } else {
                            $ret[$map[$sort]['value']] = ($value==-1 || $value=='desc') ? 'desc' : 'asc';
                        }
                    }
                }
                foreach ($ret as $column => $direction) {
                    $query->orderBy($column, $direction);
                }
            } else {
                foreach ($params as $param => $order) {
                    if(is_array($order)) {
                        foreach ($order as $column => $direction) {
                            $query->orderBy($column, $direction);
                        }
                    } else {
                        $query->orderBy($param, $order);
                    }
                }            
            }
        }
    }

    /**
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  array $table
     */
    public static function getJoin($query=[], $table=[])
    {
        if (count($query)>0) {
            $query->join($table['table'].' as '.$table['as'], function ($join) use ($table) {
                if (isset($table['first'])) {
                    $join->on($table['first'], $table['operator'], $table['second']);
                }
                if (isset($table['on'])) {
                    if (is_array($table['on'][0])) {
                        foreach ($table['on'] as $on) {
                            if(isset($on[3]) && $on[3]=='or') {
                                $join->orOn(DB::raw($on[0]), $on[1], DB::raw($on[2]));
                            } else {
                                $join->on(DB::raw($on[0]), $on[1], DB::raw($on[2]));
                            }
                        }
                    } else {
                        $join->on(DB::raw($table['on'][0]), $table['on'][1], DB::raw($table['on'][2]));
                    }
                }
            },null,null,$table['type']);
        }
    }

    protected function getCountQuery($query) {
        $count = 0;

        if (count($query)>0) {
            $count_obj = $query->selectRaw('count(DISTINCT('.$this->model->maps['table']['primary_key'].')) as count')->first();
            $count = $count_obj->count;
        }

        return $count;
    }

    public function getPagination($count, $data, $request)
    {
        $params = $this->getParams();
        $request_data = is_array($request) ? $request : $request->all();
        $first = $previous = $next = $last = $this->cleanQueryUrl($request_data);
        $url = is_array($request) ? "" : $request->url();

        $first = array_merge($first, ['page' => 1, 'per_page' => $params['per_page']]);

        $previous = array_merge($previous, ['page' => (int)$params['page']-1, 'per_page' => $params['per_page']]);

        $next = array_merge($next, ['page' => (int)$params['page']+1, 'per_page' => $params['per_page']]);

        $last = array_merge($last, ['page' => ceil($count/$params['per_page']), 'per_page' => $params['per_page']]);

        $return = [
            'count' => $count,
            'page_context' => [
                'page' => $params['page'],
                'per_page' => $params['per_page'],
                'total_pages' => ceil($count/$params['per_page']),
            ],
            'links' => [
                'first' => $url . '?' . urldecode(http_build_query($first)),
                'previous' => $params['page']==1 ? null : $url . '?' . urldecode(http_build_query($previous)),
                'next' => $params['page']==ceil($count/$params['per_page']) ? null : $url . '?' . urldecode(http_build_query($next)),
                'last' => $url . '?' . urldecode(http_build_query($last)),
            ],
            'results' => $data,
        ];

        return $return;
    }

    public static function cleanQueryUrl($param)
    {
        foreach ($param as $key => $value) {
            if(gettype($key) == "integer" || $key == 'page' || $key == 'per_page'){
                unset($param[$key]);
            }
        }
        return $param;
    }
}
