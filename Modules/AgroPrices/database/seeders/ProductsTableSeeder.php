<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\AgroPrices\Models\Product;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['uz' => 'Karbamid (mochevina)', 'ru' => 'Карбамид (мочевина)', 'en' => 'Urea', 'oz' => 'Karbamid'],
            ['uz' => 'Ammiakli selitra', 'ru' => 'Аммиачная селитра', 'en' => 'Ammonium nitrate', 'oz' => 'Ammiakli selitra'],
            ['uz' => 'Superfosfat', 'ru' => 'Суперфосфат', 'en' => 'Superphosphate', 'oz' => 'Superfosfat'],
            ['uz' => 'Kaliy xlorid', 'ru' => 'Хлорид калия', 'en' => 'Potassium chloride', 'oz' => 'Kaliy xlorid'],
            ['uz' => 'NPK 16-16-16', 'ru' => 'NPK 16-16-16', 'en' => 'NPK 16-16-16', 'oz' => 'NPK 16-16-16'],
            ['uz' => 'NPK 10-26-26', 'ru' => 'NPK 10-26-26', 'en' => 'NPK 10-26-26', 'oz' => 'NPK 10-26-26'],
            ['uz' => 'Kalsiy nitrat', 'ru' => 'Кальциевая селитра', 'en' => 'Calcium nitrate', 'oz' => 'Kalsiy nitrat'],
            ['uz' => 'Magniy sulfat', 'ru' => 'Сульфат магния', 'en' => 'Magnesium sulfate', 'oz' => 'Magniy sulfat'],
            ['uz' => 'Sink sulfat', 'ru' => 'Сульфат цинка', 'en' => 'Zinc sulfate', 'oz' => 'Sink sulfat'],
            ['uz' => 'Temir sulfat', 'ru' => 'Сульфат железа', 'en' => 'Iron sulfate', 'oz' => 'Temir sulfat'],
            ['uz' => 'Bor kislotasi', 'ru' => 'Борная кислота', 'en' => 'Boric acid', 'oz' => 'Bor kislotasi'],
            ['uz' => 'Kaliy nitrat', 'ru' => 'Калиевая селитра', 'en' => 'Potassium nitrate', 'oz' => 'Kaliy nitrat'],
            ['uz' => 'Fosfor kislotasi', 'ru' => 'Фосфорная кислота', 'en' => 'Phosphoric acid', 'oz' => 'Fosfor kislotasi'],
            ['uz' => 'Humus ekstrakti', 'ru' => 'Экстракт гумуса', 'en' => 'Humic extract', 'oz' => 'Humus ekstrakti'],
            ['uz' => 'Gibberillin', 'ru' => 'Гиббереллин', 'en' => 'Gibberellin', 'oz' => 'Gibberillin'],
            ['uz' => 'Kinetin', 'ru' => 'Кинетин', 'en' => 'Kinetin', 'oz' => 'Kinetin'],
            ['uz' => 'Auxin (IAA)', 'ru' => 'Ауксин (IAA)', 'en' => 'Auxin (IAA)', 'oz' => 'Auxin (IAA)'],
            ['uz' => 'Amino kislotalar', 'ru' => 'Аминокислоты', 'en' => 'Amino acids', 'oz' => 'Amino kislotalar'],
            ['uz' => 'Fulvo kislota', 'ru' => 'Фульвокислота', 'en' => 'Fulvic acid', 'oz' => 'Fulvo kislota'],
            ['uz' => 'Kaliy humat', 'ru' => 'Гумат калия', 'en' => 'Potassium humate', 'oz' => 'Kaliy humat'],
            ['uz' => 'Kalsiy karbonat', 'ru' => 'Карбонат кальция', 'en' => 'Calcium carbonate', 'oz' => 'Kalsiy karbonat'],
            ['uz' => 'Dolomit un', 'ru' => 'Доломитовая мука', 'en' => 'Dolomite lime', 'oz' => 'Dolomit un'],
            ['uz' => 'Yashil sovun', 'ru' => 'Зелёное мыло', 'en' => 'Soft green soap', 'oz' => 'Yashil sovun'],
            ['uz' => 'Neem yog‘', 'ru' => 'Ним масло', 'en' => 'Neem oil', 'oz' => 'Neem yog‘'],
            ['uz' => 'Kalsiy xelat', 'ru' => 'Хелат кальция', 'en' => 'Calcium chelate', 'oz' => 'Kalsiy xelat'],
            ['uz' => 'Mikroelementlar kompleksi', 'ru' => 'Комплекс микроэлементов', 'en' => 'Micronutrient complex', 'oz' => 'Mikroelementlar kompleksi'],
            ['uz' => 'Silisiy o‘g‘it', 'ru' => 'Кремниевое удобрение', 'en' => 'Silicon fertilizer', 'oz' => 'Silisiy o‘g‘it'],
            ['uz' => 'Kükürt (oltingugurt)', 'ru' => 'Сера', 'en' => 'Sulfur', 'oz' => 'Kükürt (oltingugurt)'],
            ['uz' => 'Kaltsiy oksid', 'ru' => 'Оксид кальция', 'en' => 'Calcium oxide', 'oz' => 'Kaltsiy oksid'],
            ['uz' => 'Biostimulyator kompleks', 'ru' => 'Биостимулятор комплекс', 'en' => 'Biostimulant complex', 'oz' => 'Biostimulyator kompleks'],
        ];

        foreach ($items as $item) {
            $slug = Str::slug($item['en']);
            Product::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $item]
            );
        }
    }
}
