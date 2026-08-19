<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Crops\Models\Crop;

class CropImagesSeeder extends Seeder
{
    public function run(): void
    {
        $dir = public_path('crops/images');
        if (! is_dir($dir)) {
            $this->command?->warn('No public/crops/images directory found, skipping image assignment.');

            return;
        }

        // Build a list of available image basenames (without extension)
        $files = glob($dir.'/*.png');
        $bases = [];
        foreach ($files as $file) {
            $base = pathinfo($file, PATHINFO_FILENAME); // e.g., 'watermelon'
            $bases[$base] = '/crops/images/'.basename($file);
        }

        // Alias map: common substrings mapped to available PNG image base names
        $alias = [
            // cereals - PNG filenames
            'wheat' => "bug'doy", 'triticum' => "bug'doy", "bug'doy" => "bug'doy",
            'rice' => 'sholi', 'sholi' => 'sholi', 'oryza' => 'sholi',
            'corn' => 'makkajoxori', 'maize' => 'makkajoxori', 'makkajoxori' => 'makkajoxori', 'zea mays' => 'makkajoxori',
            'sorghum' => 'joxori', 'joxori' => 'joxori', "jo'xori" => 'joxori',
            'barley' => 'arpa', 'arpa' => 'arpa', 'hordeum' => 'arpa',
            'rye' => 'javdar', 'javdar' => 'javdar', 'secale' => 'javdar',
            'oats' => 'suli', 'suli' => 'suli', 'avena' => 'suli',
            'tritikale' => 'tritikale',

            // vegetables & fruits - PNG filenames
            'tomato' => 'tomato', 'pomidor' => 'tomato', 'lycopersicum' => 'tomato',
            'cucumber' => 'cucumber', 'bodring' => 'cucumber', 'cucumis sativus' => 'cucumber',
            'pepper' => 'pepper', 'sweet pepper' => 'pepper', 'shirin_qalampir' => 'pepper',
            'hot pepper' => 'pepper', 'achchiq_qalampir' => 'pepper', 'capsicum' => 'pepper',
            'eggplant' => 'eggplant', 'baqlajon' => 'eggplant', 'melongena' => 'eggplant',
            'cabbage' => 'karam', 'karam' => 'karam', 'brassica oleracea' => 'karam',
            'broccoli' => 'brokkoli', 'brokkoli' => 'brokkoli',
            'cauliflower' => 'gulkaram', 'gulkaram' => 'gulkaram',
            'brussels sprouts' => 'bryussel_karami', 'bryussel karami' => 'bryussel_karami', 'bryussel_karami' => 'bryussel_karami',
            'leek' => 'porey_piyoz', 'porey piyoz' => 'porey_piyoz', 'porey_piyoz' => 'porey_piyoz',
            'onion' => 'piyoz', 'piyoz' => 'piyoz', 'allium cepa' => 'piyoz',
            'potato' => 'potato', 'kartoshka' => 'potato', 'solanum tuberosum' => 'potato',
            'carrot' => 'sabzi', 'sabzi' => 'sabzi', 'daucus carota' => 'sabzi',
            'beans' => 'loviya', 'loviya' => 'loviya', 'phaseolus' => 'loviya',
            'peas' => 'noxot', 'noxot' => 'noxot', 'pisum sativum' => 'noxot',
            'chickpea' => 'noxot', "no'xat" => 'noxot', 'cicer' => 'noxot',
            'turnip' => 'sholgom', 'sholgom' => 'sholgom', "sholg'om" => 'sholgom',
            'turnip greens' => 'bar_sholgom', 'bar_sholgom' => 'bar_sholgom', "barg sholg'om" => 'bar_sholgom',
            'radish' => 'turp', 'turp' => 'turp', 'raphanus' => 'turp',
            'radish small' => 'rediska', 'rediska' => 'rediska',

            // fruits - PNG filenames
            'apple' => 'apple', 'olma' => 'apple', 'malus' => 'apple',
            'grape' => 'grape', 'grapes' => 'grape', 'uzum' => 'grape', 'vitis' => 'grape',
            'apricot' => 'apricot', 'orik' => 'apricot', "o'rik" => 'apricot', 'armeniaca' => 'apricot',
            'pomegranate' => 'pomegranate', 'anor' => 'pomegranate', 'punica granatum' => 'pomegranate',
            'watermelon' => 'watermelon', 'tarvuz' => 'watermelon', 'citrullus' => 'watermelon',
            'melon' => 'melon', 'qovun' => 'melon', 'cucumis melo' => 'melon',
            'pumpkin' => 'pumpkin', 'qovoq' => 'pumpkin', 'cucurbita' => 'pumpkin',
            'walnut' => 'walnut', "yong'oq" => 'walnut', 'juglans' => 'walnut',
            'peanut' => 'yer_yongoq', 'yer yongoq' => 'yer_yongoq', 'yer_yongoq' => 'yer_yongoq',
            'almond' => 'almond', 'bodom' => 'almond', 'prunus dulcis' => 'almond',
            'quince' => 'quince', 'behi' => 'quince', 'cydonia' => 'quince',
            'persimmon' => 'xurmo', 'xurmo' => 'xurmo',
            'fig' => 'fig', 'anjir' => 'fig', 'ficus' => 'fig',
            'pear' => 'pear', 'nok' => 'pear', 'pyrus' => 'pear',
            'cherry' => 'cherry', 'gilos' => 'cherry',
            'sour cherry' => 'cherry', 'olcha' => 'cherry', 'cerasus' => 'cherry',
            'plum' => 'plum', 'olxori' => 'plum', "olxo'ri" => 'plum', 'prunus domestica' => 'plum',
            'peach' => 'peach', 'shaftoli' => 'peach', 'prunus persica' => 'peach',
            'pistachio' => 'pista', 'pista' => 'pista', 'pistacia' => 'pista',
            'strawberry' => 'qulupnay', 'qulupnay' => 'qulupnay', 'fragaria' => 'qulupnay',
            'olive' => 'zaytun', 'zaytun' => 'zaytun',

            // herbs & aromatics - PNG filenames
            'garlic' => 'sarimsoq', 'sarimsoq' => 'sarimsoq', 'allium sativum' => 'sarimsoq',
            'spinach' => 'ismaloq', 'ismaloq' => 'ismaloq', 'spinacia' => 'ismaloq',
            'arugula' => 'rukola', 'rukola' => 'rukola', 'eruca' => 'rukola',
            'dill' => 'ukrop', 'ukrop' => 'ukrop', 'anethum' => 'ukrop',
            'parsley' => 'petrushka', 'petrushka' => 'petrushka',
            'artichoke' => 'artishok', 'artishok' => 'artishok',
            'coriander' => 'kashnich', 'kashnich' => 'kashnich', 'coriandrum' => 'kashnich',

            // legumes & oil crops - PNG filenames
            'soybean' => 'soya', 'soya' => 'soya', 'glycine' => 'soya',
            'lentil' => 'yasmiq', 'yasmiq' => 'yasmiq', 'lens' => 'yasmiq',
            'mung bean' => 'mosh', 'mosh' => 'mosh', 'phaseolus aureus' => 'mosh',
            'vetch' => 'burchoq', 'burchoq' => 'burchoq', 'yathyvus' => 'burchoq',
            'flax' => 'moyli_zigir', 'flaxseed' => 'moyli_zigir', 'moyli_zigir' => 'moyli_zigir', "zig'ir" => 'moyli_zigir',
            'sunflower' => 'kungaboqar', 'kungaboqar' => 'kungaboqar',
            'rapeseed' => 'raps', 'raps' => 'raps', 'canola' => 'raps',
            'safflower' => 'maxsar', 'maxsar' => 'maxsar',

            // industrial & forage crops - PNG filenames
            'cotton' => 'goza', 'paxta' => 'goza', 'goza' => 'goza', 'gossypium' => 'goza',
            'hemp' => 'kanop', 'kanop' => 'kanop',
            'sugar beet' => 'qand_lavlagi', 'qand_lavlagi' => 'qand_lavlagi', 'beta vulgaris l' => 'qand_lavlagi',
            'fodder beet' => 'lavlagi', 'lavlagi' => 'lavlagi', 'beta vulgaris' => 'lavlagi',
            'sweet potato' => 'batat', 'batat' => 'batat', 'ipomoea batatas' => 'batat',
            'alfalfa' => 'beda', 'beda' => 'beda', 'medicago' => 'beda',
            'sudan grass' => 'sudan_oti', 'sudan_oti' => 'sudan_oti', 'sudan' => 'sudan_oti',

            // special crops - PNG filenames
            'okra' => 'bamiya', 'bamiya' => 'bamiya',
        ];

        $updated = 0;
        $crops = Crop::query()->get();
        foreach ($crops as $crop) {
            // Skip crops that already have media
            if ($crop->hasMedia(Crop::MEDIA_COLLECTION_IMAGE)) {
                continue;
            }

            // Match by name in different locales
            $names = $crop->getTranslations('name');
            $en = (string) ($names['en'] ?? '');
            $uz = (string) ($names['uz'] ?? '');
            $ru = (string) ($names['ru'] ?? '');
            $enSlug = Str::slug($en);

            $match = null;
            // Build a searchable haystack with multiple locales
            $hay = strtolower($en.' '.$enSlug.' '.$uz.' '.$ru);

            // Try alias mapping first
            foreach ($alias as $needle => $imgBase) {
                if (! isset($bases[$imgBase])) {
                    continue;
                }
                if (str_contains($hay, strtolower($needle))) {
                    $match = $bases[$imgBase];
                    break;
                }
            }

            // Fallback: loose match by filename base contained in EN slug
            if (! $match) {
                foreach ($bases as $key => $path) {
                    if ($enSlug && str_contains($enSlug, $key)) {
                        $match = $path;
                        break;
                    }
                }
            }

            if ($match) {
                // Use Spatie Media Library instead of image field
                try {
                    $imagePath = public_path(ltrim($match, '/'));
                    if (file_exists($imagePath)) {
                        $crop->addMedia($imagePath)
                            ->preservingOriginal()
                            ->toMediaCollection(Crop::MEDIA_COLLECTION_IMAGE);
                        $updated++;
                    }
                } catch (Exception $e) {
                    $this->command?->warn("Failed to add image for crop {$crop->id}: ".$e->getMessage());
                }
            }
        }

        $this->command?->info("Assigned images to {$updated} crops based on public/crops/images/*.png");

        // Report any crops without images
        $unassigned = Crop::query()->whereDoesntHave('media')->get();
        if ($unassigned->count() > 0) {
            $this->command?->warn("There are still {$unassigned->count()} crops without images:");
            foreach ($unassigned as $crop) {
                $names = $crop->getTranslations('name');
                $this->command?->warn("  - {$names['en']} (ID: {$crop->id})");
            }
        }
    }
}
