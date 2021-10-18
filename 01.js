db = new Mongo().getDB("dojo");
db.createCollection("user",{ collation:{ locale:"tr@collation=search" } });
db.getCollection('user').createIndex({ "true": 1 }, { unique: true });