db = new Mongo().getDB("dojo");
db.createCollection("user",{ collation:{ locale:"tr@collation=search" } });
db.getCollection('user').createIndex({ "name": 1 }, { unique: true });
db.createCollection("uye",{ collation:{ locale:"tr@collation=search" } });
db.getCollection('user').createIndex({ "email": 1 }, { unique: true });