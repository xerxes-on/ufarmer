<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Crops\Models\Crop;

class CropsSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            [
                'name' => ['uz' => "Bug'doy", 'ru' => 'Пшеница', 'en' => 'Wheat'],
                'description' => [
                    'uz' => "Bug'doy - asosiy oziq-ovqat mahsuloti, undan non, makaron va boshqa mahsulotlar tayyorlanadi.",
                    'ru' => 'Пшеница - основная продовольственная культура, из которой производят хлеб, макароны и другие продукты.',
                    'en' => 'Wheat is a staple food crop used to make bread, pasta, and other products.',
                ],
                'image' => "/crops/images/bug'doy.png",
            ],
            [
                'name' => ['uz' => 'Paxta', 'ru' => 'Хлопок', 'en' => 'Cotton'],
                'description' => [
                    'uz' => "Paxta - O'zbekistonning asosiy eksport mahsuloti, to'qimachilik sanoatining xom ashyosi.",
                    'ru' => 'Хлопок - основной экспортный продукт Узбекистана, сырье для текстильной промышленности.',
                    'en' => 'Cotton is a major export crop of Uzbekistan, raw material for the textile industry.',
                ],
                'image' => '/crops/images/cotton.png',
            ],
            [
                'name' => ['uz' => 'Sholi', 'ru' => 'Рис', 'en' => 'Rice'],
                'description' => [
                    'uz' => "Sholi - asosiy oziq-ovqat mahsuloti, sug'oriladigan yerlarda ekiladi.",
                    'ru' => 'Рис - основная продовольственная культура, выращивается на орошаемых землях.',
                    'en' => 'Rice is a staple food crop grown on irrigated lands.',
                ],
                'image' => '/crops/images/sholi.png',
            ],
            [
                'name' => ['uz' => 'Makkajo\'xori', 'ru' => 'Кукуруза', 'en' => 'Corn/Maize'],
                'description' => [
                    'uz' => "Makkajo'xori - oziq-ovqat va chorva uchun yem sifatida ishlatiladi.",
                    'ru' => 'Кукуруза - используется как продовольственная культура и корм для скота.',
                    'en' => 'Corn is used as food and livestock feed.',
                ],
                'image' => '/crops/images/makkajoxori.png',
            ],
            [
                'name' => ['uz' => 'Kartoshka', 'ru' => 'Картофель', 'en' => 'Potato'],
                'description' => [
                    'uz' => 'Kartoshka - ikkinchi non deb ataladigan muhim oziq-ovqat mahsuloti.',
                    'ru' => 'Картофель - важная продовольственная культура, называемая вторым хлебом.',
                    'en' => 'Potato is an important food crop, often called the second bread.',
                ],
                'image' => '/crops/images/potato.png',
            ],
            [
                'name' => ['uz' => 'Pomidor', 'ru' => 'Помидор', 'en' => 'Tomato'],
                'description' => [
                    'uz' => "Pomidor - sabzavot mahsuloti, yangi holda va qayta ishlangan holda iste'mol qilinadi.",
                    'ru' => 'Помидор - овощная культура, употребляется в свежем и переработанном виде.',
                    'en' => 'Tomato is a vegetable crop consumed fresh and processed.',
                ],
                'image' => '/crops/images/tomato.png',
            ],
            [
                'name' => ['uz' => 'Bodring', 'ru' => 'Огурец', 'en' => 'Cucumber'],
                'description' => [
                    'uz' => "Bodring - yangi holda va tuzlangan holda iste'mol qilinadigan sabzavot.",
                    'ru' => 'Огурец - овощ, употребляемый в свежем и соленом виде.',
                    'en' => 'Cucumber is a vegetable consumed fresh and pickled.',
                ],
                'image' => '/crops/images/cucumber.png',
            ],
            [
                'name' => ['uz' => 'Sabzi', 'ru' => 'Морковь', 'en' => 'Carrot'],
                'description' => [
                    'uz' => 'Sabzi - A vitamini va beta-karotinga boy sabzavot mahsuloti.',
                    'ru' => 'Морковь - овощная культура, богатая витамином А и бета-каротином.',
                    'en' => 'Carrot is a vegetable rich in vitamin A and beta-carotene.',
                ],
                'image' => '/crops/images/carrot.png',
            ],
            [
                'name' => ['uz' => 'Piyoz', 'ru' => 'Лук', 'en' => 'Onion'],
                'description' => [
                    'uz' => "Piyoz - oshxonada keng qo'llaniladigan sabzavot va ziravor.",
                    'ru' => 'Лук - широко используемый в кулинарии овощ и приправа.',
                    'en' => 'Onion is a widely used vegetable and spice in cooking.',
                ],
                'image' => '/crops/images/piyoz.png',
            ],
            [
                'name' => ['uz' => 'Qovun', 'ru' => 'Дыня', 'en' => 'Melon'],
                'description' => [
                    'uz' => "Qovun - O'zbekistonning mashhur poliz mahsuloti, shirin va xushbo'y.",
                    'ru' => 'Дыня - знаменитая бахчевая культура Узбекистана, сладкая и ароматная.',
                    'en' => 'Melon is a famous cucurbit crop of Uzbekistan, sweet and aromatic.',
                ],
                'image' => '/crops/images/qovun.png',
            ],
            [
                'name' => ['uz' => 'Tarvuz', 'ru' => 'Арбуз', 'en' => 'Watermelon'],
                'description' => [
                    'uz' => 'Tarvuz - yozgi poliz mahsuloti, chanqoqni qondiruvchi va foydali.',
                    'ru' => 'Арбуз - летняя бахчевая культура, утоляющая жажду и полезная.',
                    'en' => 'Watermelon is a summer cucurbit crop, thirst-quenching and beneficial.',
                ],
                'image' => '/crops/images/tarvuz.png',
            ],
            [
                'name' => ['uz' => 'Uzum', 'ru' => 'Виноград', 'en' => 'Grapes'],
                'description' => [
                    'uz' => 'Uzum - yangi holda, quritilgan (mayiz) va vino tayyorlashda ishlatiladi.',
                    'ru' => 'Виноград - используется в свежем виде, сушеном (изюм) и для производства вина.',
                    'en' => 'Grapes are used fresh, dried (raisins) and for wine production.',
                ],
                'image' => '/crops/images/grape.png',
            ],
            [
                'name' => ['uz' => 'Olma', 'ru' => 'Яблоко', 'en' => 'Apple'],
                'description' => [
                    'uz' => 'Olma - vitamin va minerallarga boy meva, turli navlari mavjud.',
                    'ru' => 'Яблоко - фрукт, богатый витаминами и минералами, имеет различные сорта.',
                    'en' => 'Apple is a fruit rich in vitamins and minerals, with various varieties.',
                ],
                'image' => '/crops/images/apple.png',
            ],
            [
                'name' => ['uz' => "O'rik", 'ru' => 'Абрикос', 'en' => 'Apricot'],
                'description' => [
                    'uz' => "O'rik - O'zbekistonning mashhur mevasi, yangi va quritilgan holda iste'mol qilinadi.",
                    'ru' => 'Абрикос - знаменитый фрукт Узбекистана, употребляется в свежем и сушеном виде.',
                    'en' => 'Apricot is a famous fruit of Uzbekistan, consumed fresh and dried.',
                ],
                'image' => '/crops/images/orik.png',
            ],
            [
                'name' => ['uz' => 'Anor', 'ru' => 'Гранат', 'en' => 'Pomegranate'],
                'description' => [
                    'uz' => 'Anor - antioksidantlarga boy meva, shifobaxsh xususiyatlarga ega.',
                    'ru' => 'Гранат - фрукт, богатый антиоксидантами, обладает лечебными свойствами.',
                    'en' => 'Pomegranate is a fruit rich in antioxidants with medicinal properties.',
                ],
                'image' => '/crops/images/anor.png',
            ],
            [
                'name' => ['uz' => 'Loviya', 'ru' => 'Фасоль', 'en' => 'Beans'],
                'description' => [
                    'uz' => "Loviya - oqsilga boy dukkakli o'simlik, oziq-ovqat sifatida ishlatiladi.",
                    'ru' => 'Фасоль - бобовая культура, богатая белком, используется в пищу.',
                    'en' => 'Beans are legumes rich in protein, used as food.',
                ],
                'image' => '/crops/images/loviya.png',
            ],
            [
                'name' => ['uz' => "No'xat", 'ru' => 'Горох', 'en' => 'Peas'],
                'description' => [
                    'uz' => "No'xat - dukkakli o'simlik, yangi va quruq holda iste'mol qilinadi.",
                    'ru' => 'Горох - бобовая культура, употребляется в свежем и сухом виде.',
                    'en' => 'Peas are legumes consumed fresh and dried.',
                ],
                'image' => '/crops/images/noxot.png',
            ],
            [
                'name' => ['uz' => 'Baqlajon', 'ru' => 'Баклажан', 'en' => 'Eggplant'],
                'description' => [
                    'uz' => 'Baqlajon - turli taomlarda ishlatiladigan sabzavot mahsuloti.',
                    'ru' => 'Баклажан - овощная культура, используемая в различных блюдах.',
                    'en' => 'Eggplant is a vegetable used in various dishes.',
                ],
                'image' => '/crops/images/eggplant.png',
            ],
            [
                'name' => ['uz' => 'Qalampir', 'ru' => 'Перец', 'en' => 'Pepper'],
                'description' => [
                    'uz' => 'Qalampir - shirin va achchiq navlari mavjud, C vitaminiga boy.',
                    'ru' => 'Перец - имеет сладкие и острые сорта, богат витамином С.',
                    'en' => 'Pepper has sweet and hot varieties, rich in vitamin C.',
                ],
                'image' => '/crops/images/sweet_pepper.png',
            ],
            [
                'name' => ['uz' => 'Karam', 'ru' => 'Капуста', 'en' => 'Cabbage'],
                'description' => [
                    'uz' => 'Karam - foydali sabzavot, turli navlari (oq, qizil, gul karam) mavjud.',
                    'ru' => 'Капуста - полезный овощ, имеет различные виды (белокочанная, краснокочанная, цветная).',
                    'en' => 'Cabbage is a healthy vegetable with various types (white, red, cauliflower).',
                ],
                'image' => '/crops/images/karam.png',
            ],
        ];

        foreach ($crops as $index => $cropData) {
            Crop::updateOrCreate(
                ['name->en' => $cropData['name']['en']],
                [
                    'uuid' => Str::uuid(),
                    'name' => $cropData['name'],
                    'description' => $cropData['description'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
