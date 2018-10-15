<?php

namespace Nahl;

use Nahl\Convert;

class Mapper
{
    /**
     * Map data with actual column on the database.
     *
     * @param  array $map
     * @param  array $data
     * @param  array $opts
     */
    public static function map($data=[], $map=[], $opts=['is_from_db'=>true],$data_orig=[],$debug=false)
    {
        $result = [];

        if (isset($opts['prefix'])) {
            $map = self::mapByPrefix($map, $opts['prefix']);
        }
        $map_result = [];

        foreach ($map as $mp_key => $map_value) {
            if ($opts['is_from_db']) {
                if (isset($map_value['is_hide_empty']) && $map_value['is_hide_empty'] && (!isset($data[$map_value['value']]) || empty($data[$map_value['value']]))) {
                    continue;
                } else if (isset($map_value['is_allowed_null']) && $map_value['is_allowed_null'] && (!isset($data[$map_value['value']]) || empty($data[$map_value['value']]))) {
                    $tmpx = explode(".", $mp_key);
                    if(count($tmpx)>=2) array_pop($tmpx);
                    $mp_key_ = implode(".", $tmpx);

                    if(isset($map_result[$mp_key_])){
                        $map_result[$mp_key] = null;
                    } else {
                        $map_result[$mp_key_] = null;
                    }
                } else {
                    if(!isset($map_value['value']) && isset($map_value['map'])) {
                        $k_ = explode('.', $map_value['key']);
                        $detailKey_ = end($k_);

                        if(!$data_orig) $data_orig = $data;

                        if(isset($map_result[$detailKey_])) {
                            $map_result[$mp_key] = self::mapChildren($data_orig, $map_value['primary_key'], $map_result[$detailKey_], $map_value['map']);
                        }

                    } else if(!isset($data[$map_value['name']]) || (empty($data[$map_value['name']]) && $data[$map_value['name']]!="0")) {
                        $tmpx = explode(".", $mp_key);
                        if(count($tmpx)>=2) array_pop($tmpx);
                        $mp_key = implode(".", $tmpx);
                        $map_result[$mp_key] = null;
                    } else {
                        $map_result[$mp_key] = Convert::convert($map_value['type'], $data[$map_value['name']], $opts);
                        if(isset($map_value['charset']) && $map_value['charset']) {
                            $map_result[$mp_key] = html_entity_decode($map_result[$mp_key]);
                        }
                    }
                }
            } else {
                if(isset($opts['prefix_key'])) {
                    $mp_key = str_replace($opts['prefix_key'].".", "", $mp_key);
                }
                if (!isset($data[$mp_key])) {
                    continue;
                } else {
                    $map_result[$map_value['value']] = Convert::convert($map_value['type'], $data[$mp_key], $opts);
                    if(isset($map_value['charset']) && $map_value['charset']) {
                        $map_result[$map_value['value']] = htmlentities($map_result[$map_value['value']]);
                    }
                }
            }
        }

        $result = self::mapPrefix($map_result, $opts);

        return $result;
    }

    /**
     * Filter map by prefix
     *
     * @param  array $map
     * @param  string $prefix
     */
    public static function mapByPrefix($map=[], $prefix=null)
    {
        $res = [];
        foreach ($map as $key => $value) {
            $arr = explode(".", $value['value']);
            if($arr[0] == $prefix) {
                $res[$key] = $value;
            }
        }
        return $res;
    }

    /**
     *  Get map result from data array
     *
     * @param  array $data
     * @param  array $opts
     */
    public static function mapPrefix($data, $opts=[]) {
        $result = [];
        if (isset($opts['is_remove_prefix']) && $opts['is_remove_prefix']) {
            foreach ($data as $key => $value) {
                $k = explode('.', $key);
                $new_key = null;
                foreach (array_slice($k,1) as $dk => $dv) {
                    $new_key .= '.'.$dv;
                }
                $result[substr($new_key, 1)] = $value;
            }
        } else {
            $result = $data;
        }
        return $result;
    }

    /**
     *  Get map table
     *
     * @param  array $data
     * @param  array $map
     */
    public static function mapTable($table=[], $map=[])
    {
        $res = [];
        foreach ($map as $key => $value) {
            if (isset($value['is_array']) && $value['is_array']) {
                $k = explode('.', $key);
                $detailKey = null;
                foreach (array_slice($k,1) as $dk => $dv) {
                    $detailKey .= '.'.$dv;
                }
                $res[$k[0]]['primary_key'] = $table['primary_key'];
                $res[$k[0]]['key'] = $table['key'];
                
                if(isset($value['parent'])) {
                    $res[$k[0]]['map'] = self::multiArray($res[$k[0]]['map'],$k[0],substr($detailKey, 1),$map);
                } else {
                    $res[$k[0]]['map'][substr($detailKey, 1)] = $value;
                }
                $res['main'][$key] = null;
            } else {
                $res['main'][$key] = $value;
            }
        }
        return $res;
    }

    /**
     *  Get map table multi array
     *
     * @param  array $data
     * @param  array $map
     */
    public static function multiArray($res=[],$parent,$key,$map=[]) {
        $k = explode('.', $key);
        $detailKey = null;
        foreach (array_slice($k,1) as $dk => $dv) {
            $detailKey .= '.'.$dv;
        }
        
        $parentObj = $map[$parent.".".$key]['parent'];
        if(is_array($parentObj)) {
            $res[$k[0]]['primary_key'] = $map[$parentObj[0]]['value'];
            $res[$k[0]]['key'] = $parentObj[0];
            $parentObj = array_slice($parentObj,1);
            if(count($parentObj)==1) {
                $map[$parent.".".$key]['parent'] = $parentObj[0];
            } else {
                $map[$parent.".".$key]['parent'] = $parentObj;
            }
            $k_ = explode('.', $detailKey);
            $detailKey_ = null;
            foreach (array_slice($k_,1) as $dk_ => $dv_) {
                $detailKey_ .= '.'.$dv_;
            }
            $res[$k[0]]['map'] = self::multiArray($res[$k[0]]['map'],$parent.".".$k[0],substr($detailKey_, 1),$map);
        } else {
            $res[$k[0]]['primary_key'] = $map[$parentObj]['value'];
            $res[$k[0]]['key'] = $parentObj;
            $res[$k[0]]['map'][substr($detailKey, 1)] = $map[$parent.".".$key];
        }
        
        return $res;
    }

    /**
     * Get map result from query (with details)
     *
     * @param  array $query
     * @param  array $opts
     */
    public static function getMapResult($query=[], $table=[], $map=[], $opts=[])
    {
        $res = Convert::trimValue($query->get());
        $data = self::mapResult($res, self::mapTable($table, $map)['main']);
        foreach ($data as $i => $v) {
            foreach (self::mapTable($table, $map) as $details => $map_detail) {
                if($details != "main") {
                    $data[$i][$details] = self::mapChildren($res, $map_detail['primary_key'], $v[$map_detail['key']], $map_detail['map']);
                }
            }
        }

        return self::uniqueMap($data);
    }

    /**
     *  Get map result from data array
     *
     * @param  array $data
     * @param  array $map
     */
    public static function mapResult($data=[], $map=[],$data_orig=[])
    {
        $res = [];
        foreach ($data as $d) {
            $res[] = self::map((array)$d, $map, ['is_from_db'=>true],$data_orig);
        }
        $ret = [];
        foreach ($res as $r) {
            $arr = [];
            foreach ($r as $key => $value) {
                $d = explode('.',$key);
                Convert::dotToArray($arr, $d, $value);
            }
            if($arr){
                $ret[] = $arr;
            }
        }

        return $ret;
    }

    /**
     *  Get details map result from data array
     *
     * @param  array $data
     * @param  string $key
     * @param  string $value
     * @param  array $map
     */
    public static function mapChildren($data, $key, $value, $map) {
        $result = [];
        if (gettype($data) == "object") {
            $data =  (array) $data;
            if ($data[$key] == $value) {
                $result = array_merge($result, self::mapResult([$data],$map));
            }
        } else {
            foreach ($data as $d) {
                if(is_object($d)) {
                    $d =  (array) $d;
                }
                if ($d[$key] == $value) {
                    $result = array_merge($result, self::mapResult([$d],$map,$data));
                }
            }
        }

        return self::uniqueMap($result);
    }

    /**
     * Get unique array
     *
     * @param  array $data
     */
    public static function uniqueMap($data=[])
    {
        $collection = collect($data);
        return $collection->unique()->values()->all();
    }

    /**
     *
     * @param  array $map
     * @param  array $params
     */
    public static function getMapWhere($map, $params, $operators)
    {
        $ret = [];
        if (count($params)>0) {
            $filters = Convert::arrayToDot($params, $operators);
            foreach ($filters as $filter => $value) {
                if(!isset($map[$filter]['value'])){
                    continue;
                } else if (is_array($value)) {
                    $column = $map[$filter]['value'];
                    $operator = array_keys($value)[0];
                    $where_value = Convert::convert($map[$filter]['type'], array_values($value)[0], $opts=['is_from_db'=>false]);
                } else {
                    $column = $map[$filter]['value'];
                    $operator = '=';
                    $where_value = Convert::convert($map[$filter]['type'], $value, $opts=['is_from_db'=>false]);
                }
                $ret[] = [$column, $operator, $where_value];
            }
        }
        return $ret;
    }

    public static function ddMap($table=[], $map=[])
    {
        $res = [];
        foreach ($map as $key => $value) {
            if ($value['type']=='uuid') {
                $val = Convert::createStringUUID();
            } else if ($value['type']=='boolean') {
                $val = true;
            } else if ($value['type']=='integer' || $value['type']=='double') {
                $val = 1;
            } else if ($value['type']=='dateTime') {
                $val = date('c');
            } else if ($value['type']=='date') {
                $val = date('Y-m-d');
            } else {
                $val = 'string';
            }
            $dd[$value['value']] = $val;
        }
        $res[] = $dd;
        $data = self::mapResult($res, self::mapTable($table, $map)['main']);
        foreach ($data as $i => $v) {
            foreach (self::mapTable($table, $map) as $details => $map_detail) {
                if($details != "main") {
                    $data[$i][$details] = self::mapChildren($res, $map_detail['primary_key'], $v[$map_detail['key']], $map_detail['map']);
                }
            }
        }

        return self::uniqueMap($data)[0];
    }
}
