<?php

namespace pgClass {

    use DateTime;

    class pgTypeRange implements pgTypeBase
    {

        public const RANGE_TYPE_INT = 1;
        public const RANGE_TYPE_BIG = 2;
        public const RANGE_TYPE_NUM = 3;
        public const RANGE_TYPE_TS = 4;
        public const RANGE_TYPE_TSZ = 5;
        public const RANGE_TYPE_DATE = 6;

        private $min;
        private $max;
        private bool $inclusiveMinBound = true;
        private bool $inclusiveMaxBound = true;
        private int $rangeType = self::RANGE_TYPE_INT;
        public function __construct(int $rangeType, $min, $max, bool $minb = true, bool $maxb = false)
        {
            $this->min = $min;
            $this->max = $max;
            $this->inclusiveMinBound = $minb;
            $this->inclusiveMaxBound = $maxb;
            $this->rangeType = $rangeType;
        }

        private function tsformat($v, $format)
        {
            if (is_string($v)) {
                return $v;
            } elseif ($v instanceof DateTime) {
                return $v->format($format);
            } else {
                throw new pgException("Unknown data type for TS range");
            }
        }

        public function load(string $data): void
        {
            $str = trim($data);
            $fp = substr($str, 0, 1);
            $lp = substr($str, -1);
            $str = substr($str, 1, -1);
            $arr = explode(",", str_replace("\"", "", $str));
            $this->inclusiveMinBound = ($fp == "[" ? true : false);
            $this->inclusiveMaxBound = ($lp == "]" ? true : false);
            switch ($this->rangeType) {
                case self::RANGE_TYPE_INT:
                    $this->min = intval($arr[0]);
                    $this->max = intval($arr[1]);
                    break;
                case self::RANGE_TYPE_BIG:
                    $this->min = intval($arr[0]);
                    $this->max = intval($arr[1]);
                    break;
                case self::RANGE_TYPE_NUM:
                    $this->min = floatval($arr[0]);
                    $this->max = floatval($arr[1]);
                    break;
                case self::RANGE_TYPE_TS:
                    $this->min = DateTime::createFromFormat("Y-m-d H:i:s", $arr[0]);
                    $this->max = DateTime::createFromFormat("Y-m-d H:i:s", $arr[1]);
                    break;
                case self::RANGE_TYPE_TSZ:
                    $this->min = DateTime::createFromFormat("Y-m-d H:i:sP", $arr[0]);
                    $this->max = DateTime::createFromFormat("Y-m-d H:i:sP", $arr[1]);
                    var_dump($arr[0]);
                    var_dump($this->min);
                    break;
                case self::RANGE_TYPE_DATE:
                    $this->min = DateTime::createFromFormat("Y-m-d", $arr[0]);
                    $this->max = DateTime::createFromFormat("Y-m-d", $arr[1]);
                    break;
            }
        }

        public final function serialize(): string
        {
            $rt = "";
            $min = "";
            $max = "";
            $minb = ($this->inclusiveMinBound ? "[" : "(");
            $maxb = ($this->inclusiveMaxBound ? "]" : ")");
            switch ($this->rangeType) {
                case self::RANGE_TYPE_INT:
                    $rt = "int4range";
                    $min = intval($this->min);
                    $max = intval($this->max);
                    break;
                case self::RANGE_TYPE_BIG:
                    $rt = "int8range";
                    $min = intval($this->min);
                    $max = intval($this->max);
                    break;
                case self::RANGE_TYPE_NUM:
                    $rt = "numrange";
                    $min = floatval($this->min);
                    $max = floatval($this->max);
                    break;
                case self::RANGE_TYPE_TS:
                    $rt = "tsrange";
                    $min = $this->tsformat($this->min, "Y-m-d H:i:s");
                    $max = $this->tsformat($this->max, "Y-m-d H:i:s");
                    break;
                case self::RANGE_TYPE_TSZ:
                    $rt = "tstzrange";
                    $min = $this->tsformat($this->min, "Y-m-d H:i:sP");
                    $max = $this->tsformat($this->max, "Y-m-d H:i:sP");
                    break;
                case self::RANGE_TYPE_DATE:
                    $rt = "daterange";
                    $min = $this->tsformat($this->min, "Y-m-d");
                    $max = $this->tsformat($this->max, "Y-m-d");
                    break;
            }
            return "'$minb$min,$max$maxb'::$rt";
        }
    }
}
