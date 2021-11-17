#!/bin/sh
mongorestore --db=dojo --drop --archive=/docker-entrypoint-initdb.d/dojo.arc -u $MONGO_INITDB_ROOT_USERNAME -p $MONGO_INITDB_ROOT_PASSWORD --authenticationDatabase admin