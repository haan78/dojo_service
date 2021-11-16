db = new Mongo().getDB("dojo");
db.createCollection("kullanici", { collation: { locale: "tr@collation=search" } });
db.getCollection('kullanıcı').createIndex({ "name": 1 }, { unique: true });
db.createCollection("uye", { collation: { locale: "tr@collation=search" } });


db.system.js.save({
    _id: "uyeListesi",
    value: function (active,limit) {
        var _active = ( typeof active == "undefined" ? true : active );
        var _limit = ( typeof limit == "undefined" ? 1000 : $limit );

        var d = ISODate();
        d.setMonth(d.getMonth() - 3);
        var res = db.getCollection('uye').aggregate([
            { $match: { active: _active } },
            {
                $project: {
                    ad: 1,
                    email: 1,
                    ekfno: 1,
                    cinsiyet: 1,
                    dogum: 1,
                    ogrenci: 1,
                    keikolar: {
                        $size: {
                            $filter: {
                                input: { $ifNull: ["$keikolar", []] },
                                as: "k",
                                cond: {
                                    $gte: ["$$k", d]
                                }
                            }
                        }
                    }
                }
            },
            { $limit: _limit }
        ]);
        return res;
    }
});