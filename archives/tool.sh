#!/bin/sh
BASEDIR="$( cd "$( dirname "$0" )" && pwd )"

if [ "$#" -gt 1 ]
then
    arcf="$BASEDIR/$1.arc"
    if  [ "$2" = "backup" ]
    then
        
        rm -f $arcf

        mongodump --db=$1 --archive=$arcf -u $MONGO_INITDB_ROOT_USERNAME -p $MONGO_INITDB_ROOT_PASSWORD --authenticationDatabase admin
        if [ ! -f $arcf ]
        then
            echo "Backup file could not be created"
            exit 1
        fi
    elif [ "$2" = "restore" ]
    then
        if [ -f $arcf ]
        then
            mongorestore --db=$1 --drop --archive=$arcf -u $MONGO_INITDB_ROOT_USERNAME -p $MONGO_INITDB_ROOT_PASSWORD --authenticationDatabase admin
        else
            echo "Bacuk file not found ($arcf)"
            exit 1
        fi
    else
else
    echo "Database name and action(backup/restore) required"
    exit 1
fi

exit 0
