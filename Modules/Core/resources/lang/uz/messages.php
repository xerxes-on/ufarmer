<?php

declare(strict_types=1);

return [
    'auth' => [
        'unauthorized' => 'Ruxsat berilmagan.',
    ],
    'area' => [
        'created' => 'Maydon muvaffaqiyatli yaratildi.',
        'updated' => 'Maydon muvaffaqiyatli yangilandi.',
        'deleted' => 'Maydon muvaffaqiyatli o\'chirildi.',
        'unit_m2' => 'm²',
        'unit_ha' => 'ga',
    ],
    'area_crop' => [
        'attached' => 'Ekin maydonga muvaffaqiyatli bog\'landi.',
        'updated' => 'Maydondagi ekin muvaffaqiyatli yangilandi.',
        'detached' => 'Ekin maydondan muvaffaqiyatli ajratildi.',
        'none_attached' => 'Bog\'lash uchun to\'g\'ri ekinlar yo\'q.',
        'activated' => 'Maydondagi ekin muvaffaqiyatli faollashtirildi.',
        'deactivated' => 'Maydondagi ekin muvaffaqiyatli o\'chirildi.',
        'not_found' => 'Ekin bu maydonga bog\'lanmagan.',
        'missing_crop' => 'Ekin topilmadi.',
        'inactive_crop' => 'Ekin topilmadi yoki faol emas.',
        'harvested' => 'Ekin muvaffaqiyatli yig\'ib olindi.',
        'already_harvested' => 'Bu ekin allaqachon yig\'ib olingan.',
        'insufficient_space' => 'Bo\'sh joy yetarli emas. Mavjud: :available ga, So\'ralgan: :requested ga',
        'exceeds_total' => 'Ajratilgan maydon umumiy maydondan oshib ketmasligi kerak.',
        'invalid_area' => 'Maydon 0 dan katta bo\'lishi kerak.',
    ],
    'region' => [
        'not_found' => 'Viloyat topilmadi.',
    ],
    'user_detail' => [
        'created' => 'Foydalanuvchi ma\'lumotlari muvaffaqiyatli yaratildi.',
        'updated' => 'Foydalanuvchi ma\'lumotlari muvaffaqiyatli yangilandi.',
        'deleted' => 'Foydalanuvchi ma\'lumotlari muvaffaqiyatli o\'chirildi.',
        'already_exists' => 'Foydalanuvchi ma\'lumotlari allaqachon mavjud. Yangilash uchun PUT dan foydalaning.',
        'not_found' => 'Foydalanuvchi ma\'lumotlari topilmadi.',
        'image_deleted' => 'Rasm muvaffaqiyatli o\'chirildi.',
        'city_mismatch' => 'Tanlangan shahar tanlangan viloyatga tegishli emas.',
        'no_image' => 'O\'chirish uchun rasm topilmadi.',
    ],
    'user' => [
        'not_found' => 'Foydalanuvchi topilmadi.',
    ],
    'nearby_users' => [
        'found' => ':radius km radiusda :count foydalanuvchi topildi.',
        'none_found' => 'Belgilangan radiusda foydalanuvchilar topilmadi.',
        'invalid_coordinates' => 'Noto\'g\'ri koordinatalar kiritilgan.',
    ],
];
