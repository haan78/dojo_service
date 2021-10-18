<?php

namespace pgClass {

    use DateTime;

    class pgTool
    {

        public static function connection(string $cs)
        {
            $conn = pg_connect($cs, PGSQL_CONNECT_ASYNC);
            if ($conn !== FALSE) {
                $poll_outcome = PGSQL_POLLING_WRITING;

                while (true) {
                    $socket = [pg_socket($conn)]; // "Caution: do not assume that the socket remains the same across PQconnectPoll calls."
                    $null = [];

                    if ($poll_outcome === PGSQL_POLLING_READING) {
                        stream_select($socket, $null, $null, 5);
                        $poll_outcome = pg_connect_poll($conn);
                    } else if ($poll_outcome === PGSQL_POLLING_WRITING) {
                        stream_select($null, $socket, $null, 5);
                        $poll_outcome = pg_connect_poll($conn);
                    } else {
                        break;
                    }
                }
                if (pg_connection_status($conn) == PGSQL_CONNECTION_OK) {
                    return $conn;
                } else {
                    throw new pgException("Connection is bad!");
                }
            } else {
                throw new pgException("Connection failed!");
            }
        }

        private static function isAssoc(array $arr)
        {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        }

        public final static function eveluate($v): string
        {
            $str = "";
            if (is_bool($v)) {
                $str .= ($v == true ? '1' : '0') . "::bit";
            } elseif (is_int($v)) {
                $str .= "$v";
            } elseif (is_float($v)) {
                $str .= "$v";
            } elseif (is_double($v)) {
                $str .= "$v";
            } elseif (is_string($v)) {
                $str .=   "'".pg_escape_string($v)."'";
            } elseif (is_null($v)) {
                $str .= "NULL";
            } elseif (is_long($v)) {
                $str .= $v;
            } elseif ($v instanceof DateTime) {
                $str .= "'" . $v->format('Y-m-d H:i:s') . "'";
            } elseif (is_array($v)) {
                if (self::isAssoc($v)) {
                    $vars = array_values($v);
                    $str .= "ROW(";
                    for ($i = 0; $i < count($vars); $i++) {
                        if ($i > 0) {
                            $str .= ",";
                        }
                        $str .= self::eveluate($vars[$i]);
                    }
                    $str .= ")";
                } else {
                    $vars = $v;
                    $str .= "ARRAY[";
                    for ($i = 0; $i < count($vars); $i++) {
                        if ($i > 0) {
                            $str .= ",";
                        }
                        $str .= self::eveluate($vars[$i]);
                    }
                    $str .= "]";
                }
            } elseif ($v instanceof pgTypeBase) {
                $str .= $v->serialize();
            } else {
                $str .= "NULL";
            }
            return $str;
        }

        private static function pg_array_parse($s, $start = 0, &$end = null)
        {
            if (empty($s) || $s[0] != '{') return null;
            $return = array();
            $string = false;
            $quote = '';
            $len = strlen($s);
            $v = '';
            for ($i = $start + 1; $i < $len; $i++) {
                $ch = $s[$i];

                if (!$string && $ch == '}') {
                    if ($v !== '' || !empty($return)) {
                        $return[] = $v;
                    }
                    $end = $i;
                    break;
                } elseif (!$string && $ch == '{') {
                    $v = self::pg_array_parse($s, $i, $i);
                } elseif (!$string && $ch == ',') {
                    $return[] = $v;
                    $v = '';
                } elseif (!$string && ($ch == '"' || $ch == "'")) {
                    $string = true;
                    $quote = $ch;
                } elseif ($string && $ch == $quote && $s[$i - 1] == "\\") {
                    $v = substr($v, 0, -1) . $ch;
                } elseif ($string && $ch == $quote && $s[$i - 1] != "\\") {
                    $string = false;
                } else {
                    $v .= $ch;
                }
            }
            return $return;
        }

        public static function parse($data)
        {
            $val = null;
            if (is_string($data) && substr($data, 0, 1) == "{") {
                $val = [];
                $arr = self::pg_array_parse($data);
                for ($i = 0; $i < count($arr); $i++) {
                    array_push($val, self::parse($arr[$i]));
                }
            } elseif (is_string($data)  &&  substr($data, 0, 1) == "(") {
                $val = [];
                $str = "{" . substr($data, 1, -1) . "}";
                $arr = self::pg_array_parse($str);
                for ($i = 0; $i < count($arr); $i++) {
                    array_push($val, self::parse($arr[$i]));
                }
            } elseif (is_string($data)  &&  substr($data, 0, 1) == "[") {
                $str = substr($data, 1, -1);
                $val = explode(",", str_replace("\"", "", $str));
            } else {
                $val = $data;
            }
            return $val;
        }
    }
}
