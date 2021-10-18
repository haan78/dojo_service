<?php 

namespace MongoTools {
    class Cast {
        public static string $DATE_TIME_FORMAT = 'Y-m-d H:i:s';
        public static function toUTCDateTime(\DateTime $dt)  : \MongoDB\BSON\UTCDateTime {
            return new  \MongoDB\BSON\UTCDateTime($dt);
        }

        public static function toDateTime(\MongoDB\BSON\UTCDateTime $mdt) : \DateTime {
            $dt = $mdt->toDateTime();
            $tz = date_default_timezone_get();
            $dt->setTimeZone(new \DateTimeZone($tz));
            return $dt;
        }

        private static function convert($v) {
            if ( $v instanceof  \MongoDB\Model\BSONDocument) {
                return self::toObject($v);
            } elseif ( $v instanceof \MongoDB\BSON\ObjectId ) {
                return $v->__toString();
            } elseif ( $v instanceof \MongoDB\BSON\UTCDateTime ) {
                return self::toDateTime($v)->format(self::$DATE_TIME_FORMAT);
            } elseif ( $v instanceof \MongoDB\Model\BSONArray ) {
                return self::toArray($v);
            } else {
                return $v;
            }
        }

        public static function toArray(\MongoDB\Model\BSONArray $arr) : array {
            $result = [];
            for ( $i=0; $i < count($arr); $i++ ) {
                array_push($result,self::convert($arr[$i]));
            }
            return $result;
        }

        public static function toObject(\MongoDB\Model\BSONDocument $doc) : object {
            $row = (array)$doc;
            foreach( $row as $k => $v ) {                
                $row[$k] = self::convert($v);
            }            
            return (object)$row;
        }

        public static function toTable(\MongoDB\Driver\Cursor $cursor, ?callable $fnc = null) : array {
            $list = [];
            $it = new \IteratorIterator($cursor);
            $it->rewind();
            while ($doc = $it->current()) {
                if ( !is_null($fnc) ) {
                    array_push($list, $fnc($doc));
                } else {
                    array_push($list, self::convert($doc));
                }                
                $it->next();
            }
            return $list;
        }

        public static function toList(\MongoDB\Driver\Cursor $cursor, ?callable $fnc = null) : array {
            $list = [];
            $it = new \IteratorIterator($cursor);
            $it->rewind();
            while ($doc = $it->current()) {
                $row = (array)$doc;
                if ( isset($row["_id"]) && $row["_id"] instanceof \MongoDB\BSON\ObjectId )  {                    
                    $row["_id"] = $row["_id"]->__toString();
                }
                if ( !is_null($fnc) ) {
                    array_push($list, $fnc($row));
                } else {
                    array_push($list, $row);
                }                
                $it->next();
            }
            return $list;
        }

		public static function toRegex(string $regex,string $flags = "") : \MongoDB\BSON\Regex  {
            return new \MongoDB\BSON\Regex($regex,$flags);
        }
		
        public static function toObjectId(string $_id) : \MongoDB\BSON\ObjectId {
            return new \MongoDB\BSON\ObjectId( trim($_id) );
        }

        public static function toClient(string $connectionString) : \MongoDB\Client {
            return new \MongoDB\Client($connectionString);
        }
    }

    class Get {
        public static function matchedCount(\MongoDB\UpdateResult $result) : int {
            return $result->getMatchedCount();
        }
    }
}