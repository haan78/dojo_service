db.getCollection('uye').aggregate([
    {
        $lookup:{
            from:"gelirgider",
            localField: "_id",
            foreignField: "uye_id",
            pipeline:[
                { 
                    $match:{ 
                        $expr: { $eq: [ "$tur","GELIR" ] }
                    } 
                }
            ],
            as:"odentiler"
        }
    },
    {
        $project:{
            _id:1,
            ad:1,
            email:1,
            ekfno:1,
            ogrenci:1,
            active:1,
            dogum:1,
            sinavlar:1,
            odemesay:{ $size:"$odentiler" },
        }
    }
])