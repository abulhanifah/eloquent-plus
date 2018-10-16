<?php

namespace Zahirlib\Eloquent;

use \DateTime;
use \RecursiveArrayIterator;
use \RecursiveIteratorIterator;

class Convert
{
    public static function createStringUUID()
    {
        $ret = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), // TODO: replace with ipv4

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        return $ret;
    }

    public static function isValidUUID($value)
    {
        return preg_match('/^[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}$/i', $value) === 1;
    }

    public static function stringToUUID($value)
    {
        if (self::isValidUUID($value)) {
            $clean = str_replace(array('-','{','}'), '', $value);
            $return = pack('H*',$clean);
        } else {
            $return = $value;
        }
        return $return;
    }

    public static function uuidToString($value)
    {
        if (strlen($value) == 16){
            $clean = implode('',unpack('H*', $value));
            $uuid = substr($clean, 0,8) . '-' . substr($clean, 8,4) . '-' . substr($clean, 12,4) . '-' . substr($clean, 16,4) . '-' . substr($clean, 20);
            $ret = strtolower(trim($uuid));
        } else {
            $ret = $value;
        }
        return $ret;
    }

    public static function booleanToInteger($value)
    {
        return ($value=='true')? 1 : 0;
    }

    public static function booleanToString($value)
    {
        return ($value=='true' || $value==true)? 'T' : 'F';
    }

    public static function integerToBoolean($value)
    {
        if ($value==1) {
            $ret = true;
        } else if ($value==0) {
            $ret = false;
        } else {
            $ret = $value;
        }
        return $ret;
    }

    public static function stringToBoolean($value)
    {
        if ($value=='T') {
            $ret = true;
        } else if ($value=='F') {
            $ret = false;
        } else {
            $ret = $value;
        }
        return $ret;
    }

    public static function stringToInteger($value)
    {
        return (is_numeric($value))? (int)$value : $value;
    }

    public static function stringToNumber($value)
    {
        return (is_numeric($value))? (float)$value : $value;
    }

    public static function isValidDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public static function stringToDate($value)
    {
        return date("Y-m-d", strtotime($value));
    }

    public static function stringToDateTime($value)
    {
        return date("Y-m-d H:i:s", strtotime($value));
    }

    public static function stringToISODate($value)
    {
        return date("c", strtotime($value));
    }

    public static function trimValue($value)
    {
        $res = [];
        foreach ($value as $obj) {
            foreach ($obj as $k => $v) {
                $obj->$k = trim($v);
            }
            $res[] = $obj;
        }

        return $res;
    }

    public static function stringToJsonArray($value)
    {
        return json_decode(stripslashes($value));
    }

    public static function arrayToJsonString($value)
    {
        return addslashes(json_encode($value));
    }

    /**
     * Convert multidimensional array to dot notation.
     *
     * @param  array $params
     */
    public static function arrayToDot($params, $operators = [],$isdebug=false) 
    {
        $ritit = new RecursiveIteratorIterator(new RecursiveArrayIterator($params));
        $res = [];
        foreach ($ritit as $leafValue) {
            $keys = [];
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }
            if (isset($operators[end($keys)])) {
                $key = array_slice($keys, 0, -1);
                $res[ join('.', $key) ][trim($operators[end($keys)])] = $leafValue;
            } else {
                $res[ join('.', $keys) ] = $leafValue;
            }
        }
        return $res;
    }

    /**
     * Convert dot notation to multidimensional array.
     *
     * @param  array $params
     * @param  array $ancestors
     * @param  mixed $value
     */
    public static function dotToArray(array &$arr, array $ancestors, $value)
    {
        $current = &$arr;
        foreach ($ancestors as $key) {

            if (!is_array($current)) {
                $current = array( $current);
            }

            if (!array_key_exists($key, $current)) {
                $current[$key] = array();
            }
            $current = &$current[$key];
        }

        $current = $value;
    }

    public static function zEncrypt($p, $lvl = 2)
    {
        for ($i = 0; $i <= $lvl - 1; $i++) {
            $p = base64_encode($p);
        }
        return $p;
    }

    public static function zDecrypt($p, $lvl = 2)
    {
        for ($i=0; $i<=$lvl-1; $i++) {
            $p =  base64_decode($p);
        }
        return $p;
    }

    /**
     * Convert value from db to expected type or expected type to db.
     *
     * @param  string $type
     * @param  string|number $value
     * @param  array $opts
     */
    public static function convert($type, $value, $opts=['is_from_db'=>true])
    {
        // from db
        if ($opts['is_from_db']) {
            if ($type=='uuid'||$type=='char') {
                $result = trim($value);
            } else if ($type=='uuidBinary') {
                $result = self::uuidToString($value);
            } else if ($type=='boolean') {
                $result = self::integerToBoolean($value);
            } else if ($type=='booleanString') {
                $result = self::stringToBoolean($value);
            } else if ($type=='integer') {
                $result = self::stringToInteger($value);
            } else if ($type=='double') {
                $result = self::stringToNumber($value);
            } else if ($type=='date') {
                $result = self::stringToDate($value);
            } else if ($type=='dateTime') {
                $result = self::stringToISODate($value);
            } else if ($type=='json') {
                $result = self::stringToJsonArray($value);
            } else if (isset($opts['charset']) && $opts['charset']=='ASCII') {
                $result = html_entity_decode($value);
            } else {
                $result = $value;
            }
        // to db
        } else {
            if ($type=='uuid'||$type=='char') {
                $result = trim($value);
            } else if ($type=='uuidBinary') {
                $result = self::stringToUUID($value);
            } else if ($type=='boolean') {
                $result = self::booleanToInteger($value);
            } else if ($type=='booleanString') {
                $result = self::booleanToString($value);
            } else if ($type=='integer') {
                $result = self::stringToInteger($value);
            } else if ($type=='double') {
                $result = self::stringToNumber($value);
            } else if ($type=='date') {
                $result = self::stringToDate($value);
            } else if ($type=='dateTime') {
                $result = self::stringToDateTime($value);
            } else if ($type=='json') {
                $result = self::arrayToJsonString($value);
            } else if (isset($opts['charset']) && $opts['charset']=='ASCII') {
                $result = preg_replace_callback(
                    '/[\x{80}-\x{10FFFF}]/u',
                    function ($m) {
                        $utf = iconv('UTF-8', 'UCS-4', current($m));
                        return sprintf("&#x%s;", ltrim(strtoupper(bin2hex($utf)), "0"));
                    }, 
                    $value);
            } else {
                $result = $value;
            }
        }
        return $result;
    }

    public static function getFirstNumber($prefix, $digit)
    {
        $ret = $prefix;
        if(strlen($prefix)<$digit) {
            $ret .= str_repeat('0', $digit-strlen($prefix)-1);
            $ret .= '1';
        } else {
            $ret = substr($ret, 1, $digit)+1;
        }
        return $ret;
    }
}
