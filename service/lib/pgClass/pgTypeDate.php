<?php

namespace pgClass {
    use DateTime;

    use function PHPSTORM_META\type;

class pgTypeDate implements pgTypeBase
    {

        public const TYPE_DATE = 1;
        public const TYPE_DATETIME = 2;
        public const TYPE_TIMESTAMP = 3;
        public const TYPE_TIMESTAMP_TZ = 4; 
        public const TYPE_TIME = 5;

        private DateTime $dt;
        private int $type;
        public function __construct(DateTime $dt, int $type = self::TYPE_DATE)
        {
            $this->dt = $dt;
            $this->type = $type;
        }

        public static function create(string $date, int $type = self::TYPE_DATE): pgTypeDate
        {
            return new pgTypeDate(new DateTime($date), $type);
        }

        public function load(string $data) : void {
            switch ( $this->type ) {
                case self::TYPE_DATE :
                    $this->dt = DateTime::createFromFormat("Y-m-d",$data);
                    break;
                case self::TYPE_DATETIME :
                    $this->dt = DateTime::createFromFormat("Y-m-d H:i:s",$data);
                    break;
                case self::TYPE_TIMESTAMP :
                    $this->dt = DateTime::createFromFormat("Y-m-d H:i:s",$data);
                    break;
                case self::TYPE_TIME :
                    $this->dt = DateTime::createFromFormat("H:i:s",$data);
                    break;
                case self::TYPE_TIMESTAMP_TZ :
                    $this->dt = DateTime::createFromFormat("H:i:s",$data);
                    break;
                default:
                    $this->dt = DateTime::createFromFormat("Y-m-d",$data);
                    break;
            }
        }

        public function serialize(): string
        {
            switch($this->type) {
                case self::TYPE_DATE : 
                    return "'" . $this->dt->format('Y-m-d') . "'::date";
                    break;
                case self::TYPE_DATETIME : 
                    return "'" . $this->dt->format('Y-m-d H:i:s') . "'::datetime";
                    break;
                case self::TYPE_TIMESTAMP : 
                    return "'" . $this->dt->format('Y-m-d H:i:s') . "'::timestamp";
                    break;
                case self::TYPE_TIME : 
                    return "'" . $this->dt->format('H:i:s') . "'::time";
                    break;
                case self::TYPE_TIMESTAMP_TZ : 
                    return "'" . $this->dt->format('c') . "'::timestamptz";
                    break;
                default:
                    return "'" . $this->dt->format('Y-m-d') . "'";
                    break;
            }
            
        }
    }
}