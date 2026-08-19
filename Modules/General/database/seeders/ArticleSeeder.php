<?php

declare(strict_types=1);

namespace Modules\General\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Crops\Models\Crop;
use Modules\General\Models\Article;
use Modules\General\Models\ArticleTag;
use RuntimeException;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tagIdBySlug = ArticleTag::query()->pluck('id', 'slug');

        $activeCrops = Crop::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($activeCrops->isEmpty()) {
            $this->command?->warn('No active crops found. Skipping article seeding.');

            return;
        }

        $articles = collect();

        foreach ($activeCrops as $crop) {
            $articles = $articles->merge($this->buildArticlesForCrop($crop));
        }

        $articles = $articles
            ->merge($this->buildSharedArticles($activeCrops))
            ->unique('title_oz')
            ->values();

        $cropSlugToIdMap = [];
        foreach (Crop::query()->get() as $crop) {
            $slug = Str::slug($crop->name['en'] ?? $crop->name['uz'] ?? ('crop-'.$crop->id));
            $cropSlugToIdMap[$slug] = $crop->id;
        }

        foreach ($articles as $articleData) {
            $cropSlugs = Arr::pull($articleData, 'crop_slugs', []);

            if (empty($cropSlugs)) {
                throw new RuntimeException('Article definition is missing crop_slugs.');
            }

            $this->ensureCropSlugsExist($cropSlugs, $cropSlugToIdMap, $articleData['title_oz']);

            $articleData['crop_ids'] = collect($cropSlugs)
                ->map(fn (string $slug) => (int) $cropSlugToIdMap[$slug])
                ->values()
                ->all();

            $tagSlugs = array_values(array_unique(Arr::wrap($articleData['tags'] ?? [])));
            $articleData = Arr::except($articleData, ['tags']);

            $model = Article::updateOrCreate(
                ['title_oz' => $articleData['title_oz']],
                $articleData
            );

            $tagIds = collect($tagSlugs)
                ->filter()
                ->map(function (string $slug) use (&$tagIdBySlug): int {
                    $slug = trim($slug);
                    if (! isset($tagIdBySlug[$slug])) {
                        $translations = $this->getTagTranslations($slug);
                        $tag = ArticleTag::updateOrCreate(
                            ['slug' => $slug],
                            [
                                'name_uz' => $translations['uz'],
                                'name_ru' => $translations['ru'],
                                'name_oz' => $translations['en'],
                            ]
                        );
                        $tagIdBySlug[$slug] = $tag->id;
                    }

                    return (int) $tagIdBySlug[$slug];
                })
                ->values()
                ->all();

            $model->tags()->sync($tagIds);
        }
    }

    /**
     * Build deterministic article set for a single crop.
     */
    private function buildArticlesForCrop(Crop $crop): Collection
    {
        $templates = collect($this->articleTemplates());

        if ($templates->isEmpty()) {
            return collect();
        }

        $available = $templates->count();
        $minCount = min(4, $available);
        $maxCount = min(10, $available);
        $range = max(0, $maxCount - $minCount);
        $count = $minCount + ($range > 0 ? $this->hashMod((string) $crop->id, $range + 1) : 0);

        $offset = $this->hashMod((string) $crop->id, $available);
        $fallback = $crop->name['en'] ?? $crop->name['uz'] ?? $crop->name['ru'] ?? ('Crop '.$crop->id);

        $replacements = [
            ':crop_uz' => $crop->name['uz'] ?? $crop->name['en'] ?? $crop->name['ru'] ?? $fallback,
            ':crop_ru' => $crop->name['ru'] ?? $crop->name['uz'] ?? $crop->name['en'] ?? $fallback,
            ':crop_en' => $crop->name['en'] ?? $crop->name['uz'] ?? $crop->name['ru'] ?? $fallback,
            ':crop_slug' => Str::slug($crop->name['en'] ?? $crop->name['uz'] ?? ('crop-'.$crop->id)),
        ];

        $articles = [];

        for ($i = 0; $i < $count; $i++) {
            $template = $templates[($offset + $i) % $available];
            $cropSlug = Str::slug($crop->name['en'] ?? $crop->name['uz'] ?? ('crop-'.$crop->id));
            $articles[] = $this->applyTemplate(
                $template,
                $replacements,
                [
                    'crop_slugs' => [$cropSlug],
                    'preview_image_seed' => $cropSlug.'-'.($template['key'] ?? 'article'),
                ]
            );
        }

        return collect($articles);
    }

    /**
     * Build a set of cross-crop articles to reflect multi-crop guidance.
     */
    private function buildSharedArticles(Collection $crops): Collection
    {
        $bySlug = $crops->keyBy('slug');
        $articles = [];

        foreach ($this->sharedArticlesDefinition() as $definition) {
            $slugs = $definition['slugs'];
            $missing = collect($slugs)
                ->reject(fn (string $slug) => $bySlug->has($slug));

            if ($missing->isNotEmpty()) {
                continue;
            }

            $subset = collect($slugs)->map(fn (string $slug) => $bySlug[$slug]);

            $replacements = [
                ':crop_list_uz' => $this->formatCropList($subset, 'uz'),
                ':crop_list_ru' => $this->formatCropList($subset, 'ru'),
                ':crop_list_en' => $this->formatCropList($subset, 'en'),
            ];

            $articles[] = [
                'title_uz' => $this->format($definition['title']['uz'], $replacements),
                'title_ru' => $this->format($definition['title']['ru'], $replacements),
                'title_oz' => $this->format($definition['title']['oz'], $replacements),
                'preview_uz' => $this->format($definition['preview']['uz'], $replacements),
                'preview_ru' => $this->format($definition['preview']['ru'], $replacements),
                'preview_oz' => $this->format($definition['preview']['oz'], $replacements),
                'body_uz' => $this->format($definition['body']['uz'], $replacements),
                'body_ru' => $this->format($definition['body']['ru'], $replacements),
                'body_oz' => $this->format($definition['body']['oz'], $replacements),
                'attachment' => $definition['attachment'] ?? null,
                'icon' => $definition['icon'] ?? null,
                'preview_image' => $this->buildPreviewImage($slugs[0], $definition['key']),
                'tags' => $definition['tags'],
                'crop_slugs' => $slugs,
            ];
        }

        return collect($articles);
    }

    /**
     * Static article templates per crop.
     */
    private function articleTemplates(): array
    {
        return [
            [
                'key' => 'soil-preparation',
                'tags' => ['soil-management', 'good-agriculture-practices'],
                'icon' => 'soil',
                'title' => [
                    'uz' => ':crop_uz uchun tuproqni chuqur tayyorlash rejasi',
                    'ru' => 'План глубокой подготовки почвы под :crop_ru',
                    'oz' => 'Deep Soil Preparation Plan for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Namni ushlab qolish va shoʻr hosil bo‘lishini kamaytirish uchun chuqur yumshatish, organik modda hamda bo‘lak tuzilmasini nazorat qilish.',
                    'ru' => 'Глубокое рыхление, органика и контроль агрегатного состава для сохранения влаги и снижения засоления.',
                    'oz' => 'Deep ripping, organic amendments, and aggregate control keep moisture in place and limit salinity.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ekiladigan maydonda hosildor qatlamni 30 sm gacha chuqur yuvshatish zich qatlamlarni parchalab, ildizlar uchun havo va suv kirishini yaxshilaydi. Har bir yo'nalishda shudgor yoki chisel ishlovi berib, daladagi o'simlik qoldiqlarini maydalab tuproqqa aralashtiring; bu namni ushlab qolishga va mikroorganizmlar faolligini oshirishga yordam beradi. Og'ir tuproqlarda ishlayotganda namlik 65-70% dan oshmasligini nazorat qiling, aks holda g'ovaklar eziladi.

Organik modda sifatida 10-15 t/ga kompost yoki chirindan foydalanib, fosforni chiziqli usulda 10-12 sm chuqurlikka kiritish o'simlikning dastlabki oziqlanishini tezlashtiradi. Dalada lazerli rejalash yoki engil tekislash suv to'planadigan pastqam joylarni kamaytiradi. Ish tugagach, mayda bo'lakli qatlamni hosil qilish uchun boronalashni amalga oshiring va tuproq zichligini penetrometr bilan muntazam tekshirib boring.
UZ,
                    'ru' => <<<'RU'
Глубокое рыхление на 30 см под :crop_ru разрушает уплотнённый слой и улучшает воздухо- и влагопроницаемость. Проводите вспашку или чизелевание в перекрёстных направлениях, измельчайте пожнивные остатки и заделывайте их в горизонты — так вы сохраните влагу и повысите активность микробиоты. На тяжёлых почвах контролируйте влажность: при показателях выше 70 % работа тяжёлой техникой разрушает агрегаты.

Внесите 10–15 т/га компоста либо перегноя и подайте фосфор строчно на глубину 10–12 см, чтобы ускорить стартовое питание. Лазерное планирование или лёгкое выравнивание поля уменьшит застой воды. После основной обработки сформируйте мелкокомковатое ложе боронованием и периодически проверяйте плотность почвы пенетрометром.
RU,
                    'oz' => <<<'EN'
Deep tillage to 30 cm for :crop_en breaks compacted layers and opens porosity for air and water. Work the field in cross directions with a plough or chisel, shred residues, and reincorporate them to retain moisture and energise soil biology. On heavy soils, pause operations if field moisture exceeds roughly 70 percent to avoid crushing structure.

Apply 10–15 t/ha of compost or manure and band phosphorus 10–12 cm below the seed zone to accelerate early uptake. Laser levelling or light grading evens low spots that would pond water. Finish with a fine seedbed by harrowing and keep tracking soil resistance with a penetrometer to decide when the next loosening is needed.
EN,
                ],
            ],
            [
                'key' => 'irrigation-scheduling',
                'tags' => ['irrigation', 'water-management'],
                'icon' => 'water',
                'title' => [
                    'uz' => ':crop_uz dalalarida namlikni aniq boshqarish jadvali',
                    'ru' => 'График точного управления влагой для :crop_ru',
                    'oz' => 'Precision Moisture Schedule for :crop_en Fields',
                ],
                'preview' => [
                    'uz' => 'Tensiometrlar, ET-hisob va yomg‘ir sensorlari yordamida sug‘orish oraliqlarini optimallashtirish.',
                    'ru' => 'Тензиометры, расчёт ЭТ и датчики осадков помогают подобрать оптимальные интервалы полива.',
                    'oz' => 'Tensiometers, ET modelling, and rain gauges fine-tune irrigation intervals.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz maydonlarida 20 va 40 sm chuqurlikdagi tensiometrlar namlik chegaralarini aniqlashga yordam beradi. Ko'rsatkich 30 kPa dan oshishi bilan 25-30 mm me'yorida sug'orish boshlanadi, hosilning og'ir o'sish fazasida esa chegarani 20 kPa ga tushiring. Iqlim stansiyasidan olinadigan evapotranspiratsiya (ET) ma'lumotlarini kiritib, haftalik suv balansini yuriting va yomg'ir sensorlari orqali tabiiy yog'ingarchilikni hisobga oling.

Sug'orishdan so'ng 24 soat ichida namlikni qayta o'lchab, tuproqning suvni qay darajada saqlayotganini baholang. Agar pastki qatlamlar doim ho'l qolsa, chuqur drenaj tirqishlarini oching yoki almashlab sug'orish (skip irrigation) usulidan foydalaning. Kechki yoki erta tonggi sug'orish bargdagi bug'lanishni kamaytiradi va suv sarfini 12-15 % gacha tejaydi.
UZ,
                    'ru' => <<<'RU'
На полях :crop_ru устанавливайте тензометры на глубине 20 и 40 см, чтобы фиксировать порог влажности. При значениях свыше 30 кПа подавайте 25–30 мм воды, а в период активного роста снижайте порог до 20 кПа. Включайте расчёты эвтранспирации и данные метеостанции, ведите недельный баланс воды и учитывайте натуральные осадки по датчикам дождя.

Через 24 часа после полива повторно измеряйте влажность, чтобы оценить удержание воды в нижних горизонтах. Если они остаются переувлажнёнными, вскройте глубокие дренажные щели или применяйте чередование поливов через ряд. Полив вечером или ранним утром уменьшает испарение с листьев и экономит до 15 % воды.
RU,
                    'oz' => <<<'EN'
Install tensiometers at 20 and 40 cm depths for :crop_en to set moisture thresholds precisely. When readings climb above 30 kPa, deliver 25–30 mm of water, and tighten the trigger to 20 kPa during peak vegetative growth. Feed evapotranspiration data from weather stations into a weekly water balance and subtract rainfall captured by onsite gauges.

Recheck moisture 24 hours after each irrigation to gauge how well the lower profile holds water. If deeper layers stay saturated, cut relief slots or alternate irrigated rows to lift drainage. Watering at dusk or dawn reduces leaf evaporation and can save up to 15 percent of the total water budget.
EN,
                ],
            ],
            [
                'key' => 'nutrition-plan',
                'tags' => ['fertilization', 'nutrient-management'],
                'icon' => 'fertilizer',
                'title' => [
                    'uz' => ':crop_uz uchun muvozanatli oziqlantirish strategiyasi',
                    'ru' => 'Сбалансированная схема питания для :crop_ru',
                    'oz' => 'Balanced Nutrition Strategy for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Makro va mikroelementlarni fenologiyaga qarab bo‘lish, barg tahlili va differensial normani kiritish.',
                    'ru' => 'Деление макро- и микроэлементов по фазам, листовые анализы и дифференцированные нормы.',
                    'oz' => 'Stage-based macro and micro feeding backed by tissue tests and variable rates.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz hosildorligini oshirish uchun azotni uch qismga bo'ling: 40 % ekish paytida, 30 % o'sishning shiddatli fazasida va 30 % generativ davrga kirishdan oldin. Fosforni chiziqli usulda 8-10 sm chuqurlikka joylashtiring, kaliy esa sorbfangani yuqori maydonlarda bargdan qo'llang (3 % K2SO4). Har ikki haftada barg tahlilini olib borib, kritik elementlar darajasini kuzating.

Omikronsiz mikroelementlar uchun kelatlangan rux, bor yoki molibdenni 0,5-1 % konsentratsiyada purkash barglarning yashil massasi va fotosintezini qo'llab-quvvatlaydi. Tomchilatib sug'orish liniyalariga Venturi injektori orqali ozuqa eritmalarini berib, EC darajasini 1,8 dS/m dan oshirmang. Tuproqdagi organik uglerod miqdorini barqaror ushlab turish uchun oraliqda sideratlar ekib, mulchalashni muntazam ravishda yangilang.
UZ,
                    'ru' => <<<'RU'
Разделите азот для :crop_ru на три приёма: 40 % при посадке, 30 % в период бурного роста и 30 % перед вступлением в генеративную фазу. Фосфор размещайте строчно на глубине 8–10 см, а калий при высоком КОЕ вносите листовыми подкормками (3 % сульфата калия). Каждые две недели проводите листовой анализ, чтобы контролировать критические элементы.

Хелатные формы цинка, бора или молибдена в концентрации 0,5–1 % поддерживают листовую массу и фотосинтез. Через инжектор Вентури в систему капельного орошения подавайте питательные растворы, удерживая электропроводность ниже 1,8 дС/м. Для стабилизации органического углерода высевайте сидераты и регулярно обновляйте мульчу.
RU,
                    'oz' => <<<'EN'
Split nitrogen for :crop_en into three events: 40 percent at planting, 30 percent during rapid vegetative growth, and the final 30 percent just ahead of reproductive development. Band phosphorus 8–10 cm deep in the row, and supply potassium as foliar sprays (3 percent potassium sulfate) where exchange sites are saturated. Run tissue tests every two weeks to monitor critical nutrients.

Chelated zinc, boron, or molybdenum at 0.5–1 percent solutions support canopy health and photosynthesis. Inject fertigation blends via a Venturi into drip laterals while keeping electrical conductivity under 1.8 dS/m. Maintain soil organic carbon by sowing cover crops between cycles and refreshing mulch layers on a schedule.
EN,
                ],
            ],
            [
                'key' => 'pest-monitoring',
                'tags' => ['pest-management', 'integrated-pest-management'],
                'icon' => 'shield',
                'title' => [
                    'uz' => ':crop_uz dalalarida integratsiyalashgan zararkunanda kuzatuvi',
                    'ru' => 'Интегрированный мониторинг вредителей на :crop_ru',
                    'oz' => 'Integrated Pest Scouting for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Haftalik dalani ko‘zdan kechirish, feromon tutishlari va foydali entomofaunaning himoyasi.',
                    'ru' => 'Еженедельный осмотр поля, феромонные ловушки и сохранение полезной энтомофауны.',
                    'oz' => 'Weekly field walks, pheromone trapping, and conserving beneficials.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun feromon tuzoqlarini gektariga bittadan joylashtirib, haftada ikki marta tutish sonini qayd eting. 25 ta tasodifiy nuqtada o'simliklarni tekshirib, zarar ko'rgan barglar, kurtaklar yoki mevalar ulushi 5 % dan oshganida himoya choralarini boshlang. Erta bosqichda biopreparatlar (Bacillus thuringiensis, Beauveria bassiana) va kerosin asosidagi sovun eritmalari foydali hasharotlarni saqlagan holda bosimni pasaytiradi.

Kimyoviy ishlovlar zarur bo'lganda, IRAC guruhlari o'rtasida navbatlang va bargning quyi qismiga yetib borishi uchun past bosimli purkagichlardan foydalaning. Kuzgi mavsumda zararkunanda qishlashi mumkin bo'lgan o'simlik qoldiqlarini daladan chiqarib, chuqur haydashni amalga oshiring. Yaqin atrofdagi dala chetlarini gul aralashmalari bilan boyitish tabiiy entomofaunani dalada ushlab turadi.
UZ,
                    'ru' => <<<'RU'
Размещайте по одной феромонной ловушке на гектар :crop_ru и фиксируйте уловы дважды в неделю. Осматривайте 25 случайных точек: если повреждённых листьев, бутонов или плодов более 5 %, начинайте защитные мероприятия. На ранних стадиях применяйте биопрепараты (Bacillus thuringiensis, Beauveria bassiana) и мыльно-керосиновые растворы, чтобы снизить давление, сохраняя полезных насекомых.

При химических обработках чередуйте препараты разных групп IRAC и используйте опрыскиватели с низким давлением, чтобы раствор попадал на нижнюю сторону листа. Осенью удаляйте растительные остатки и проводите глубокую вспашку, сокращая зимующие стадии вредителей. Высевайте по кромкам поля цветочные смеси — это удержит естественных врагов рядом с посевами.
RU,
                    'oz' => <<<'EN'
Deploy one pheromone trap per hectare of :crop_en and record catches twice each week. Inspect 25 random sites; when damaged leaves, buds, or fruit exceed five percent, initiate control. Early in the season rely on biocontrol inputs such as Bacillus thuringiensis or Beauveria bassiana sprays along with soap–kerosene mixes to keep pressure down while preserving beneficials.

When chemical intervention is needed, rotate active ingredients across IRAC groups and use low-pressure sprayers to reach the underside of foliage. Remove crop residues and deep-plough after harvest to destroy overwintering stages. Plant flowering strips along field borders to keep natural enemies active within the production block.
EN,
                ],
            ],
            [
                'key' => 'harvest-postharvest',
                'tags' => ['harvest-management', 'postharvest'],
                'icon' => 'storage',
                'title' => [
                    'uz' => ':crop_uz hosilini yig‘ib olish va saqlash protokoli',
                    'ru' => 'Протокол уборки и хранения для :crop_ru',
                    'oz' => 'Harvest and Storage Protocol for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Yetuklikni aniqlash, dalada dastlabki saralash va ventilyatsiyalangan saqlash xonalarini sozlash.',
                    'ru' => 'Определение зрелости, полевое сортирование и настройка вентилируемых камер.',
                    'oz' => 'Assess maturity, grade in-field, and tune ventilated storage.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz hosili yig'ishga tayyorligini brix o'lchagichi, qobiq rangi yoki to'qimalar orqali aniqlang. Dalada dastlabki saralash o'tkazib, ajratilgan sifat sinflari bo'yicha plastik yashiklarga joylashtiring. Hosilni salqin (12–15 °C) va soyali joyda nafas olishini ta'minlab vaqtincha saqlang, keyin esa ventilyatsiyalangan xonalarga ko'chiring.

Saqlash omborida haroratni va nisbiy namlikni doimiy monitor bilan kuzating: mevali mahsulotlar uchun 0–4 °C, dukkaklilar uchun 8–10 °C atrofida ushlang. Havo almashinuvi soatiga 20 m³/t dan kam bo'lmaganda CO₂ 3000 ppm dan oshmaydi. Partiyalarni RFID yoki oddiy yorliqlar orqali kuzatib, birinchi kirgan–birinchi chiqadi (FIFO) tamoyiliga amal qiling.
UZ,
                    'ru' => <<<'RU'
Определяйте готовность :crop_ru к уборке по показаниям рефрактометра, цвету и плотности тканей. Проводите первичную сортировку прямо в поле, распределяя продукцию по классам качества и укладывая её в пластиковые ящики. Держите собранный урожай в тени при 12–15 °C, обеспечивая воздухообмен, после чего перемещайте в вентилируемые хранилища.

В хранилище контролируйте температуру и влажность с помощью датчиков: для плодов — 0–4 °C, для бобовых — около 8–10 °C. При воздухообмене не ниже 20 м³/т·ч концентрация CO₂ останется ниже 3000 ppm. Отслеживайте партии с помощью RFID или маркировки и придерживайтесь принципа FIFO, чтобы минимизировать потери качества.
RU,
                    'oz' => <<<'EN'
Determine :crop_en harvest readiness with a refractometer, skin colour cues, or tissue firmness. Grade lots in the field into vented crates and keep them shaded at 12–15 °C with airflow before moving to storage. Maintain breathable spacing during transport to avoid pressure bruising.

Inside storage, log both temperature and humidity continuously: hold fruit crops near 0–4 °C, while legumes stay best around 8–10 °C. Keep airflow above 20 m³ per tonne per hour so CO₂ stays under 3000 ppm. Track batches with RFID or simple tags and follow first-in, first-out rotation to control shrink.
EN,
                ],
            ],
            [
                'key' => 'climate-resilience',
                'tags' => ['climate-adaptation', 'crop-management'],
                'icon' => 'climate',
                'title' => [
                    'uz' => ':crop_uz ni iqlim stressiga tayyorlash choralari',
                    'ru' => 'Меры адаптации :crop_ru к климатическому стрессу',
                    'oz' => 'Climate Resilience Tactics for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Issiq-to‘lqin va sovuq zarbalarida sug‘orish, soyalash va antistress biostimulyatorlarini uyg‘unlashtirish.',
                    'ru' => 'Комбинация полива, затенения и антистрессовых биостимуляторов при жаре и заморозках.',
                    'oz' => 'Blend irrigation, shading, and anti-stress biostimulants against heat or cold snaps.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
Issiq to'lqin paytida :crop_uz barg sathidagi suv potensialini -1,2 MPa dan pastga tushirmaslik uchun kechki tomchilatib sug'orishni 35-40 mm norma bilan amalga oshiring. Bug'lanishni kamaytirish uchun agroto'qima yoki tarmoq soyalashni qo'llang, tuproq ustini organik mulcha bilan yopib, 5–7 sm qatlamni saqlang. Stressga chidamlilikni oshirish maqsadida silikon va aminokislotalarga boy biostimulyatorlarni bargdan qo'llash mumkin.

Kutilmagan sovuq zarbalari oldidan tuproqdagi namlikni to'yingan holda ushlab, qatorlarga tuman purkash yoki shamollatkichlarni ishga tushiring. Urug' ekishdan oldin urug'larni biologik preparatlar bilan ishlov berish ildizlarni tezlashtiradi va stressdan keyin tiklanishni tezlashtiradi. Agroklimat ma'lumotlarini doimiy kuzatib, fenologik jadvalni mos ravishda yangilang.
UZ,
                    'ru' => <<<'RU'
Во время жары поддерживайте водный потенциал листьев :crop_ru выше –1,2 МПа, проводя вечерние капельные поливы нормой 35–40 мм. Для снижения испарения используйте затеняющие сетки и удерживайте 5–7 см органической мульчи. Листовые обработки биостимуляторами на основе кремния и аминокислот повышают устойчивость к стрессу.

Перед ожидаемыми заморозками удерживайте почву влажной, применяйте дымление или включайте ветродуи вдоль рядов. Протравливание семян биопрепаратами ускоряет укоренение и помогает быстрее восстановиться после стресса. Постоянно анализируйте агроклиматические данные и корректируйте фенологический график.
RU,
                    'oz' => <<<'EN'
During heat waves keep :crop_en leaf water potential above –1.2 MPa by irrigating at dusk with 35–40 mm through drip lines. Deploy shading nets and maintain 5–7 cm of organic mulch to suppress evaporation. Foliar biostimulants rich in silicon and amino acids bolster heat tolerance.

Ahead of forecast cold snaps, keep soil profiles at field capacity, use misting or wind machines across rows, and treat seed with biologicals to accelerate rooting and post-stress recovery. Track agroclimatic dashboards frequently and adjust phenology plans to match the latest forecasts.
EN,
                ],
            ],
            [
                'key' => 'seed-selection',
                'tags' => ['seed-management', 'crop-planning'],
                'icon' => 'seed',
                'title' => [
                    'uz' => ':crop_uz uchun yuqori sifatli urug\' tanlash va tayyorlash',
                    'ru' => 'Выбор и подготовка качественных семян для :crop_ru',
                    'oz' => 'Selecting and Preparing Quality Seeds for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Sertifikatlangan urug\', unuvchanlik testlari va ekishdan oldingi ishlov berish.',
                    'ru' => 'Сертифицированные семена, тесты всхожести и предпосевная обработка.',
                    'oz' => 'Certified seed, germination tests, and pre-planting treatment.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ekish uchun sertifikatlangan urug\' tanlang va unuvchanlik foizini 85% dan yuqori bo\'lishini tekshiring. Urug\'larni ekishdan 7-10 kun oldin termik ishlov berish (50-52°C, 20 daqiqa) yoki biologik preparatlar bilan protravlash kasalliklarni kamaytiradi. Urug\' o\'lchamini saralash va zich urug\'larni ajratish bir xil unish va o\'sishni ta\'minlaydi.

Ekishdan oldin urug\'larni mikroelementlar (molibden, sink, bor) bilan qoplash dastlabki o\'sishni tezlashtiradi. Urug\' saqlash sharoitlarini nazorat qiling: harorat 10-15°C, namlik 12-14% atrofida bo\'lishi kerak. Har bir partiyani laboratoriya testidan o\'tkazing va sertifikat bilan birga saqlang.
UZ,
                    'ru' => <<<'RU'
Для посадки :crop_ru выбирайте сертифицированные семена с всхожестью выше 85%. За 7-10 дней до посева проведите термическую обработку (50-52°C, 20 минут) или протравливание биопрепаратами для снижения болезней. Калибровка и отбор тяжелых семян обеспечивают равномерные всходы и развитие.

Предпосевное покрытие микроэлементами (молибден, цинк, бор) ускоряет начальный рост. Контролируйте условия хранения: температура 10-15°C, влажность около 12-14%. Каждую партию проверяйте в лаборатории и храните вместе с сертификатом.
RU,
                    'oz' => <<<'EN'
For planting :crop_en select certified seeds with germination rates above 85%. Conduct thermal treatment (50-52°C, 20 minutes) or biological seed dressing 7-10 days before planting to reduce diseases. Calibration and selection of dense seeds ensures uniform emergence and growth.

Pre-planting micronutrient coating (molybdenum, zinc, boron) accelerates early development. Control storage conditions: temperature 10-15°C, moisture around 12-14%. Test each seed lot in laboratory and store with certification documents.
EN,
                ],
            ],
            [
                'key' => 'organic-farming',
                'tags' => ['organic-agriculture', 'sustainable-farming'],
                'icon' => 'organic',
                'title' => [
                    'uz' => ':crop_uz organik yetishtirish standartlari',
                    'ru' => 'Стандарты органического выращивания :crop_ru',
                    'oz' => 'Organic Production Standards for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Tabiiy o\'g\'itlar, bionazorat va sertifikatsiya talablari.',
                    'ru' => 'Натуральные удобрения, биоконтроль и требования сертификации.',
                    'oz' => 'Natural fertilizers, biocontrol, and certification requirements.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz organik ishlab chiqarishda sintetik pestitsidlar va GMO dan voz kechib, tabiiy metodlarga tayanasiz. Kompost, chirindi va yashil o\'g\'itlarni ishlatib tuproq sog\'ligini saqlang. Zararkunandalarga qarshi biopreparatlar (neem moyi, Trichoderma) va tabiiy dushmanlarni qo\'llang.

Organik sertifikatsiya olish uchun 3 yillik o\'tish davrini o\'tish kerak. Barcha ishlov berish jarayonlarini dokumentlashtiring va tashqi auditlarga tayyorlaning. Organik mahsulotlarni oddiy mahsulotlardan alohida saqlang va transport qiling. Bozor narxlari odatdagidan 30-50% yuqori bo\'lishi mumkin.
UZ,
                    'ru' => <<<'RU'
При органическом производстве :crop_ru откажитесь от синтетических пестицидов и ГМО, опираясь на естественные методы. Поддерживайте здоровье почвы компостом, навозом и зелёными удобрениями. Против вредителей применяйте биопрепараты (масло нима, Trichoderma) и естественных врагов.

Для получения органического сертификата требуется пройти 3-летний переходный период. Документируйте все процессы обработки и готовьтесь к внешним аудитам. Храните и транспортируйте органическую продукцию отдельно от обычной. Рыночные цены могут быть на 30-50% выше обычных.
RU,
                    'oz' => <<<'EN'
In organic production of :crop_en, avoid synthetic pesticides and GMOs, relying on natural methods. Maintain soil health with compost, manure, and green fertilizers. Use biopreparations (neem oil, Trichoderma) and natural enemies against pests.

To obtain organic certification, complete a 3-year transition period. Document all treatment processes and prepare for external audits. Store and transport organic products separately from conventional ones. Market prices can be 30-50% higher than conventional.
EN,
                ],
            ],
            [
                'key' => 'drip-irrigation-setup',
                'tags' => ['irrigation', 'water-efficiency'],
                'icon' => 'drip',
                'title' => [
                    'uz' => ':crop_uz uchun tomchilatib sug\'orish tizimini o\'rnatish',
                    'ru' => 'Установка капельного орошения для :crop_ru',
                    'oz' => 'Setting Up Drip Irrigation for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Tizim dizayni, tomchilatgichlar tanlash va suv bosimini sozlash.',
                    'ru' => 'Проектирование системы, выбор капельниц и настройка давления воды.',
                    'oz' => 'System design, dripper selection, and water pressure calibration.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun tomchilatib sug\'orish tizimini loyihalashda qator oralig\'i va o\'simlik ehtiyojini hisobga oling. Tomchilatgichlar orasidagi masofa 30-40 sm bo\'lishi kerak. Suv bosimini 1-1.5 bar da ushlab turing va filtrlarni muntazam tozalang.

Asosiy quvurdan tarmoqlangan liniyalarni o\'tkazib, har bir zonada nazorat klapanlari o\'rnating. Fertigatsiya uchun Venturi injektori yoki dozator nasosini ulang. Tizimni ishga tushirishdan oldin suvni 30 daqiqa oqizib, quvurlardagi ifloslanishni chiqaring. Haftada bir marta tomchilatgichlarni tekshiring va tiqilganlarini tozalang.
UZ,
                    'ru' => <<<'RU'
При проектировании капельного орошения для :crop_ru учитывайте расстояние между рядами и потребности растений. Расстояние между капельницами должно быть 30-40 см. Поддерживайте давление воды на уровне 1-1.5 бар и регулярно очищайте фильтры.

От магистрального трубопровода проведите разветвленные линии, установив контрольные клапаны в каждой зоне. Для фертигации подключите инжектор Вентури или дозирующий насос. Перед запуском системы промойте водой 30 минут, чтобы удалить загрязнения из труб. Еженедельно проверяйте капельницы и очищайте засорённые.
RU,
                    'oz' => <<<'EN'
When designing drip irrigation for :crop_en, consider row spacing and plant requirements. Distance between drippers should be 30-40 cm. Maintain water pressure at 1-1.5 bar and clean filters regularly.

Run branched lines from the main pipeline, installing control valves in each zone. For fertigation, connect a Venturi injector or dosing pump. Before system startup, flush with water for 30 minutes to remove debris from pipes. Check drippers weekly and clean clogged ones.
EN,
                ],
            ],
            [
                'key' => 'mulching-techniques',
                'tags' => ['soil-management', 'water-conservation'],
                'icon' => 'mulch',
                'title' => [
                    'uz' => ':crop_uz dalalarida mulchalash usullari',
                    'ru' => 'Техники мульчирования для полей :crop_ru',
                    'oz' => 'Mulching Techniques for :crop_en Fields',
                ],
                'preview' => [
                    'uz' => 'Organik va sintetik mulcha, namlikni saqlash va begona o\'tlarni bostirish.',
                    'ru' => 'Органическая и синтетическая мульча, сохранение влаги и подавление сорняков.',
                    'oz' => 'Organic and synthetic mulch, moisture retention, and weed suppression.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz dalalarida 5-7 sm qalinlikdagi organik mulcha (somon, kompost, yog\'och qirindilari) namlikni 30-40% gacha saqlaydi va tuproq haroratini barqarorlashtiradi. Mulchani ekishdan keyin darhol qo\'llang, o\'simlik poyasidan 5-10 sm masofani bo\'sh qoldiring.

Polietilen plyonka begona o\'tlarni 95% gacha kamaytiradi va dastlabki o\'sishni tezlashtiradi. Qora plyonka issiqlikni yutadi, oq plyonka esa qaytaradi. Organik mulcha parchalanib tuproqqa ozuqa qo\'shadi. Mavsumda mulcha qalinligini tekshirib, kerak bo\'lsa yangilang.
UZ,
                    'ru' => <<<'RU'
На полях :crop_ru органическая мульча толщиной 5-7 см (солома, компост, древесная щепа) сохраняет 30-40% влаги и стабилизирует температуру почвы. Применяйте мульчу сразу после посадки, оставляя 5-10 см расстояния от стебля растения.

Полиэтиленовая пленка сокращает сорняки на 95% и ускоряет начальный рост. Черная пленка поглощает тепло, белая — отражает. Органическая мульча разлагается, добавляя питательные вещества в почву. В течение сезона проверяйте толщину мульчи и обновляйте при необходимости.
RU,
                    'oz' => <<<'EN'
On :crop_en fields, 5-7 cm thick organic mulch (straw, compost, wood chips) retains 30-40% moisture and stabilizes soil temperature. Apply mulch immediately after planting, leaving 5-10 cm distance from plant stem.

Polyethylene film reduces weeds by 95% and accelerates early growth. Black film absorbs heat, white reflects it. Organic mulch decomposes, adding nutrients to soil. During the season, check mulch thickness and refresh as needed.
EN,
                ],
            ],
            [
                'key' => 'composting',
                'tags' => ['organic-matter', 'soil-fertility'],
                'icon' => 'compost',
                'title' => [
                    'uz' => ':crop_uz uchun sifatli kompost tayyorlash',
                    'ru' => 'Приготовление качественного компоста для :crop_ru',
                    'oz' => 'Preparing Quality Compost for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Qatlamli texnologiya, havo almashinuvi va kompostning pishishi.',
                    'ru' => 'Слоистая технология, аэрация и созревание компоста.',
                    'oz' => 'Layering technique, aeration, and compost maturation.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun kompost tayyorlashda yashil massa (o\'t-o\'lan, o\'simlik qoldiqlari) va jigarrang massa (quruq barglar, somon) nisbatini 2:1 da ushlab turing. Qatlamlarni 15-20 sm qalinlikda joylashtiring va har qatlamga ozgina tuproq va chirindi qo\'shing.

Kompost uyasining o\'lchamini 1x1x1 m da saqlang va namlikni 50-60% da nazorat qiling. Har 2-3 haftada kompostni aralashtirib havo kiriting. Harorat 60-70°C ga ko\'tarilishi zararlangan urug\'lar va patogenlarni yo\'q qiladi. 3-4 oydan keyin kompost qora, tuprog\'simon va yoqimli hidli bo\'lishi kerak.
UZ,
                    'ru' => <<<'RU'
При приготовлении компоста для :crop_ru поддерживайте соотношение зелёной массы (трава, растительные остатки) и коричневой массы (сухие листья, солома) 2:1. Укладывайте слои толщиной 15-20 см, добавляя в каждый слой немного почвы и навоза.

Сохраняйте размер компостной кучи 1x1x1 м и контролируйте влажность на уровне 50-60%. Каждые 2-3 недели перемешивайте компост для аэрации. Температура должна подняться до 60-70°C, уничтожая семена сорняков и патогены. Через 3-4 месяца компост должен стать тёмным, почвоподобным и иметь приятный запах.
RU,
                    'oz' => <<<'EN'
When preparing compost for :crop_en, maintain a 2:1 ratio of green mass (grass, plant residues) to brown mass (dry leaves, straw). Layer materials 15-20 cm thick, adding some soil and manure to each layer.

Keep compost pile size at 1x1x1 m and control moisture at 50-60%. Turn compost every 2-3 weeks for aeration. Temperature should reach 60-70°C, destroying weed seeds and pathogens. After 3-4 months, compost should be dark, soil-like, and have a pleasant smell.
EN,
                ],
            ],
            [
                'key' => 'greenhouse-management',
                'tags' => ['protected-cultivation', 'climate-control'],
                'icon' => 'greenhouse',
                'title' => [
                    'uz' => 'Issiqxonada :crop_uz yetishtirish texnologiyasi',
                    'ru' => 'Технология выращивания :crop_ru в теплице',
                    'oz' => 'Greenhouse Production Technology for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Harorat, namlik va shamollatishni avtomatik nazorat qilish.',
                    'ru' => 'Автоматический контроль температуры, влажности и вентиляции.',
                    'oz' => 'Automated control of temperature, humidity, and ventilation.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
Issiqxonada :crop_uz uchun kunduzgi haroratni 22-26°C, tungi haroratni 16-18°C da ushlab turing. Nisbiy namlikni 60-70% atrofida nazorat qilib, qo\'ziqorin kasalliklarini oldini oling. Avtomatik shamollatish tizimlarini o\'rnatib, issiq kunlarda havo almashinuvini ta\'minlang.

Yorug\'lik intensivligi 30,000-50,000 lux bo\'lishi kerak; qishda qo\'shimcha yoritish lampalari ishlatiladi. Tomchilatib sug\'orish va fertigatsiya orqali ozuqalarni aniq berib, EC darajasini 1.5-2.0 dS/m da saqlang. Issiqxona shishasini tez-tez tozalang va soyalash to\'rlarini yozda qo\'llang.
UZ,
                    'ru' => <<<'RU'
В теплице для :crop_ru поддерживайте дневную температуру 22-26°C, ночную 16-18°C. Контролируйте относительную влажность на уровне 60-70%, предотвращая грибковые заболевания. Установите автоматические системы вентиляции для обеспечения воздухообмена в жаркие дни.

Интенсивность освещения должна быть 30,000-50,000 люкс; зимой используйте дополнительные лампы. Через капельное орошение и фертигацию точно подавайте питательные вещества, поддерживая EC на уровне 1.5-2.0 дС/м. Регулярно очищайте стекла теплицы и применяйте затеняющие сетки летом.
RU,
                    'oz' => <<<'EN'
In greenhouse for :crop_en, maintain daytime temperature at 22-26°C, nighttime at 16-18°C. Control relative humidity around 60-70% to prevent fungal diseases. Install automated ventilation systems to ensure air exchange on hot days.

Light intensity should be 30,000-50,000 lux; use supplemental lamps in winter. Through drip irrigation and fertigation, precisely deliver nutrients, maintaining EC at 1.5-2.0 dS/m. Regularly clean greenhouse glass and apply shading nets in summer.
EN,
                ],
            ],
            [
                'key' => 'crop-insurance',
                'tags' => ['risk-management', 'farm-economics'],
                'icon' => 'insurance',
                'title' => [
                    'uz' => ':crop_uz uchun sug\'urta va xavflarni boshqarish',
                    'ru' => 'Страхование и управление рисками для :crop_ru',
                    'oz' => 'Crop Insurance and Risk Management for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Hosil sug\'urtasi, tabiiy ofatlar va moliyaviy himoya.',
                    'ru' => 'Страхование урожая, природные бедствия и финансовая защита.',
                    'oz' => 'Yield insurance, natural disasters, and financial protection.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ishlab chiqarishida hosil sug\'urtasi do\'l, suv toshqini va qurg\'oqchilik xavflarini kamaytiradi. Sug\'urta shartnomalarini ekishdan oldin tuzib, barcha dalalarni ro\'yxatga oling. Fotosurat va GPS koordinatalarini saqlang.

Xavflarni diversifikatsiya qilish uchun turli navlar va ekin turlarini ekib, moliyaviy zaxiralarni shakllantiring. Tabiiy ofat sodir bo\'lganda darhol sug\'urta kompaniyasiga xabar bering va zararni hujjatlashtiring. Yillik hosil ma\'lumotlarini to\'plang va risk tahlilini o\'tkazing.
UZ,
                    'ru' => <<<'RU'
В производстве :crop_ru страхование урожая снижает риски града, наводнений и засухи. Заключайте страховые договоры до посадки, регистрируя все поля. Сохраняйте фотографии и GPS-координаты.

Для диверсификации рисков высаживайте разные сорта и виды культур, формируйте финансовые резервы. При наступлении стихийного бедствия немедленно уведомляйте страховую компанию и документируйте ущерб. Собирайте ежегодные данные об урожае и проводите анализ рисков.
RU,
                    'oz' => <<<'EN'
In :crop_en production, crop insurance reduces risks of hail, floods, and drought. Sign insurance contracts before planting, registering all fields. Keep photographs and GPS coordinates.

To diversify risks, plant different varieties and crop types, build financial reserves. When natural disaster occurs, immediately notify insurance company and document damage. Collect annual yield data and conduct risk analysis.
EN,
                ],
            ],
            [
                'key' => 'cover-cropping',
                'tags' => ['soil-fertility', 'sustainable-farming'],
                'icon' => 'cover-crop',
                'title' => [
                    'uz' => ':crop_uz ketidan qoplovchi ekinlar ekish',
                    'ru' => 'Посев покровных культур после :crop_ru',
                    'oz' => 'Cover Cropping After :crop_en',
                ],
                'preview' => [
                    'uz' => 'Tuproq eroziyasini kamaytirish, azot to\'plash va organik massa.',
                    'ru' => 'Снижение эрозии почвы, накопление азота и органическая масса.',
                    'oz' => 'Reducing soil erosion, nitrogen fixation, and organic mass.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz hosilini yig\'ib olgandan so\'ng qoplovchi ekinlar (dukkaklilar, raps, gorchitsa) ekib, tuproq eroziyasini 60-80% gacha kamaytiring. Dukkakli qoplovchilar (beda, no\'xat) ildizida 40-60 kg/ga azot to\'playdi va keyingi ekinga beradi.

Qoplovchi ekinlarni bahorda gullashdan oldin kesib, tuproqqa mulcha sifatida qoldiring yoki haydab aralashtiring. Bu tuproq tuzilmasini yaxshilaydi va organik uglerodni oshiradi. Gorchitsa va raps ildiz zararkunandalarini kamaytiradi va tuproqni tabiiy ravishda sanitatsiyalaydi.
UZ,
                    'ru' => <<<'RU'
После уборки урожая :crop_ru посев покровных культур (бобовые, рапс, горчица) снижает эрозию почвы на 60-80%. Бобовые покровники (вика, горох) накапливают 40-60 кг/га азота в корневых клубеньках и передают следующей культуре.

Скашивайте покровные культуры весной до цветения, оставляя их на поле как мульчу или запахивая в почву. Это улучшает структуру почвы и повышает органический углерод. Горчица и рапс снижают корневых вредителей и естественно санируют почву.
RU,
                    'oz' => <<<'EN'
After harvesting :crop_en, sowing cover crops (legumes, rape, mustard) reduces soil erosion by 60-80%. Legume covers (vetch, peas) accumulate 40-60 kg/ha of nitrogen in root nodules and transfer it to the next crop.

Mow cover crops in spring before flowering, leaving them as mulch or plowing into soil. This improves soil structure and increases organic carbon. Mustard and rape reduce root pests and naturally sanitize soil.
EN,
                ],
            ],
            [
                'key' => 'precision-agriculture',
                'tags' => ['digital-farming', 'technology'],
                'icon' => 'gps',
                'title' => [
                    'uz' => ':crop_uz dalalarida aniq qishloq xo\'jaligi texnologiyalari',
                    'ru' => 'Технологии точного земледелия для полей :crop_ru',
                    'oz' => 'Precision Agriculture Technologies for :crop_en Fields',
                ],
                'preview' => [
                    'uz' => 'GPS, dronlar, tuproq xaritalash va o\'zgaruvchan normali qo\'llash.',
                    'ru' => 'GPS, дроны, картирование почвы и дифференцированное внесение.',
                    'oz' => 'GPS, drones, soil mapping, and variable rate application.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz dalalarini dronlar yordamida NDVI xaritalarini yaratib, o\'simliklarning fotosintez faoliyatini kuzatib boring. GPS nazoratli traktorlar va ekish mashinalari aniqligini 2-5 sm gacha oshiradi. Tuproq namligini sensorlar orqali real vaqtda monitoring qiling.

Tuproq tahlillari asosida o\'zgaruvchan normali qo\'llash (VRA) texnologiyasini ishlatib, o\'g\'itlarni har bir zonaning ehtiyojiga qarab bering. Bu xarajatlarni 15-20% kamaytiradi va hosildorlikni oshiradi. Barcha ma\'lumotlarni bulut serverlarida saqlang va tahlil qiling.
UZ,
                    'ru' => <<<'RU'
Создавайте NDVI-карты полей :crop_ru с помощью дронов, отслеживая фотосинтетическую активность растений. Тракторы и сеялки с GPS-контролем повышают точность до 2-5 см. Мониторьте влажность почвы сенсорами в реальном времени.

На основе почвенных анализов используйте технологию дифференцированного внесения (VRA), подавая удобрения по потребностям каждой зоны. Это снижает затраты на 15-20% и повышает урожайность. Храните и анализируйте все данные на облачных серверах.
RU,
                    'oz' => <<<'EN'
Create NDVI maps of :crop_en fields using drones to track photosynthetic activity. GPS-guided tractors and seeders improve accuracy to 2-5 cm. Monitor soil moisture with sensors in real-time.

Based on soil analyses, use variable rate application (VRA) technology, delivering fertilizers according to each zone's needs. This reduces costs by 15-20% and increases yields. Store and analyze all data on cloud servers.
EN,
                ],
            ],
            [
                'key' => 'water-quality',
                'tags' => ['irrigation', 'water-management'],
                'icon' => 'water-test',
                'title' => [
                    'uz' => ':crop_uz sug\'orish suvi sifatini baholash',
                    'ru' => 'Оценка качества оросительной воды для :crop_ru',
                    'oz' => 'Assessing Irrigation Water Quality for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Sho\'rlanish, pH, tarkibdagi tuzlar va filtratsiya.',
                    'ru' => 'Засоление, pH, состав солей и фильтрация.',
                    'oz' => 'Salinity, pH, salt composition, and filtration.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun sug\'orish suvini har mavsumda kamida 2 marta laboratoriyada tahlil qiling. EC darajasi 0.7 dS/m dan yuqori bo\'lsa, sho\'rlanish xavfi bor. pH darajasi 6.5-7.5 oralig\'ida bo\'lishi kerak. Natriy adsorbsiya nisbati (SAR) 3 dan past bo\'lganda tuproq tuzilmasi saqlanadi.

Yuqori sho\'rli suvdan foydalanganda yuvish suv berish (10-15% ortiqcha) qo\'llang. Mexanik zarrachalarni olib tashlash uchun qumoq va diskli filtrlarni o\'rnating. Temir va magniy yuqori bo\'lsa, tomchilatgichlar tiqilishi mumkin — kislota bilan tozalash kerak bo\'ladi.
UZ,
                    'ru' => <<<'RU'
Анализируйте оросительную воду для :crop_ru в лаборатории минимум 2 раза за сезон. Если уровень EC выше 0.7 дС/м, есть риск засоления. Уровень pH должен быть в диапазоне 6.5-7.5. При коэффициенте адсорбции натрия (SAR) ниже 3 сохраняется структура почвы.

При использовании высокосолёной воды применяйте промывные поливы (10-15% избыточно). Для удаления механических частиц установите песочные и дисковые фильтры. При высоком содержании железа и магния капельницы могут засоряться — потребуется кислотная очистка.
RU,
                    'oz' => <<<'EN'
Analyze irrigation water for :crop_en in laboratory at least twice per season. If EC level exceeds 0.7 dS/m, there is salinity risk. pH level should be in 6.5-7.5 range. When sodium adsorption ratio (SAR) is below 3, soil structure is preserved.

When using high-salinity water, apply leaching irrigation (10-15% excess). Install sand and disc filters to remove mechanical particles. With high iron and magnesium content, drippers may clog — acid cleaning will be needed.
EN,
                ],
            ],
            [
                'key' => 'mechanization',
                'tags' => ['farm-equipment', 'efficiency'],
                'icon' => 'tractor',
                'title' => [
                    'uz' => ':crop_uz ishlab chiqarishini mexanizatsiyalashtirish',
                    'ru' => 'Механизация производства :crop_ru',
                    'oz' => 'Mechanizing :crop_en Production',
                ],
                'preview' => [
                    'uz' => 'Texnika tanlash, xizmat ko\'rsatish va iqtisodiy samaradorlik.',
                    'ru' => 'Выбор техники, обслуживание и экономическая эффективность.',
                    'oz' => 'Equipment selection, maintenance, and economic efficiency.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ekish va hosil yig\'ish uchun to\'g\'ri texnikani tanlash mehnat xarajatlarini 50-70% gacha kamaytiradi. Traktor quvvatini maydonga qarab hisoblang: 100 ha gacha — 80-100 ot kuchi, 100-300 ha — 120-150 ot kuchi. Qo\'shimcha jihozlar (ekish mashinasi, purkagich, kombayn) bilan mosligini tekshiring.

Texnikani muntazam xizmat ko\'rsatib, yog\', filtrlar va podshipniklarni vaqtida almashtiring. Yillik texnik ko\'rikdan o\'tkazing va ta\'mirlarni mavsumdan oldin bajaring. Yoqilg\'i sarfini kamaytirish uchun haydovchilarni o\'qitib, GPS va telemtriya tizimlarini o\'rnating. Kooperativ orqali qimmat texnikani ijaraga bering.
UZ,
                    'ru' => <<<'RU'
Правильный выбор техники для посадки и уборки :crop_ru снижает трудозатраты на 50-70%. Рассчитывайте мощность трактора по площади: до 100 га — 80-100 л.с., 100-300 га — 120-150 л.с. Проверяйте совместимость с навесным оборудованием (сеялки, опрыскиватели, комбайны).

Регулярно обслуживайте технику, своевременно меняя масло, фильтры и подшипники. Проводите ежегодный техосмотр и выполняйте ремонт до начала сезона. Для снижения расхода топлива обучайте операторов и устанавливайте GPS и телематику. Арендуйте дорогую технику через кооператив.
RU,
                    'oz' => <<<'EN'
Proper equipment selection for planting and harvesting :crop_en reduces labor costs by 50-70%. Calculate tractor power by area: up to 100 ha — 80-100 hp, 100-300 ha — 120-150 hp. Check compatibility with attachments (seeders, sprayers, combines).

Regularly service equipment, timely changing oil, filters, and bearings. Conduct annual inspection and perform repairs before season. To reduce fuel consumption, train operators and install GPS and telematics. Rent expensive equipment through cooperative.
EN,
                ],
            ],
            [
                'key' => 'market-linkages',
                'tags' => ['marketing', 'value-chain'],
                'icon' => 'market',
                'title' => [
                    'uz' => ':crop_uz bozorga chiqarish strategiyalari',
                    'ru' => 'Стратегии выхода на рынок для :crop_ru',
                    'oz' => 'Market Access Strategies for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Xaridorlar bilan shartnomalar, narx tahlili va to\'g\'ridan-to\'g\'ri savdo.',
                    'ru' => 'Контракты с покупателями, анализ цен и прямые продажи.',
                    'oz' => 'Buyer contracts, price analysis, and direct marketing.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ni sotishdan oldin bozor narxlarini tahlil qiling va mavsumiy o\'zgarishlarni kuzating. Xaridorlar bilan ekishdan oldin shartnomalar tuzib, narx va sifat talablarini belgilang. Kooperativ orqali yig\'ma sotish hajmni oshiradi va narxni yaxshilaydi.

Sifat sertifikatlarini (GlobalGAP, organik) olib, eksport bozorlariga kirish imkoniyatini yarating. To\'g\'ridan-to\'g\'ri savdo (fermer bozorlari, CSA) oraliq xarajatlarni kamaytiradi. Onlayn platformalar va ijtimoiy tarmoqlar orqali marketing qiling. Mahsulotni qayta ishlash orqali qiymat qo\'shing.
UZ,
                    'ru' => <<<'RU'
Перед продажей :crop_ru анализируйте рыночные цены и отслеживайте сезонные колебания. Заключайте контракты с покупателями до посадки, фиксируя цены и требования к качеству. Коллективная продажа через кооператив увеличивает объём и улучшает цены.

Получите сертификаты качества (GlobalGAP, органик) для доступа к экспортным рынкам. Прямые продажи (фермерские рынки, CSA) снижают посреднические издержки. Проводите маркетинг через онлайн-платформы и социальные сети. Добавляйте ценность через переработку продукции.
RU,
                    'oz' => <<<'EN'
Before selling :crop_en, analyze market prices and track seasonal fluctuations. Sign contracts with buyers before planting, fixing prices and quality requirements. Collective selling through cooperative increases volume and improves prices.

Obtain quality certificates (GlobalGAP, organic) for export market access. Direct sales (farmers markets, CSA) reduce intermediary costs. Market through online platforms and social networks. Add value through product processing.
EN,
                ],
            ],
            [
                'key' => 'climate-smart',
                'tags' => ['climate-adaptation', 'sustainability'],
                'icon' => 'climate-smart',
                'title' => [
                    'uz' => ':crop_uz uchun aqlli iqlim qishloq xo\'jaligi amaliyoti',
                    'ru' => 'Климатически адаптивное сельское хозяйство для :crop_ru',
                    'oz' => 'Climate-Smart Agriculture Practices for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Uglerod saqlash, iqlimga chidamli navlar va ekotizim xizmatlari.',
                    'ru' => 'Секвестрация углерода, устойчивые сорта и экосистемные услуги.',
                    'oz' => 'Carbon sequestration, climate-resilient varieties, and ecosystem services.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ishlab chiqarishida iqlim o\'zgarishiga moslashish uchun issiqlik va qurg\'oqchilikka chidamli navlarni tanlang. Tuproqda uglerod saqlashni oshirish maqsadida minimal haydash, qoplovchi ekinlar va agroleschilikni qo\'llang. Bu hektariga yiliga 1-2 tonna CO2 ni saqlaydi.

Suv tejovchi texnologiyalar (tomchilatib sug\'orish, mulchlash) va qayta tiklanadigan energiya (quyosh panellari) dan foydalaning. Biologik xilma-xillikni saqlash uchun dala chetlariga mahalliy o\'simliklar eking. Iqlim prognozlarini kuzatib, fenologik jadvalni moslashtiring va sug\'urta imkoniyatlarini o\'rganing.
UZ,
                    'ru' => <<<'RU'
Для адаптации к изменению климата в производстве :crop_ru выбирайте жаро- и засухоустойчивые сорта. Для повышения углеродного депонирования применяйте минимальную обработку, покровные культуры и агролесоводство. Это секвестрирует 1-2 тонны CO2 на гектар в год.

Используйте водосберегающие технологии (капельное орошение, мульчирование) и возобновляемую энергию (солнечные панели). Для сохранения биоразнообразия высаживайте местные растения по краям полей. Отслеживайте климатические прогнозы, корректируйте фенологический график и изучайте страховые возможности.
RU,
                    'oz' => <<<'EN'
To adapt to climate change in :crop_en production, select heat and drought-resistant varieties. To increase carbon sequestration, apply minimal tillage, cover crops, and agroforestry. This sequesters 1-2 tons of CO2 per hectare annually.

Use water-saving technologies (drip irrigation, mulching) and renewable energy (solar panels). To preserve biodiversity, plant native species along field edges. Monitor climate forecasts, adjust phenological schedules, and explore insurance options.
EN,
                ],
            ],
            [
                'key' => 'biological-control',
                'tags' => ['biocontrol', 'integrated-pest-management'],
                'icon' => 'beneficial-insects',
                'title' => [
                    'uz' => ':crop_uz da biologik himoya usullari',
                    'ru' => 'Методы биологической защиты для :crop_ru',
                    'oz' => 'Biological Control Methods for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Foydali hasharotlar, parazitoitlar va mikrobial preparatlar.',
                    'ru' => 'Полезные насекомые, паразитоиды и микробные препараты.',
                    'oz' => 'Beneficial insects, parasitoids, and microbial agents.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz dalalarida foydali hasharotlarni (xato\'buqalar, parazit ariqchalar) jalb qilish uchun gul aralashmalari eking va kimyoviy ishlovlarni kamaytiring. Trichogramma va encarsia kabi parazitoidlarni biotexnik usulda ko\'paytirib, zararkunanda tuxumlariga chiqaring.

Mikrobial preparatlar (Bacillus thuringiensis, Beauveria bassiana, Metarhizium) zararlarga selektiv ta\'sir ko\'rsatadi va foydalilarni saqlab qoladi. Ishlov berishni kechqurun bajaring va preparatlarni ultrabinafsha nuridan himoya qiling. Biologik nazorat kimyoviy ishlovlarni 40-60% gacha kamaytiradi.
UZ,
                    'ru' => <<<'RU'
Для привлечения полезных насекомых (божьи коровки, паразитические осы) на поля :crop_ru высевайте цветочные смеси и снижайте химические обработки. Биотехнически размножайте паразитоидов как трихограмма и энкарзия, выпуская их на яйца вредителей.

Микробные препараты (Bacillus thuringiensis, Beauveria bassiana, Metarhizium) избирательно воздействуют на вредителей, сохраняя полезных. Проводите обработки вечером и защищайте препараты от ультрафиолета. Биоконтроль снижает химические обработки на 40-60%.
RU,
                    'oz' => <<<'EN'
To attract beneficial insects (ladybugs, parasitic wasps) to :crop_en fields, sow flower mixes and reduce chemical treatments. Biotechnically multiply parasitoids like Trichogramma and Encarsia, releasing them onto pest eggs.

Microbial agents (Bacillus thuringiensis, Beauveria bassiana, Metarhizium) selectively target pests while preserving beneficials. Apply treatments in evening and protect agents from UV radiation. Biocontrol reduces chemical treatments by 40-60%.
EN,
                ],
            ],
            [
                'key' => 'water-harvesting',
                'tags' => ['water-management', 'sustainability'],
                'icon' => 'water-harvest',
                'title' => [
                    'uz' => ':crop_uz dalalarida yomg\'ir suvini yig\'ish',
                    'ru' => 'Сбор дождевой воды для полей :crop_ru',
                    'oz' => 'Rainwater Harvesting for :crop_en Fields',
                ],
                'preview' => [
                    'uz' => 'Suv havzalari, to\'g\'onlar va yer shakllantirish texnikalari.',
                    'ru' => 'Водные резервуары, дамбы и техники формирования рельефа.',
                    'oz' => 'Water ponds, bunds, and land-shaping techniques.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz dalalarida yomg\'ir suvini samarali to\'plash uchun dala chetlarida suv havzalari qurib, 200-500 m³ hajmda suv saqlang. To\'g\'onlar va yo\'laklar orqali oqib keluvchi suvni yo\'naltiring va eroziyani kamaytiring. Suvni qurg\'oqchilik paytida qo\'shimcha sug\'orish uchun ishlating.

Qiyal dalalarda kontur haydash va terraslar qurish suvni o\'simliklar zonasida ushlab turadi. Yer shakllantirish texnikalari (swales, bunds) orqali yuzaki oqimni sekinlashtiring va infiltratsiyani oshiring. Polietilen yoki loy bilan qoplangan havzalar bug\'lanishni 30-40% kamaytiradi.
UZ,
                    'ru' => <<<'RU'
Для эффективного сбора дождевой воды на полях :crop_ru постройте резервуары по краям поля объёмом 200-500 м³. Через дамбы и каналы направляйте стекающую воду и снижайте эрозию. Используйте воду для дополнительного орошения в засушливые периоды.

На склонах применяйте контурную вспашку и террасы, удерживая воду в корневой зоне. Техники формирования рельефа (swales, bunds) замедляют поверхностный сток и повышают инфильтрацию. Резервуары с полиэтиленовой или глиняной облицовкой снижают испарение на 30-40%.
RU,
                    'oz' => <<<'EN'
For effective rainwater collection on :crop_en fields, build ponds along field edges with 200-500 m³ capacity. Through bunds and channels, direct runoff water and reduce erosion. Use water for supplemental irrigation during dry periods.

On slopes, apply contour plowing and terraces, retaining water in root zone. Land-shaping techniques (swales, bunds) slow surface runoff and increase infiltration. Ponds with polyethylene or clay lining reduce evaporation by 30-40%.
EN,
                ],
            ],
            [
                'key' => 'leaf-analysis',
                'tags' => ['nutrient-management', 'diagnostics'],
                'icon' => 'lab',
                'title' => [
                    'uz' => ':crop_uz barg tahlili va ozuqa diagnostikasi',
                    'ru' => 'Листовой анализ и диагностика питания :crop_ru',
                    'oz' => 'Leaf Analysis and Nutrient Diagnostics for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Namunalar olish, laboratoriya tahlillari va to\'g\'rilash choralari.',
                    'ru' => 'Отбор проб, лабораторные анализы и корректирующие меры.',
                    'oz' => 'Sample collection, laboratory tests, and corrective measures.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz o\'sishining faol fazasida har 2 haftada barg namunalari oling. O\'rta yoshdagi to\'liq rivojlangan barglarni ertalab soat 8-10 oralig\'ida 20-25 ta o\'simlikdan yig\'ing. Namunalarni qog\'oz qoplarga solib, laboratoriyaga 24 soat ichida yuboring.

Tahlil natijalari asosida nitrogen, fosfor, kaliy va mikroelementlar (sink, temir, bor) darajasini baholang. Kamchilik aniqlanganda bargdan purkash yoki tuproqqa qo\'shimcha kiritish orqali tuzating. Barg tahlili tuproq tahlilini to\'ldiradi va real vaqtda ozuqa holatini ko\'rsatadi.
UZ,
                    'ru' => <<<'RU'
В активной фазе роста :crop_ru отбирайте листовые пробы каждые 2 недели. Собирайте средневозрастные полностью развитые листья утром с 8 до 10 часов с 20-25 растений. Поместите пробы в бумажные конверты и отправьте в лабораторию в течение 24 часов.

По результатам анализа оцените уровни азота, фосфора, калия и микроэлементов (цинк, железо, бор). При выявлении дефицита корректируйте листовыми опрыскиваниями или внесением в почву. Листовой анализ дополняет почвенный и показывает состояние питания в реальном времени.
RU,
                    'oz' => <<<'EN'
During active growth phase of :crop_en, collect leaf samples every 2 weeks. Gather middle-aged fully developed leaves in morning between 8-10 AM from 20-25 plants. Place samples in paper envelopes and send to laboratory within 24 hours.

Based on analysis results, assess levels of nitrogen, phosphorus, potassium, and micronutrients (zinc, iron, boron). When deficiency is detected, correct with foliar sprays or soil application. Leaf analysis complements soil analysis and shows real-time nutrient status.
EN,
                ],
            ],
            [
                'key' => 'pollinator-conservation',
                'tags' => ['pollination', 'biodiversity'],
                'icon' => 'bee',
                'title' => [
                    'uz' => ':crop_uz changlatuvchilarni saqlash strategiyalari',
                    'ru' => 'Стратегии сохранения опылителей для :crop_ru',
                    'oz' => 'Pollinator Conservation Strategies for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Asalarilar, yovvoyi changlatuvchilar va ularning yashash muhiti.',
                    'ru' => 'Пчёлы, дикие опылители и среда их обитания.',
                    'oz' => 'Honeybees, wild pollinators, and their habitat.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz hosildorligini oshirish uchun changlatuvchilarni saqlash muhimdir. Dala chetlarida gul aralashmalari (lavanda, rayhon, yovvoyi gullar) ekib, yil davomida nektar va polen manbai yarating. Asalarilar uchun uyalar qo\'ying va suv manbalari ta\'minlang.

Kimyoviy ishlovlarni gullash vaqtida to\'xtatib, changlatuvchilarni himoya qiling. Yovvoyi asalarilar uchun tabiiy uyalar (quruq poyalar, tuproq uyalari) qoldiring. Monokulyura o\'rniga turli xil ekinlar ekish biologik xilma-xillikni oshiradi. Changlatuvchilar hosildorlikni 20-40% ga oshirishi mumkin.
UZ,
                    'ru' => <<<'RU'
Для повышения урожайности :crop_ru важно сохранять опылителей. Высаживайте цветочные смеси (лаванда, базилик, полевые цветы) по краям полей, создавая источники нектара и пыльцы круглый год. Размещайте ульи для пчёл и обеспечивайте водопой.

Останавливайте химические обработки во время цветения, защищая опылителей. Оставляйте естественные гнёзда для диких пчёл (сухие стебли, земляные норы). Вместо монокультуры высаживайте разнообразные культуры, повышая биоразнообразие. Опылители могут повысить урожайность на 20-40%.
RU,
                    'oz' => <<<'EN'
To increase :crop_en yields, pollinator conservation is crucial. Plant flower mixes (lavender, basil, wildflowers) along field edges, creating year-round nectar and pollen sources. Place bee hives and provide water sources.

Stop chemical treatments during flowering, protecting pollinators. Leave natural nests for wild bees (dry stems, ground burrows). Instead of monoculture, plant diverse crops, increasing biodiversity. Pollinators can boost yields by 20-40%.
EN,
                ],
            ],
            [
                'key' => 'farm-record-keeping',
                'tags' => ['farm-management', 'digital-farming'],
                'icon' => 'records',
                'title' => [
                    'uz' => ':crop_uz ishlab chiqarishida hisobotlar yuritish',
                    'ru' => 'Ведение учёта в производстве :crop_ru',
                    'oz' => 'Record Keeping for :crop_en Production',
                ],
                'preview' => [
                    'uz' => 'Xarajatlar, hosildorlik, ishlov berish va moliyaviy tahlil.',
                    'ru' => 'Затраты, урожайность, обработки и финансовый анализ.',
                    'oz' => 'Costs, yields, treatments, and financial analysis.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ishlab chiqarishining barcha jarayonlarini qayd qiling: ekish sanasi, nav, urug\' miqdori, o\'g\'it va pestitsid ishlovi, sug\'orish, hosildorlik. Raqamli platformalar (Excel, mobil ilovalar, farm management software) yordamida ma\'lumotlarni tartibga soling.

Xarajatlarni kategoriyalarga ajrating: urug\', o\'g\'it, pestitsidlar, yoqilg\'i, mehnat, texnika, sug\'orish. Hosildorlik va sifat ko\'rsatkichlarini yozib, rentabellikni hisoblang. Hisobotlar davlat sertifikatsiyasi, kredit olish va qishloq xo\'jaligi sug\'urtasi uchun zarur. Yillik tahlil qilish ertaga rejalashtirish uchun asos yaratadi.
UZ,
                    'ru' => <<<'RU'
Записывайте все процессы производства :crop_ru: дата посадки, сорт, количество семян, обработки удобрениями и пестицидами, полив, урожайность. Упорядочивайте данные с помощью цифровых платформ (Excel, мобильные приложения, ПО для управления фермой).

Разделите затраты по категориям: семена, удобрения, пестициды, топливо, труд, техника, орошение. Записывайте урожайность и показатели качества, рассчитывайте рентабельность. Записи необходимы для государственной сертификации, получения кредитов и агрострахования. Ежегодный анализ создаёт основу для планирования будущего.
RU,
                    'oz' => <<<'EN'
Record all processes of :crop_en production: planting date, variety, seed quantity, fertilizer and pesticide treatments, irrigation, yields. Organize data using digital platforms (Excel, mobile apps, farm management software).

Divide costs into categories: seeds, fertilizers, pesticides, fuel, labor, equipment, irrigation. Record yields and quality indicators, calculate profitability. Records are necessary for government certification, credit applications, and agricultural insurance. Annual analysis creates foundation for future planning.
EN,
                ],
            ],
            [
                'key' => 'rootstock-selection',
                'tags' => ['orchard-management', 'crop-planning'],
                'icon' => 'rootstock',
                'title' => [
                    'uz' => ':crop_uz uchun podvoy tanlash mezonlari',
                    'ru' => 'Критерии выбора подвоя для :crop_ru',
                    'oz' => 'Rootstock Selection Criteria for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Tuproq muvofiqligi, kasalliklarga chidamlilik va hosildorlik.',
                    'ru' => 'Совместимость с почвой, устойчивость к болезням и урожайность.',
                    'oz' => 'Soil compatibility, disease resistance, and yield potential.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ekishda podvoyni tuproq turi, iqlim va kasalliklarga chidamlilikka qarab tanlang. Kuchsiz o\'suvchi podvoylar (M9, M26) zich ekishga mos keladi va hosil tez beradi. O\'rtacha va kuchli podvoylar (MM106, MM111) chuqur ildiz tizimi bilan qurg\'oqchilikka chidamlidir.

Podvoyning pashsha va ildiz kasalliklariga (Phytophthora, nematodalar) chidamliligi muhim. Mahalliy sharoitlarda sinovdan o\'tgan podvoylarni tanlang. Payvand muvofiqligini tekshiring va payvand joyini tuproq sathidan 5-10 sm yuqorida saqlang. Podvoy hosildorlikni 30-50% ga ta\'sir qiladi.
UZ,
                    'ru' => <<<'RU'
При посадке :crop_ru выбирайте подвой по типу почвы, климату и устойчивости к болезням. Слаборослые подвои (M9, M26) подходят для плотной посадки и быстро дают урожай. Среднерослые и сильнорослые подвои (MM106, MM111) с глубокой корневой системой устойчивы к засухе.

Важна устойчивость подвоя к тле и корневым болезням (Phytophthora, нематоды). Выбирайте подвои, испытанные в местных условиях. Проверьте совместимость прививки и держите место прививки на 5-10 см выше уровня почвы. Подвой влияет на урожайность на 30-50%.
RU,
                    'oz' => <<<'EN'
When planting :crop_en, select rootstock based on soil type, climate, and disease resistance. Dwarfing rootstocks (M9, M26) suit high-density planting and yield early. Semi-vigorous and vigorous rootstocks (MM106, MM111) with deep root systems resist drought.

Rootstock resistance to aphids and root diseases (Phytophthora, nematodes) is important. Choose rootstocks tested in local conditions. Verify graft compatibility and keep graft union 5-10 cm above soil level. Rootstock affects yield by 30-50%.
EN,
                ],
            ],
            [
                'key' => 'intercropping',
                'tags' => ['crop-planning', 'diversification'],
                'icon' => 'intercrop',
                'title' => [
                    'uz' => ':crop_uz bilan aralash ekish tizimi',
                    'ru' => 'Система совмещённых посевов с :crop_ru',
                    'oz' => 'Intercropping System with :crop_en',
                ],
                'preview' => [
                    'uz' => 'Yer samaradorligini oshirish, xavflarni kamaytirish va ozuqa aylanishi.',
                    'ru' => 'Повышение эффективности земли, снижение рисков и круговорот питательных веществ.',
                    'oz' => 'Increasing land efficiency, reducing risks, and nutrient cycling.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz bilan dukkaklilar (no\'xat, loviya) aralash ekish azot fiksatsiyasini oshiradi va tuproq sog\'ligini yaxshilaydi. Ikkala ekinn chidamli rivojlanishi uchun qator masofasini to\'g\'ri rejalashtiring. Baland o\'sadigan va past o\'sadigan ekinlarni birgalikda ekib, yorug\'likdan samarali foydalaning.

Aralash ekish kasallik va zararkunanda bosimini kamaytiradi, chunki monokulyura bo\'lmaydi. Turli ildiz chuqurligidagi ekinlar tuproqning turli qatlamlaridan ozuqa oladi. Hosildorlik LER (Land Equivalent Ratio) 1.2-1.5 ga yetishi mumkin, ya\'ni bir xil yerdan 20-50% ko\'proq hosil. Aralash ekish xavflarni diversifikatsiya qiladi.
UZ,
                    'ru' => <<<'RU'
Совмещённые посевы :crop_ru с бобовыми (горох, фасоль) повышают фиксацию азота и улучшают здоровье почвы. Правильно планируйте междурядья для гармоничного развития обеих культур. Высаживайте высокорослые и низкорослые культуры вместе для эффективного использования света.

Совмещённые посевы снижают давление болезней и вредителей, так как нет монокультуры. Культуры с разной глубиной корней берут питательные вещества из разных горизонтов почвы. Урожайность по LER (коэффициент эквивалента земли) может достигать 1.2-1.5, то есть на 20-50% больше с той же площади. Совмещённые посевы диверсифицируют риски.
RU,
                    'oz' => <<<'EN'
Intercropping :crop_en with legumes (peas, beans) increases nitrogen fixation and improves soil health. Properly plan row spacing for harmonious development of both crops. Plant tall and short crops together for efficient light use.

Intercropping reduces disease and pest pressure as there's no monoculture. Crops with different root depths extract nutrients from different soil layers. Yield in LER (Land Equivalent Ratio) can reach 1.2-1.5, meaning 20-50% more from same area. Intercropping diversifies risks.
EN,
                ],
            ],
            [
                'key' => 'frost-protection',
                'tags' => ['climate-adaptation', 'orchard-management'],
                'icon' => 'frost',
                'title' => [
                    'uz' => ':crop_uz bog\'larida muzlashdan himoyalanish',
                    'ru' => 'Защита от заморозков в садах :crop_ru',
                    'oz' => 'Frost Protection in :crop_en Orchards',
                ],
                'preview' => [
                    'uz' => 'Faol usullar (duman, shamol), passiv usullar (soyalash, erta ekish).',
                    'ru' => 'Активные методы (дымление, ветер), пассивные методы (полив, ранняя посадка).',
                    'oz' => 'Active methods (smudging, wind), passive methods (irrigation, early planting).',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz bahorda kutilgan sovuq zarbalaridan oldin harorat prognozlarini kuzatib, himoya choralarini boshlang. Faol usullar: shamol mashinalarini ishga tushirib, havoyi aralashtirasiz va sovuq havoni tarqatasiz. Duman yoqish yoki somon yoqish haroratni 2-3°C oshiradi.

Passiv usullar: sovuqdan oldin dalani sug\'orib, tuproq issiqligini saqlang. Erta gullashni oldini olish uchun bahorda gul ochilishini kechiktiruvchi preparatlar qo\'llang. Soyalash to\'rlari bahorgi quyosh nurini kamaytiradi va gullashni nazorat qiladi. Muzlash zararini biokimyoviy preparatlar bilan kamaytirish mumkin.
UZ,
                    'ru' => <<<'RU'
Весной перед ожидаемыми заморозками для :crop_ru отслеживайте температурные прогнозы и начинайте защитные меры. Активные методы: включайте ветродуи, перемешивая воздух и рассеивая холод. Дымление или сжигание соломы повышает температуру на 2-3°C.

Пассивные методы: перед холодами полейте поле, сохраняя тепло почвы. Для предотвращения раннего цветения применяйте препараты, задерживающие распускание почек весной. Затеняющие сетки снижают весеннее солнце и контролируют цветение. Ущерб от заморозков можно снизить биохимическими препаратами.
RU,
                    'oz' => <<<'EN'
In spring before expected frosts for :crop_en, monitor temperature forecasts and start protective measures. Active methods: run wind machines, mixing air and dispersing cold. Smudging or burning straw raises temperature by 2-3°C.

Passive methods: irrigate field before cold, preserving soil heat. To prevent early blooming, apply preparations delaying bud break in spring. Shading nets reduce spring sunlight and control flowering. Frost damage can be reduced with biochemical preparations.
EN,
                ],
            ],
            [
                'key' => 'value-addition',
                'tags' => ['processing', 'marketing'],
                'icon' => 'processing',
                'title' => [
                    'uz' => ':crop_uz ni qayta ishlash va qiymat qo\'shish',
                    'ru' => 'Переработка и добавление ценности :crop_ru',
                    'oz' => 'Processing and Value Addition for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Quritish, konservalash, qadoqlash va brendlashtirish.',
                    'ru' => 'Сушка, консервирование, упаковка и брендирование.',
                    'oz' => 'Drying, canning, packaging, and branding.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ni qayta ishlash orqali qiymatini 2-3 barobarga oshiring. Quritish (quyosh yoki elektr quritgichlar) mahsulot umrini 6-12 oygacha uzaytiradi. Konservalash, muzlatish yoki sirka qo\'shish yo\'li bilan mahsulotni saqlab qolasiz. Organik va premium brendlashtirilgan mahsulotlar narxini 50-100% oshiradi.

Qadoqlashni sifatli va jozibali qilib, mahsulot ma\'lumotlari va barkodlarni qo\'shing. Mahalliy sertifikatsiyadan o\'ting (Halol, Organik) va eksport bozorlari uchun xalqaro standartlarga mos keling. Qayta ishlash kooperativ orqali birgalikda amalga oshirilsa, xarajatlar kamayd va bozor imkoniyatlari kengayadi.
UZ,
                    'ru' => <<<'RU'
Переработка :crop_ru повышает стоимость в 2-3 раза. Сушка (солнечная или электрическая) продлевает срок хранения до 6-12 месяцев. Консервирование, заморозка или маринование сохраняют продукт. Органические и премиальные брендированные продукты повышают цену на 50-100%.

Делайте упаковку качественной и привлекательной, добавляя информацию о продукте и штрих-коды. Пройдите местную сертификацию (Халяль, Органик) и соответствуйте международным стандартам для экспортных рынков. Если переработка проводится коллективно через кооператив, затраты снижаются, а рыночные возможности расширяются.
RU,
                    'oz' => <<<'EN'
Processing :crop_en increases value 2-3 times. Drying (solar or electric) extends shelf life to 6-12 months. Canning, freezing, or pickling preserves product. Organic and premium branded products increase price by 50-100%.

Make packaging quality and attractive, adding product information and barcodes. Obtain local certification (Halal, Organic) and meet international standards for export markets. If processing is done collectively through cooperative, costs decrease and market opportunities expand.
EN,
                ],
            ],
            [
                'key' => 'soil-testing',
                'tags' => ['soil-management', 'diagnostics'],
                'icon' => 'soil-test',
                'title' => [
                    'uz' => ':crop_uz ekishdan oldin tuproq tahlili',
                    'ru' => 'Почвенный анализ перед посадкой :crop_ru',
                    'oz' => 'Soil Testing Before Planting :crop_en',
                ],
                'preview' => [
                    'uz' => 'Namunalar olish, laboratoriya testlari va o\'g\'itlash rejasi.',
                    'ru' => 'Отбор проб, лабораторные тесты и план удобрения.',
                    'oz' => 'Sample collection, laboratory tests, and fertilization plan.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ekishdan 2-3 oy oldin tuproq tahlilini o\'tkazing. Har 2-3 gektarga bitta nuqtadan 15-20 sm chuqurlikda namuna oling va aralash namuna tayyorlang. Laboratoriyaga yuborib, pH, organik modda, NPK, mikroelementlar va EC darajasini aniqlang.

Tahlil natijalariga qarab o\'g\'itlash rejasini tuzing. pH 6.0 dan past bo\'lsa, ohak qo\'shing; 7.5 dan yuqori bo\'lsa, oltingugurt yoki organik kislota bering. Fosfor va kaliy etishmovchiligini bazal kiritish bilan to\'ldiring. Mikroelementlarni (sink, bor, temir) kam bo\'lsa, bargdan yoki tuproqqa qo\'llang. Tuproq tahlilini har 2-3 yilda qaytaring.
UZ,
                    'ru' => <<<'RU'
Проведите почвенный анализ за 2-3 месяца до посадки :crop_ru. Отбирайте пробы с глубины 15-20 см, по одной точке на 2-3 гектара, и готовьте смешанную пробу. Отправьте в лабораторию для определения pH, органического вещества, NPK, микроэлементов и EC.

По результатам анализа составьте план удобрения. Если pH ниже 6.0, добавьте известь; если выше 7.5, внесите серу или органические кислоты. Недостаток фосфора и калия компенсируйте базальным внесением. При нехватке микроэлементов (цинк, бор, железо) применяйте листовые или почвенные подкормки. Повторяйте почвенный анализ каждые 2-3 года.
RU,
                    'oz' => <<<'EN'
Conduct soil analysis 2-3 months before planting :crop_en. Collect samples from 15-20 cm depth, one point per 2-3 hectares, and prepare composite sample. Send to laboratory to determine pH, organic matter, NPK, micronutrients, and EC.

Based on analysis results, prepare fertilization plan. If pH below 6.0, add lime; if above 7.5, apply sulfur or organic acids. Compensate phosphorus and potassium deficiency with basal application. If micronutrients (zinc, boron, iron) are low, apply foliar or soil treatments. Repeat soil analysis every 2-3 years.
EN,
                ],
            ],
            [
                'key' => 'variety-selection',
                'tags' => ['crop-planning', 'seed-management'],
                'icon' => 'variety',
                'title' => [
                    'uz' => ':crop_uz navlarini tanlash va ularning xususiyatlari',
                    'ru' => 'Выбор сортов :crop_ru и их характеристики',
                    'oz' => 'Variety Selection and Characteristics for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Mahalliy sharoit, kasallik chidamliligi va bozor talabi bo\'yicha nav tanlash.',
                    'ru' => 'Выбор сорта по местным условиям, устойчивости к болезням и рыночному спросу.',
                    'oz' => 'Selecting varieties by local conditions, disease resistance, and market demand.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz navlarini tanlashda mintaqaviy iqlim, tuproq va suv sharoitlariga moslashganligini tekshiring. Erta, o\'rta va kech pishuvchi navlarni aralashtirib, bozor mavsumini uzaytiring. Kasalliklarga chidamli navlar (mildew, bakterioz) kimyoviy ishlovlarni kamaytiradi va xarajatlarni pasaytiradi.

Mahalliy bozor talab qilgan sifat ko\'rsatkichlariga e\'tibor bering: shakl, rang, ta\'m, saqlash qobiliyati. Ilmiy-tadqiqot institutlaridan tavsiya etilgan va mahalliy sinovdan o\'tgan navlarni afzal ko\'ring. Turli navlarni sinab ko\'ring va har yili hosildorlik va sifat ma\'lumotlarini yozib boring. Urug\' yangilanish tezligini nazorat qilib, degeneratsiyadan saqlaning.
UZ,
                    'ru' => <<<'RU'
При выборе сортов :crop_ru проверяйте их адаптацию к региональному климату, почвам и водным условиям. Смешивайте раннеспелые, среднеспелые и позднеспелые сорта, продлевая рыночный сезон. Устойчивые к болезням сорта (мучнистая роса, бактериоз) снижают химические обработки и затраты.

Обращайте внимание на качественные показатели, востребованные местным рынком: форму, цвет, вкус, лёжкость. Предпочитайте сорта, рекомендованные научными институтами и прошедшие местные испытания. Испытывайте разные сорта и ежегодно записывайте данные урожайности и качества. Контролируйте скорость обновления семян, избегая дегенерации.
RU,
                    'oz' => <<<'EN'
When selecting :crop_en varieties, verify their adaptation to regional climate, soils, and water conditions. Mix early, mid, and late-maturing varieties to extend market season. Disease-resistant varieties (powdery mildew, bacteriosis) reduce chemical treatments and costs.

Pay attention to quality indicators demanded by local market: shape, color, taste, storage ability. Prefer varieties recommended by research institutes and tested locally. Trial different varieties and record yield and quality data annually. Control seed renewal rate, avoiding degeneration.
EN,
                ],
            ],
            [
                'key' => 'plant-spacing',
                'tags' => ['crop-planning', 'yield-optimization'],
                'icon' => 'spacing',
                'title' => [
                    'uz' => ':crop_uz ekish zichligi va qatorlar oralig\'i',
                    'ru' => 'Плотность посадки и междурядья для :crop_ru',
                    'oz' => 'Planting Density and Row Spacing for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Optimal o\'simlik zichligi, yorug\'lik va ozuqa raqobati.',
                    'ru' => 'Оптимальная густота растений, конкуренция за свет и питание.',
                    'oz' => 'Optimal plant density, light and nutrient competition.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun ekish zichligini tuproq unumdorligi va sug\'orish imkoniyatlariga qarab sozlang. Zich ekish (o\'simlik orasida 15-20 sm) begona o\'tlarni bostiradi lekin kasallik xavfini oshiradi. Keng ekish (30-40 sm) shamollatishni yaxshilaydi va ishlov berishni osonlashtiradi.

Qatorlar oralig\'ini mexanizatsiyaga moslang: 60-70 sm traktor g\'ildiraklari uchun qulay. Ikki qatorli ekish (juft qatorlar) yerdan samarali foydalanadi. Zich yetishtirilgan navlar (salat, ismaloq) 10-15 sm oraliqda ekiladi. Qatorlarni shimoldan janubga yo\'naltiring, yorug\'likdan maksimal foydalanish uchun.
UZ,
                    'ru' => <<<'RU'
Для :crop_ru настраивайте густоту посадки по плодородию почвы и возможностям орошения. Плотная посадка (15-20 см между растениями) подавляет сорняки, но повышает риск болезней. Широкая посадка (30-40 см) улучшает вентиляцию и облегчает обработку.

Междурядья подстраивайте под механизацию: 60-70 см удобны для колёс трактора. Двухстрочная посадка (парные ряды) эффективно использует землю. Загущенно выращиваемые культуры (салат, шпинат) высаживают с интервалом 10-15 см. Ориентируйте ряды с севера на юг для максимального использования света.
RU,
                    'oz' => <<<'EN'
For :crop_en, adjust planting density by soil fertility and irrigation capacity. Dense planting (15-20 cm between plants) suppresses weeds but increases disease risk. Wide planting (30-40 cm) improves ventilation and facilitates cultivation.

Adapt row spacing to mechanization: 60-70 cm suits tractor wheels. Twin-row planting (paired rows) uses land efficiently. Densely grown crops (lettuce, spinach) are planted at 10-15 cm intervals. Orient rows north-south for maximum light utilization.
EN,
                ],
            ],
            [
                'key' => 'grafting-techniques',
                'tags' => ['propagation', 'orchard-management'],
                'icon' => 'grafting',
                'title' => [
                    'uz' => ':crop_uz payvand qilish texnikasi va vaqti',
                    'ru' => 'Техника и сроки прививки :crop_ru',
                    'oz' => 'Grafting Techniques and Timing for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Payvand turlari, muvofiqlik va yopishish foizi.',
                    'ru' => 'Виды прививок, совместимость и приживаемость.',
                    'oz' => 'Graft types, compatibility, and success rate.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun payvand qilishning eng yaxshi vaqti — bahorda shiraning harakati boshlanganida (mart-aprel). Ko\'zcha payvandi yozda (iyul-avgust) amalga oshiriladi. Payvand uskunalarini spirt yoki olov bilan sterilizatsiya qiling. Kesish o\'tkirligini ta\'minlab, to\'qimalar shikastlanishini kamaytiring.

Payvand joylarini payvand lentasi yoki polietilen bilan mahkam o\'rang va namligi saqlansin. Payvand ostidan o\'sadigan kurtaklar va novdalarni darhol olib tashlang. Payvandning yopishishi 2-3 haftada ko\'rinadi. Birinchi yil hosil olishga ruxsat bermang, o\'simlik kuchaytirishga e\'tibor bering. Payvand muvofiqligini tajribada sinab ko\'ring.
UZ,
                    'ru' => <<<'RU'
Для :crop_ru лучшее время прививки — весной при начале сокодвижения (март-апрель). Окулировка проводится летом (июль-август). Стерилизуйте инструменты спиртом или огнём. Обеспечьте остроту среза, снижая повреждение тканей.

Плотно обматывайте места прививки прививочной лентой или полиэтиленом для сохранения влаги. Немедленно удаляйте побеги и почки, растущие ниже прививки. Приживаемость прививки видна через 2-3 недели. В первый год не допускайте плодоношения, сосредоточьтесь на укреплении растения. Проверяйте совместимость прививки экспериментально.
RU,
                    'oz' => <<<'EN'
For :crop_en, best grafting time is spring when sap flow begins (March-April). Budding is done in summer (July-August). Sterilize tools with alcohol or flame. Ensure sharp cuts, reducing tissue damage.

Wrap graft sites tightly with grafting tape or polyethylene to retain moisture. Immediately remove shoots and buds growing below graft. Graft success is visible in 2-3 weeks. In first year, prevent fruiting, focus on plant strengthening. Test graft compatibility experimentally.
EN,
                ],
            ],
            [
                'key' => 'pruning-training',
                'tags' => ['orchard-management', 'crop-management'],
                'icon' => 'pruning',
                'title' => [
                    'uz' => ':crop_uz kesish va shakllantirish texnikasi',
                    'ru' => 'Техника обрезки и формирования :crop_ru',
                    'oz' => 'Pruning and Training Techniques for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Qish va yoz kesimi, shakl tanlash va hosildorlikni oshirish.',
                    'ru' => 'Зимняя и летняя обрезка, выбор формы и повышение урожайности.',
                    'oz' => 'Winter and summer pruning, shape selection, and yield increase.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz qish kesimi (yanvar-fevral) dam olish davrida bajariladi va daraxt strukturasini shakllantiradi. Yoz kesimi (iyun-iyul) novdalarni qisqartirib, yorug\'likni va havo almashinuvini yaxshilaydi. O\'tkir va toza asboblardan foydalaning, kasallik tarqalishini oldini oling.

Kesimni 45 daraja burchak ostida, kurtakdan 5 mm yuqorida amalga oshiring. Qalin novdalarni kesishda kesim joyiga bog\' moyi suring. Ichkariga o\'sgan, qarama-qarshi yoki kasallangan novdalarni olib tashlang. Mevali novdalarni qoldiring va vegetativ novdalarni kamaytirib hosildorlikni oshiring. Yalanisht shakl, piyola shakl yoki paletka tanlab, yorug\'lik penetratsiyasini ta\'minlang.
UZ,
                    'ru' => <<<'RU'
Для :crop_ru зимняя обрезка (январь-февраль) проводится в период покоя и формирует структуру дерева. Летняя обрезка (июнь-июль) укорачивает побеги, улучшая освещённость и воздухообмен. Используйте острые и чистые инструменты, предотвращая распространение болезней.

Делайте срез под углом 45 градусов, на 5 мм выше почки. При обрезке толстых ветвей замазывайте срез садовым варом. Удаляйте ветви, растущие внутрь, перекрещивающиеся или больные. Сохраняйте плодовые ветви и сокращайте вегетативные для повышения урожайности. Выбирайте форму: разреженно-ярусная, чаша или пальметта, обеспечивая проникновение света.
RU,
                    'oz' => <<<'EN'
For :crop_en, winter pruning (January-February) is done during dormancy and forms tree structure. Summer pruning (June-July) shortens shoots, improving light and air circulation. Use sharp and clean tools, preventing disease spread.

Make cuts at 45-degree angle, 5 mm above bud. When pruning thick branches, apply wound sealant. Remove inward-growing, crossing, or diseased branches. Keep fruiting branches and reduce vegetative ones to increase yields. Select form: open-center, vase, or espalier, ensuring light penetration.
EN,
                ],
            ],
            [
                'key' => 'flowering-management',
                'tags' => ['crop-management', 'pollination'],
                'icon' => 'flower',
                'title' => [
                    'uz' => ':crop_uz gullash va changlanishni boshqarish',
                    'ru' => 'Управление цветением и опылением :crop_ru',
                    'oz' => 'Managing Flowering and Pollination for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Gullarni siyraklash, changlatuvchilar jalb qilish va meva bog\'lanishi.',
                    'ru' => 'Прореживание цветков, привлечение опылителей и завязывание плодов.',
                    'oz' => 'Flower thinning, attracting pollinators, and fruit setting.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz gullash paytida changlatuvchilar faoliyatini ta\'minlash uchun gul aralashmalari eking va kimyoviy ishlovlarni to\'xtating. O\'zini-o\'zi changlatmaydigan navlar uchun changlatuvchi navlarni 15-20% nisbatda joylashtiring. Asalari uyalarini gektariga 2-3 ta joylashtiring.

Haddan tashqari gullashda gullarni siyraklashtiring: har gul to\'pida 1-2 ta gul qoldiring. Bu meva hajmi va sifatini oshiradi. Boron bilan bargdan purkash (0.1%) changlanishni yaxshilaydi. Meva bog\'langandan 2-3 hafta o\'tgach, tabiiy tushishdan keyin qo\'shimcha siyraklash bajaring. Gullashni sovuqdan himoyalang.
UZ,
                    'ru' => <<<'RU'
Во время цветения :crop_ru для обеспечения активности опылителей высаживайте цветочные смеси и прекращайте химические обработки. Для самонесовместимых сортов размещайте опылители в соотношении 15-20%. Размещайте ульи из расчёта 2-3 на гектар.

При избыточном цветении прореживайте цветки: оставляйте 1-2 цветка на соцветие. Это увеличивает размер и качество плодов. Листовое опрыскивание бором (0.1%) улучшает опыление. Через 2-3 недели после завязывания, после естественного опадания, проведите дополнительное прореживание. Защищайте цветение от холодов.
RU,
                    'oz' => <<<'EN'
During :crop_en flowering, plant flower mixes and stop chemical treatments to ensure pollinator activity. For self-incompatible varieties, place pollinizers at 15-20% ratio. Place hives at 2-3 per hectare.

With excessive flowering, thin flowers: leave 1-2 flowers per cluster. This increases fruit size and quality. Foliar boron spray (0.1%) improves pollination. 2-3 weeks after setting, after natural drop, conduct additional thinning. Protect flowering from cold.
EN,
                ],
            ],
            [
                'key' => 'nutrient-deficiency',
                'tags' => ['nutrient-management', 'diagnostics'],
                'icon' => 'deficiency',
                'title' => [
                    'uz' => ':crop_uz ozuqa tanqisligi belgilari va tuzatish',
                    'ru' => 'Признаки дефицита питания :crop_ru и коррекция',
                    'oz' => 'Nutrient Deficiency Symptoms and Correction for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Vizual diagnostika, elementlar etishmovchiligi va to\'ldirish usullari.',
                    'ru' => 'Визуальная диагностика, недостаток элементов и способы восполнения.',
                    'oz' => 'Visual diagnosis, element deficiency, and replenishment methods.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz da azot tanqisligi — pastki barglar sarg\'ayadi va o\'sish sekinlashadi. Fosfor kamchiligi — barglar qoramtir yashil, ildiz rivojlanishi sustlashadi. Kaliy etishmasa barg chetlari kuya boshlaydi. Mikroelementlar (sink, temir, bor) kamchiligi — yangi barglar xlorozli bo\'ladi.

Azot kamchiligini karbamid (2%) dan bargdan purkash yoki tuproqga ammiak kiritish orqali tuzating. Fosfor uchun superfosfat suvda eritib bering. Kaliy sulfatini bargdan yoki tuproqqa qo\'llang. Sink va temir kelatlangan shakllarda berganda tezroq so\'riladi. Tahlil natijalari asosida to\'g\'ri elementni va miqdorni tanlang.
UZ,
                    'ru' => <<<'RU'
У :crop_ru дефицит азота — нижние листья желтеют и рост замедляется. Недостаток фосфора — листья тёмно-зелёные, развитие корней замедлено. При нехватке калия края листьев начинают гореть. Дефицит микроэлементов (цинк, железо, бор) — новые листья хлоротичные.

Корректируйте нехватку азота листовым опрыскиванием карбамида (2%) или внесением аммиака в почву. Для фосфора растворите суперфосфат в воде. Сульфат калия вносите листовыми или почвенными подкормками. Цинк и железо в хелатной форме усваиваются быстрее. На основе анализа выбирайте правильный элемент и дозу.
RU,
                    'oz' => <<<'EN'
In :crop_en, nitrogen deficiency — lower leaves yellow and growth slows. Phosphorus shortage — leaves dark green, root development slowed. With potassium lack, leaf edges start burning. Micronutrient deficiency (zinc, iron, boron) — new leaves chlorotic.

Correct nitrogen deficiency with foliar urea spray (2%) or soil ammonia application. For phosphorus, dissolve superphosphate in water. Apply potassium sulfate foliarly or to soil. Zinc and iron in chelated form are absorbed faster. Based on analysis, choose correct element and dosage.
EN,
                ],
            ],
            [
                'key' => 'disease-identification',
                'tags' => ['disease-management', 'diagnostics'],
                'icon' => 'disease',
                'title' => [
                    'uz' => ':crop_uz kasalliklarini aniqlash va davolash',
                    'ru' => 'Выявление и лечение болезней :crop_ru',
                    'oz' => 'Disease Identification and Treatment for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Qo\'ziqorin, bakterial va virus kasalliklari, belgilari va nazorat.',
                    'ru' => 'Грибные, бактериальные и вирусные болезни, симптомы и контроль.',
                    'oz' => 'Fungal, bacterial, and viral diseases, symptoms, and control.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz qo\'ziqorin kasalliklari (mildew, septoria, chirish) namlik va zich ekishda tarqaladi. Belgilari: barglarda dog\'lar, chang qatlami, chirish. Mis preparatlari (Bordeaux aralashmasi) yoki sistemik fungitsidlar bilan 7-10 kunlik intervalda ishlov bering. Kasallangan qismlarni kesib olib, daladan tashqariga chiqaring.

Bakterial kasalliklar (bakterioz, kuyish) tez tarqaladi va davolanishi qiyin. Antibakterial preparatlar va mis birikmalarini qo\'llang. Virus kasalliklari (mozaika, burishish) shifobulmaydi — kasallangan o\'simliklarni yo\'q qilish kerak. Sog\'lom urug\' va zararsizlangan asboblardan foydalaning. Profilaktika eng yaxshi himoya: nav aylanishi va shamollatish.
UZ,
                    'ru' => <<<'RU'
Грибные болезни :crop_ru (мучнистая роса, септориоз, гниль) распространяются при влажности и загущении. Симптомы: пятна на листьях, мучнистый налёт, гниение. Обрабатывайте медными препаратами (бордосская смесь) или системными фунгицидами с интервалом 7-10 дней. Удаляйте поражённые части и выносите с поля.

Бактериальные болезни (бактериоз, ожог) быстро распространяются и трудно лечатся. Применяйте антибактериальные препараты и соединения меди. Вирусные болезни (мозаика, курчавость) неизлечимы — уничтожайте больные растения. Используйте здоровые семена и обеззараженные инструменты. Профилактика — лучшая защита: севооборот и вентиляция.
RU,
                    'oz' => <<<'EN'
Fungal diseases of :crop_en (powdery mildew, septoria, rot) spread in humidity and dense planting. Symptoms: leaf spots, powdery coating, rotting. Treat with copper preparations (Bordeaux mixture) or systemic fungicides at 7-10 day intervals. Remove affected parts and take off field.

Bacterial diseases (bacteriosis, blight) spread quickly and are hard to treat. Apply antibacterial preparations and copper compounds. Viral diseases (mosaic, curl) are incurable — destroy sick plants. Use healthy seeds and sterilized tools. Prevention is best defense: crop rotation and ventilation.
EN,
                ],
            ],
            [
                'key' => 'pest-lifecycle',
                'tags' => ['pest-management', 'integrated-pest-management'],
                'icon' => 'pest-cycle',
                'title' => [
                    'uz' => ':crop_uz zararkunandalarining hayot sikli va nazorat',
                    'ru' => 'Жизненный цикл вредителей :crop_ru и контроль',
                    'oz' => 'Pest Life Cycle and Control for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Asosiy zararkunandalar, rivojlanish bosqichlari va maqsadli nazorat.',
                    'ru' => 'Основные вредители, стадии развития и целевой контроль.',
                    'oz' => 'Main pests, development stages, and targeted control.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz asosiy zararkunandalari — shiraslar, kapalaklar kurtaklari, qandalalari. Hayot siklini o\'rganib, eng zaif bosqichda nazorat qiling. Shiraslar 7-10 kunda avlod beradi, populyatsiya tez o\'sadi. Kurtaklar 3-4 bosqichda rivojlanadi, erta bosqichda biopreparatlar samarali.

Feromonal tuzoqlar orqali kattalarni monitoring qiling va ekonomik zarar chegarasi (EIL) ga yetganda ishlov boshlang. Tuxum va lichinka bosqichlarida biologik nazorat (Trichogramma, BTI) afzalroq. Kimyoviy insektitsidlarni rotatsiya qiling va rezistentlikni oldini oling. Qishlovchi bosqichlarni yo\'q qilish uchun kuzda chuqur haydash va qoldiqlarni tozalash amalga oshiring.
UZ,
                    'ru' => <<<'RU'
Основные вредители :crop_ru — тли, гусеницы бабочек, жуки. Изучив жизненный цикл, контролируйте на наиболее уязвимой стадии. Тли дают поколение за 7-10 дней, популяция быстро растёт. Гусеницы развиваются в 3-4 стадии, на ранней стадии биопрепараты эффективны.

Мониторьте взрослых особей феромонными ловушками и начинайте обработку при достижении экономического порога вредоносности (ЭПВ). На стадиях яйца и личинки предпочтителен биоконтроль (Trichogramma, BTI). Ротируйте химические инсектициды, предотвращая резистентность. Для уничтожения зимующих стадий осенью проводите глубокую вспашку и очистку остатков.
RU,
                    'oz' => <<<'EN'
Main pests of :crop_en — aphids, caterpillars, beetles. By studying life cycle, control at most vulnerable stage. Aphids give generation in 7-10 days, population grows rapidly. Caterpillars develop in 3-4 stages, biopreparations effective at early stage.

Monitor adults with pheromone traps and start treatment when economic injury level (EIL) is reached. At egg and larva stages, biocontrol (Trichogramma, BTI) is preferable. Rotate chemical insecticides, preventing resistance. To destroy overwintering stages, conduct deep plowing and residue cleanup in fall.
EN,
                ],
            ],
            [
                'key' => 'weed-management',
                'tags' => ['weed-control', 'crop-management'],
                'icon' => 'weed',
                'title' => [
                    'uz' => ':crop_uz dalalarida begona o\'tlar bilan kurash',
                    'ru' => 'Борьба с сорняками на полях :crop_ru',
                    'oz' => 'Weed Control in :crop_en Fields',
                ],
                'preview' => [
                    'uz' => 'Mexanik, kimyoviy va mulcha orqali begona o\'tlarni boshqarish.',
                    'ru' => 'Механический, химический и мульчирование для управления сорняками.',
                    'oz' => 'Mechanical, chemical, and mulching for weed management.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz dalalarida begona o\'tlar ozuqa, suv va yorug\'lik uchun raqobatlashadi. Mexanik nazorat — o\'roq, qo\'l bilan yulish va kultivatsiya. Ekishdan oldin yolg\'on ekish usulini qo\'llab, birinchi avlod begona o\'tlarni yo\'q qiling. Mulchalash organik yoki polietilen bilan begona o\'tlar unishini 90% gacha kamaytiradi.

Gerbitsidlarni tanlab qo\'llash: selektiv preparatlar (2,4-D, MCPA) faqat keng barglilarni yo\'q qiladi. Ekishdan oldin tuproqqa aralashtiriluvchi gerbitsidlar (trifluralin) uzoq ta\'sir ko\'rsatadi. Ekishdan keyin poyaga tegmaydigan qilib purkang. Ko\'p yillik begona o\'tlar (shuvoq, qamish) uchun sistemik gerbitsidlar (glyphosate) kerak. Kimyoviy nazoratni mexanik usullar bilan birgalikda qo\'llang.
UZ,
                    'ru' => <<<'RU'
На полях :crop_ru сорняки конкурируют за питание, воду и свет. Механический контроль — прополка, ручная выборка и культивация. До посадки применяйте метод ложного посева, уничтожая первое поколение сорняков. Мульчирование органикой или полиэтиленом снижает всходы сорняков до 90%.

Избирательно применяйте гербициды: селективные препараты (2,4-Д, МЦПА) уничтожают только широколистные. Почвенные гербициды (трифлуралин) действуют длительно. После посадки опрыскивайте, не попадая на стебли. Для многолетних сорняков (пырей, осот) нужны системные гербициды (глифосат). Комбинируйте химический контроль с механическими методами.
RU,
                    'oz' => <<<'EN'
In :crop_en fields, weeds compete for nutrients, water, and light. Mechanical control — hoeing, hand pulling, and cultivation. Before planting, use false seedbed method, destroying first weed generation. Mulching with organic or polyethylene reduces weed emergence by 90%.

Selectively apply herbicides: selective preparations (2,4-D, MCPA) destroy only broadleaves. Soil herbicides (trifluralin) act long-term. After planting, spray without touching stems. For perennial weeds (couch grass, thistle), systemic herbicides (glyphosate) are needed. Combine chemical control with mechanical methods.
EN,
                ],
            ],
            [
                'key' => 'crop-rotation-planning',
                'tags' => ['crop-rotation', 'soil-management'],
                'icon' => 'rotation-plan',
                'title' => [
                    'uz' => ':crop_uz uchun almashlab ekish rejasi tuzish',
                    'ru' => 'Планирование севооборота для :crop_ru',
                    'oz' => 'Crop Rotation Planning for :crop_en',
                ],
                'preview' => [
                    'uz' => '3-5 yillik almashlab ekish, kasallik tsikli va tuproq tiklanishi.',
                    'ru' => '3-5 летний севооборот, цикл болезней и восстановление почвы.',
                    'oz' => '3-5 year rotation, disease cycle, and soil recovery.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ni bir joyda ketma-ket ekmang — kasalliklar va zararkunandalar to\'planadi. 3-5 yillik almashlab ekish rejasini tuzing: :crop_uz → dukkaklilar → don ekinlari → qator ekinlari. Bir oiladagi o\'simliklarni ketma-ket ekmang (masalan, pomidor va kartoshka).

Dukkakli navlar (beda, no\'xat) tuproqqa 40-60 kg/ga azot qo\'shadi. Chuqur ildizli ekinlar (arpabodyon, kungaboqar) tuproqning pastki qatlamlaridan ozuqa oladi va tuzilmani yaxshilaydi. Har rotatsiyadan keyin tuproq tahlilini o\'tkazing va ozuqa balansini tekshiring. Qoplovchi ekinlar bilan rotatsiya tugallab, organik modda qo\'shing.
UZ,
                    'ru' => <<<'RU'
Не высаживайте :crop_ru на одном месте подряд — накапливаются болезни и вредители. Составьте план севооборота на 3-5 лет: :crop_ru → бобовые → зерновые → пропашные. Не чередуйте культуры одного семейства (например, томаты и картофель).

Бобовые культуры (вика, горох) добавляют 40-60 кг/га азота в почву. Глубококорневые культуры (люцерна, подсолнух) извлекают питание из нижних горизонтов и улучшают структуру. После каждой ротации проводите почвенный анализ и проверяйте баланс питательных веществ. Завершайте ротацию покровными культурами, добавляя органическое вещество.
RU,
                    'oz' => <<<'EN'
Don't plant :crop_en in same place consecutively — diseases and pests accumulate. Create 3-5 year rotation plan: :crop_en → legumes → cereals → row crops. Don't alternate crops from same family (e.g., tomatoes and potatoes).

Legume crops (vetch, peas) add 40-60 kg/ha nitrogen to soil. Deep-rooted crops (alfalfa, sunflower) extract nutrients from lower horizons and improve structure. After each rotation, conduct soil analysis and check nutrient balance. Complete rotation with cover crops, adding organic matter.
EN,
                ],
            ],
            [
                'key' => 'microclimate-management',
                'tags' => ['climate-control', 'crop-management'],
                'icon' => 'microclimate',
                'title' => [
                    'uz' => ':crop_uz uchun mikro iqlimni yaratish',
                    'ru' => 'Создание микроклимата для :crop_ru',
                    'oz' => 'Creating Microclimate for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Shamol himoyasi, soyalash va namlikni boshqarish.',
                    'ru' => 'Ветрозащита, затенение и управление влажностью.',
                    'oz' => 'Windbreaks, shading, and humidity control.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun qulay mikro iqlim yarating. Shamol himoyasi uchun dala chetlarida shamol to\'siqlar (daraxtlar, buta) o\'rnating, shamol tezligini 30-40% ga kamaytiring. To\'siqlarni asosiy shamol yo\'nalishiga perpendikulyar joylashtiring. Shamol to\'siq balandligining 10-15 baravariga teng masofada himoya ta\'siri bo\'ladi.

Issiq iqlimda soyalash to\'rlari (30-50% soyalash) o\'simliklarni kuyishdan saqlaydi va barglardan bug\'lanishni kamaytiradi. Tuproqni mulchlash ildiz zonasida haroratni 3-5°C pasaytiradi. Namlikni oshirish uchun tuman purkash tizimlarini qo\'llang. Mikro iqlim boshqaruvi hosildorlikni 15-25% ga oshirishi mumkin.
UZ,
                    'ru' => <<<'RU'
Создайте благоприятный микроклимат для :crop_ru. Для ветрозащиты установите ветрозаломы (деревья, кустарники) по краям поля, снижая скорость ветра на 30-40%. Размещайте заломы перпендикулярно господствующему направлению ветра. Защитный эффект действует на расстояние в 10-15 раз превышающее высоту залома.

В жарком климате затеняющие сетки (30-50% затенения) защищают растения от ожогов и снижают испарение с листьев. Мульчирование почвы снижает температуру в корневой зоне на 3-5°C. Для повышения влажности применяйте туманообразующие системы. Управление микроклиматом может повысить урожайность на 15-25%.
RU,
                    'oz' => <<<'EN'
Create favorable microclimate for :crop_en. For wind protection, install windbreaks (trees, shrubs) along field edges, reducing wind speed by 30-40%. Place breaks perpendicular to prevailing wind direction. Protective effect works at distance 10-15 times break height.

In hot climate, shade nets (30-50% shading) protect plants from burns and reduce leaf evaporation. Soil mulching reduces root zone temperature by 3-5°C. To increase humidity, use misting systems. Microclimate management can increase yields by 15-25%.
EN,
                ],
            ],
            [
                'key' => 'trellis-staking',
                'tags' => ['crop-management', 'support-systems'],
                'icon' => 'trellis',
                'title' => [
                    'uz' => ':crop_uz uchun tayanch tizimlar va qoziqlar',
                    'ru' => 'Шпалеры и опоры для :crop_ru',
                    'oz' => 'Trellis and Staking Systems for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Vertikal o\'stirish, yorug\'lik kirishi va hosil yig\'ishni osonlashtirish.',
                    'ru' => 'Вертикальное выращивание, доступ света и облегчение уборки.',
                    'oz' => 'Vertical growing, light access, and harvest facilitation.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz uchun tayanch tizimlarini o\'rnatish vertikal o\'stirishni ta\'minlab, yerdan 40-60% ko\'proq foydalanish imkonini beradi. Yagona qoziq tizimi (2-2.5 m balandlik) individual o\'simliklar uchun mos. Bir simli shpalera tomato, bodring uchun ishlatiladi. Ikki simli shpalera (60 va 120 sm balandlikda) ikkala tomondan tutish imkonini beradi.

Sim yoki tor (polipropilen, plastik) kuchli va bardoshli bo\'lishi kerak. Qoziqlarga o\'simliklarni yumshoq bog\'lovchilar bilan bog\'lang, poyani siqmasdan. Shpalera tizimi shamollatishni yaxshilaydi, qo\'ziqorin kasalliklarini kamaytiradi va hosil yig\'ishni osonlashtiradi. Shpalera inshootlarini har mavsumdan oldin tekshiring va zarur ta\'mirlashni bajaring.
UZ,
                    'ru' => <<<'RU'
Установка опор для :crop_ru обеспечивает вертикальное выращивание, позволяя использовать на 40-60% больше земли. Система одиночных кольев (высота 2-2.5 м) подходит для отдельных растений. Одноярусная шпалера используется для томатов, огурцов. Двухъярусная шпалера (на высоте 60 и 120 см) позволяет подвязывать с обеих сторон.

Проволока или шпагат (полипропилен, пластик) должны быть прочными и долговечными. Привязывайте растения к кольям мягкими подвязками, не сдавливая стебель. Шпалерная система улучшает вентиляцию, снижает грибные болезни и облегчает уборку. Проверяйте шпалерные конструкции перед каждым сезоном и проводите необходимый ремонт.
RU,
                    'oz' => <<<'EN'
Installing supports for :crop_en enables vertical growing, allowing 40-60% more land use. Single stake system (2-2.5 m height) suits individual plants. Single-wire trellis is used for tomatoes, cucumbers. Two-wire trellis (at 60 and 120 cm height) allows tying from both sides.

Wire or twine (polypropylene, plastic) should be strong and durable. Tie plants to stakes with soft ties, not squeezing stem. Trellis system improves ventilation, reduces fungal diseases, and facilitates harvest. Check trellis structures before each season and perform necessary repairs.
EN,
                ],
            ],
            [
                'key' => 'fruit-thinning',
                'tags' => ['crop-management', 'yield-quality'],
                'icon' => 'thinning',
                'title' => [
                    'uz' => ':crop_uz mevalarini siyraklash va sifatni oshirish',
                    'ru' => 'Прореживание плодов :crop_ru и повышение качества',
                    'oz' => 'Fruit Thinning and Quality Improvement for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Haddan tashqari yuklama, meva hajmi va alternativ hosildorlik.',
                    'ru' => 'Избыточная нагрузка, размер плодов и периодичность плодоношения.',
                    'oz' => 'Excessive load, fruit size, and alternate bearing.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz da haddan tashqari ko\'p meva bog\'lansa, sifat pasayadi va alternativ hosildorlik (bir yil ko\'p, keyingi yil oz) yuzaga keladi. Meva bog\'langandan 3-4 hafta o\'tgach siyraklash boshlang. Kichik, shikastlangan va noto\'g\'ri shakldagi mevalarni olib tashlang. Har 15-20 sm da bitta meva qoldiring, yoki bargli novda uzunligiga qarab.

Kimyoviy siyraklash (NAA, BA) gullashdan 10-12 kun o\'tgach qo\'llaniladi, lekin ehtiyotkorlik bilan dozalang. Qo\'lda siyraklash aniqroq lekin mehnat talab qiladi. Meva hajmi 30-50% gacha oshadi. Muntazam siyraklash har yili barqaror hosil olishni ta\'minlaydi va daraxt kuchidan saqlab qoladi. Siyraklangan mevalarni kompost qiling yoki chorva ozuqasi sifatida ishlating.
UZ,
                    'ru' => <<<'RU'
Если у :crop_ru завязывается слишком много плодов, качество снижается и возникает периодичность плодоношения (один год много, следующий мало). Начинайте прореживание через 3-4 недели после завязывания. Удаляйте мелкие, повреждённые и неправильной формы плоды. Оставляйте один плод на 15-20 см, или по длине облиственного побега.

Химическое прореживание (НУК, BA) применяется через 10-12 дней после цветения, но дозируйте осторожно. Ручное прореживание точнее, но трудозатратно. Размер плодов увеличивается на 30-50%. Регулярное прореживание обеспечивает стабильный урожай каждый год и сохраняет силу дерева. Прореженные плоды компостируйте или используйте как корм для скота.
RU,
                    'oz' => <<<'EN'
If :crop_en sets too many fruits, quality decreases and alternate bearing occurs (one year much, next little). Start thinning 3-4 weeks after setting. Remove small, damaged, and misshapen fruits. Leave one fruit per 15-20 cm, or per leafy shoot length.

Chemical thinning (NAA, BA) is applied 10-12 days after flowering, but dose carefully. Hand thinning is more accurate but labor-intensive. Fruit size increases by 30-50%. Regular thinning ensures stable yield annually and preserves tree strength. Compost thinned fruits or use as livestock feed.
EN,
                ],
            ],
            [
                'key' => 'transplanting',
                'tags' => ['propagation', 'crop-establishment'],
                'icon' => 'transplant',
                'title' => [
                    'uz' => ':crop_uz ko\'chatlarini ko\'chirish texnikasi',
                    'ru' => 'Техника пересадки рассады :crop_ru',
                    'oz' => 'Transplanting Technique for :crop_en Seedlings',
                ],
                'preview' => [
                    'uz' => 'Ko\'chat tayyorlash, ko\'chirish vaqti va tiklanish.',
                    'ru' => 'Подготовка рассады, сроки пересадки и приживаемость.',
                    'oz' => 'Seedling preparation, transplant timing, and establishment.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ko\'chatlarini ko\'chirishdan 7-10 kun oldin qattiqlashtirish (hardening off) jarayonini boshlang: sug\'orishni kamaytiring va tashqariga chiqaring. Ko\'chatlar 4-6 ta haqiqiy bargga ega bo\'lganda ko\'chirishga tayyor. Ko\'chirish kechqurun yoki bulutli kunda amalga oshiring, stressni kamaytirish uchun.

Ko\'chat ildizlarini shikastlamasdan tuproq bilan birga oling. Teshik chuqurligini ko\'chat idishi chuqurligidan 2-3 sm ortiq qiling. Ko\'chatni joylashtirganingizdan keyin tuproqni yaxshilab bosing va darhol sug\'oring. Birinchi 3-5 kun soyalash qo\'llang va kuniga 2 marta namlashtiring. Tiklanish 7-10 kun davom etadi. Ko\'chatlar ildiz urguncha mineral o\'g\'it bermang.
UZ,
                    'ru' => <<<'RU'
За 7-10 дней до пересадки рассады :crop_ru начните процесс закаливания: снижайте полив и выносите на улицу. Рассада готова к пересадке при наличии 4-6 настоящих листьев. Пересаживайте вечером или в пасмурный день, снижая стресс.

Извлекайте рассаду с комом земли, не повреждая корни. Глубину лунки делайте на 2-3 см больше глубины стаканчика. После размещения рассады хорошо уплотните почву и сразу полейте. В первые 3-5 дней применяйте затенение и увлажняйте дважды в день. Приживаемость длится 7-10 дней. Не вносите минеральные удобрения до укоренения рассады.
RU,
                    'oz' => <<<'EN'
7-10 days before transplanting :crop_en seedlings, start hardening process: reduce watering and take outside. Seedlings are ready for transplant with 4-6 true leaves. Transplant in evening or cloudy day, reducing stress.

Remove seedlings with soil ball, not damaging roots. Make hole depth 2-3 cm deeper than pot depth. After placing seedling, compact soil well and water immediately. In first 3-5 days, apply shading and moisten twice daily. Establishment lasts 7-10 days. Don't apply mineral fertilizers until seedlings root.
EN,
                ],
            ],
            [
                'key' => 'cold-storage',
                'tags' => ['postharvest', 'storage-management'],
                'icon' => 'cold-storage',
                'title' => [
                    'uz' => ':crop_uz hosilini sovuq omborida saqlash',
                    'ru' => 'Холодное хранение урожая :crop_ru',
                    'oz' => 'Cold Storage of :crop_en Harvest',
                ],
                'preview' => [
                    'uz' => 'Optimal harorat, namlik va atmosfera tarkibi.',
                    'ru' => 'Оптимальная температура, влажность и состав атмосферы.',
                    'oz' => 'Optimal temperature, humidity, and atmosphere composition.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz hosilini saqlashda harorat va namlik rejimini qat\'iy nazorat qiling. Mevali mahsulotlar uchun 0-4°C, sabzavotlar uchun 8-12°C optimal. Nisbiy namlikni 85-95% da ushlab turing, burishishni oldini olish uchun. Harorat o\'zgarishlari ± 0.5°C dan oshmasligi kerak.

Boshqariladigan atmosfera (CA) saqlash: kislorod 2-5%, karbonat angidrid 3-5% ga kamaytirib, nafas olishni sekinlashtiring. Etilen ishlab chiqaruvchi mevalarni alohida saqlang. Saqlash omborini tez-tez shamollating va kondensatsiya bo\'lmasin. Partiyalarni muntazam tekshirib, chirib ketayotganlarini olib tashlang. To\'g\'ri saqlash mahsulot umrini 3-6 oygacha uzaytiradi.
UZ,
                    'ru' => <<<'RU'
При хранении урожая :crop_ru строго контролируйте температурный и влажностный режим. Для плодов оптимальна температура 0-4°C, для овощей 8-12°C. Поддерживайте относительную влажность на уровне 85-95%, предотвращая увядание. Колебания температуры не должны превышать ± 0.5°C.

Хранение в контролируемой атмосфере (КА): снижая кислород до 2-5%, углекислый газ до 3-5%, замедляйте дыхание. Храните отдельно плоды, выделяющие этилен. Регулярно вентилируйте хранилище и не допускайте конденсации. Регулярно осматривайте партии, удаляя гнилые. Правильное хранение продлевает срок продукции до 3-6 месяцев.
RU,
                    'oz' => <<<'EN'
When storing :crop_en harvest, strictly control temperature and humidity regime. For fruits, optimal temperature is 0-4°C, for vegetables 8-12°C. Maintain relative humidity at 85-95%, preventing wilting. Temperature fluctuations should not exceed ± 0.5°C.

Controlled atmosphere (CA) storage: by reducing oxygen to 2-5%, carbon dioxide to 3-5%, slow respiration. Store ethylene-producing fruits separately. Regularly ventilate storage and prevent condensation. Regularly inspect batches, removing rotten ones. Proper storage extends product life to 3-6 months.
EN,
                ],
            ],
            [
                'key' => 'nutrient-solution',
                'tags' => ['hydroponics', 'nutrient-management'],
                'icon' => 'hydroponics',
                'title' => [
                    'uz' => ':crop_uz uchun gidroponik ozuqa eritmasi',
                    'ru' => 'Гидропонный питательный раствор для :crop_ru',
                    'oz' => 'Hydroponic Nutrient Solution for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Makro va mikro elementlar, EC va pH nazorati.',
                    'ru' => 'Макро- и микроэлементы, контроль EC и pH.',
                    'oz' => 'Macro and micronutrients, EC and pH control.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz gidroponik tizimda ozuqa eritmasini to\'g\'ri tayyorlash hosildorlikning 50% ini belgilaydi. Makroelementlar (NPK, Ca, Mg, S) va mikroelementlar (Fe, Mn, Zn, Cu, B, Mo) muvozanatli nisbatda bo\'lishi kerak. Tayyor koncentratlar (A va B eritmalar) ishlatiladi yoki mineral tuzlardan tayyorlanadi.

EC darajasini 1.5-2.5 dS/m da, pH ni 5.5-6.5 oralig\'ida ushlab turing. Kuniga 2 marta EC va pH ni o\'lchang va tuzating. Eritma harorati 18-22°C bo\'lishi kerak. Har 2 haftada eritma to\'liq almashtiriladi. Kislorod ta\'minoti uchun aeratsiya tizimi (havo nasosi) o\'rnating. Ozuqa balansi o\'simlik fazasiga qarab o\'zgaradi: vegetativ fazada N ko\'proq, generativ fazada K ko\'proq.
UZ,
                    'ru' => <<<'RU'
В гидропонной системе для :crop_ru правильное приготовление питательного раствора определяет 50% урожайности. Макроэлементы (NPK, Ca, Mg, S) и микроэлементы (Fe, Mn, Zn, Cu, B, Mo) должны быть в сбалансированных соотношениях. Используются готовые концентраты (растворы А и Б) или готовятся из минеральных солей.

Поддерживайте EC на уровне 1.5-2.5 дС/м, pH в диапазоне 5.5-6.5. Измеряйте и корректируйте EC и pH дважды в день. Температура раствора должна быть 18-22°C. Каждые 2 недели раствор полностью меняется. Для обеспечения кислорода установите систему аэрации (воздушный насос). Баланс питания меняется по фазам растения: в вегетативной фазе больше N, в генеративной больше K.
RU,
                    'oz' => <<<'EN'
In hydroponic system for :crop_en, proper nutrient solution preparation determines 50% of yield. Macronutrients (NPK, Ca, Mg, S) and micronutrients (Fe, Mn, Zn, Cu, B, Mo) must be in balanced ratios. Ready concentrates (A and B solutions) are used or prepared from mineral salts.

Maintain EC at 1.5-2.5 dS/m, pH in 5.5-6.5 range. Measure and correct EC and pH twice daily. Solution temperature should be 18-22°C. Every 2 weeks solution is completely changed. To ensure oxygen, install aeration system (air pump). Nutrient balance changes by plant phase: in vegetative phase more N, in generative phase more K.
EN,
                ],
            ],
            [
                'key' => 'wind-damage',
                'tags' => ['climate-adaptation', 'crop-protection'],
                'icon' => 'wind',
                'title' => [
                    'uz' => ':crop_uz ni shamol zararidan himoyalash',
                    'ru' => 'Защита :crop_ru от ветрового повреждения',
                    'oz' => 'Protecting :crop_en from Wind Damage',
                ],
                'preview' => [
                    'uz' => 'Mexanik himoya, tayanch va shamol bardoshli navlar.',
                    'ru' => 'Механическая защита, опоры и ветроустойчивые сорта.',
                    'oz' => 'Mechanical protection, supports, and wind-resistant varieties.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz kuchli shamoldan shikastlanishi mumkin: barglar yirtilib, novdalar sinib, mevalar tushib ketadi. Shamol to\'siqlari (daraxtlar qatorlari, devor, to\'r) asosiy shamol yo\'nalishiga perpendikulyar o\'rnating. To\'siq balandligining 15-20 baravariga teng masofada himoya effekti bo\'ladi.

Baland o\'sadigan navlarni qoziqlarga yoki shpaleralarga bog\'lab qo\'ying. Shamol bardoshli navlarni tanlang — qisqa poyali, kuchli ildizli. Sug\'orishni to\'g\'ri boshqarib, o\'simliklar turg\'unligini oshiring. Kuchli shamol kutilayotganda dalaga qayta qo\'shimcha sug\'orish bering. Shamol zararidan keyin shikastlangan qismlarni kesib olib, infektsiya kirishini oldini oling.
UZ,
                    'ru' => <<<'RU'
:crop_ru может повреждаться сильным ветром: листья рвутся, побеги ломаются, плоды опадают. Ветрозаломы (ряды деревьев, стена, сетка) устанавливайте перпендикулярно господствующему направлению ветра. Защитный эффект действует на расстояние в 15-20 раз превышающее высоту залома.

Высокорослые сорта подвязывайте к кольям или шпалерам. Выбирайте ветроустойчивые сорта — низкостебельные, с сильной корневой системой. Правильно управляя орошением, повышайте устойчивость растений. При ожидании сильного ветра дайте полю дополнительный полив. После ветрового повреждения удалите повреждённые части, предотвращая заражение.
RU,
                    'oz' => <<<'EN'
:crop_en can be damaged by strong wind: leaves tear, shoots break, fruits drop. Install windbreaks (tree rows, wall, net) perpendicular to prevailing wind direction. Protective effect works at distance 15-20 times break height.

Tie tall varieties to stakes or trellises. Choose wind-resistant varieties — short-stalked, with strong root system. By properly managing irrigation, increase plant stability. When strong wind is expected, give field additional watering. After wind damage, remove damaged parts, preventing infection.
EN,
                ],
            ],
            [
                'key' => 'photoperiod-management',
                'tags' => ['crop-management', 'flowering-control'],
                'icon' => 'light',
                'title' => [
                    'uz' => ':crop_uz fotoperiodini nazorat qilish',
                    'ru' => 'Контроль фотопериода для :crop_ru',
                    'oz' => 'Photoperiod Control for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Kun uzunligi, gullash induksiyasi va qo\'shimcha yoritish.',
                    'ru' => 'Длина дня, индукция цветения и дополнительное освещение.',
                    'oz' => 'Day length, flowering induction, and supplemental lighting.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz gullashi kun uzunligiga bog\'liq bo\'lishi mumkin. Qisqa kun o\'simliklari (xrizantema, soya) 12 soatdan kam yorug\'likda gullaydi. Uzun kun o\'simliklari (ismaloq, lobiya) 14 soatdan ortiq yorug\'likda gullashadi. Neytral kun o\'simliklari (pomidor, bodring) kun uzunligiga sezgir emas.

Gullashni boshqarish uchun qo\'shimcha yoritish (natriyli yoki LED lampalar) qo\'llang. Yoritish intensivligi 10,000-15,000 lux bo\'lishi kerak. Qisqa kun yaratish uchun qora plonka bilan yopib, yorug\'lik vaqtini qisqartiring. Fotoperiod nazorati bozorga chiqarish vaqtini rejalashtirish va hosildorlikni oshirish uchun ishlatiladi. Energiya tejash uchun LED texnologiyasidan foydalaning.
UZ,
                    'ru' => <<<'RU'
Цветение :crop_ru может зависеть от длины дня. Растения короткого дня (хризантема, соя) зацветают при менее 12 часов света. Растения длинного дня (шпинат, бобы) цветут при более 14 часов света. Нейтральные к дню растения (томаты, огурцы) нечувствительны к длине дня.

Для управления цветением применяйте дополнительное освещение (натриевые или LED лампы). Интенсивность освещения должна быть 10,000-15,000 люкс. Для создания короткого дня закрывайте чёрной плёнкой, сокращая световое время. Контроль фотопериода используется для планирования выхода на рынок и повышения урожайности. Для экономии энергии используйте LED технологию.
RU,
                    'oz' => <<<'EN'
:crop_en flowering may depend on day length. Short-day plants (chrysanthemum, soybean) flower with less than 12 hours of light. Long-day plants (spinach, beans) flower with more than 14 hours of light. Day-neutral plants (tomatoes, cucumbers) are insensitive to day length.

To control flowering, apply supplemental lighting (sodium or LED lamps). Light intensity should be 10,000-15,000 lux. To create short day, cover with black film, shortening light time. Photoperiod control is used to plan market entry and increase yields. To save energy, use LED technology.
EN,
                ],
            ],
            [
                'key' => 'regeneration-pruning',
                'tags' => ['orchard-management', 'rejuvenation'],
                'icon' => 'regeneration',
                'title' => [
                    'uz' => ':crop_uz eski bog\'larini qayta tiklashtirish',
                    'ru' => 'Омоложение старых садов :crop_ru',
                    'oz' => 'Rejuvenating Old :crop_en Orchards',
                ],
                'preview' => [
                    'uz' => 'Kuchli kesim, yangi novdalar o\'stirish va hosildorlikni tiklash.',
                    'ru' => 'Сильная обрезка, выращивание новых побегов и восстановление урожайности.',
                    'oz' => 'Heavy pruning, growing new shoots, and yield recovery.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz eski daraxtlarini (15-20 yildan oshgan) qayta tiklashtirish uchun bosqichli yondashuv qo\'llang. Birinchi yil skeletal novdalarning uchdan bir qismini ildiz bo\'ynidan 50-60 sm balandlikda kesib oling. Ikkinchi yil qolgan novdalarni kesing. Har yil 5-6 ta kuchli yangi novdani qoldirib, boshqalarini olib tashlang.

Qayta tiklash kesimidan keyin azot o\'g\'itini ko\'paytirib (150-200 kg/ha N), yangi o\'sishni rag\'batlantiring. Daraxtlarni sug\'orishni ta\'minlang va zararkunandalardan himoya qiling. Yangi novdalarga shakllanish kesimi qo\'llab, ochiq struktura yarating. Qayta tiklashdan 3-4 yil keyin to\'liq hosildorlik tiklanadi. Juda qari va kasallangan daraxtlarni o\'rniga yangisini eking.
UZ,
                    'ru' => <<<'RU'
Для омоложения старых деревьев :crop_ru (старше 15-20 лет) применяйте поэтапный подход. В первый год обрежьте треть скелетных ветвей на высоте 50-60 см от корневой шейки. Во второй год обрежьте остальные ветви. Ежегодно оставляйте 5-6 сильных новых побегов, остальные удаляйте.

После омолаживающей обрезки увеличьте азотные удобрения (150-200 кг/га N), стимулируя новый рост. Обеспечьте деревьям полив и защиту от вредителей. Применяйте формирующую обрезку к новым побегам, создавая открытую структуру. Через 3-4 года после омоложения полная урожайность восстанавливается. Очень старые и больные деревья замените новыми.
RU,
                    'oz' => <<<'EN'
To rejuvenate old :crop_en trees (over 15-20 years), apply phased approach. In first year, cut one-third of scaffold branches at 50-60 cm height from root collar. In second year, cut remaining branches. Annually leave 5-6 strong new shoots, remove others.

After rejuvenation pruning, increase nitrogen fertilizers (150-200 kg/ha N), stimulating new growth. Provide trees with irrigation and pest protection. Apply training pruning to new shoots, creating open structure. 3-4 years after rejuvenation, full productivity is restored. Replace very old and diseased trees with new ones.
EN,
                ],
            ],
            [
                'key' => 'companion-planting',
                'tags' => ['crop-planning', 'biodiversity'],
                'icon' => 'companion',
                'title' => [
                    'uz' => ':crop_uz bilan hamroh o\'simliklar ekish',
                    'ru' => 'Посадка растений-компаньонов с :crop_ru',
                    'oz' => 'Companion Planting with :crop_en',
                ],
                'preview' => [
                    'uz' => 'O\'zaro foydali ta\'sir, zararkunandalarni haydash va hosildorlikni oshirish.',
                    'ru' => 'Взаимовыгодное воздействие, отпугивание вредителей и повышение урожайности.',
                    'oz' => 'Mutual benefit, pest repelling, and yield increase.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz bilan hamroh o\'simliklar ekish biologik nazoratni yaxshilaydi va hosildorlikni oshiradi. Yaxshi hamrohlar — dukkaklilar (loviya, no\'xat) azot qo\'shadi. Hid chiqaruvchi o\'simliklar (sarimsoq, piyoz, reyhan) zararkunandalarni haydab qiladi. Chuqur ildizli o\'simliklar (arpabodyon) tuproq tuzilmasini yaxshilaydi.

Yomon hamrohlar — bir oiladagi o\'simliklar bir xil zararkunanda va kasalliklarni jalb qiladi. Qatorlar oralig\'ida hamroh o\'simliklarni eking yoki dala chetlariga joylashtiring. Gullar (kalendula, tagetes) foydali hasharotlarni jalb qiladi. Hamroh ekish begona o\'tlarni bostiradi va mikroiqlimni yaxshilaydi. Tajribada eng yaxshi kombinatsiyalarni sinab boring.
UZ,
                    'ru' => <<<'RU'
Посадка растений-компаньонов с :crop_ru улучшает биоконтроль и повышает урожайность. Хорошие компаньоны — бобовые (бобы, горох) добавляют азот. Ароматические растения (чеснок, лук, базилик) отпугивают вредителей. Глубококорневые растения (люцерна) улучшают структуру почвы.

Плохие компаньоны — растения одного семейства привлекают одних и тех же вредителей и болезни. Высаживайте компаньоны в междурядьях или размещайте по краям поля. Цветы (календула, бархатцы) привлекают полезных насекомых. Компаньонная посадка подавляет сорняки и улучшает микроклимат. Экспериментально испытывайте лучшие комбинации.
RU,
                    'oz' => <<<'EN'
Companion planting with :crop_en improves biocontrol and increases yields. Good companions — legumes (beans, peas) add nitrogen. Aromatic plants (garlic, onions, basil) repel pests. Deep-rooted plants (alfalfa) improve soil structure.

Bad companions — plants from same family attract same pests and diseases. Plant companions in row spacing or place along field edges. Flowers (calendula, marigold) attract beneficial insects. Companion planting suppresses weeds and improves microclimate. Experimentally test best combinations.
EN,
                ],
            ],
            [
                'key' => 'salt-tolerance',
                'tags' => ['soil-management', 'stress-tolerance'],
                'icon' => 'salt',
                'title' => [
                    'uz' => ':crop_uz ni sho\'rli tuproqlarda yetishtirish',
                    'ru' => 'Выращивание :crop_ru на засолённых почвах',
                    'oz' => 'Growing :crop_en on Saline Soils',
                ],
                'preview' => [
                    'uz' => 'Sho\'rga chidamli navlar, yuvish va drenaj.',
                    'ru' => 'Солеустойчивые сорта, промывка и дренаж.',
                    'oz' => 'Salt-tolerant varieties, leaching, and drainage.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ni sho\'rli tuproqlarda (EC > 4 dS/m) o\'stirish uchun sho\'rga chidamli navlarni tanlang. Ekishdan oldin yuvish suv berish (150-200 mm) qilib, tuzlarni pastki qatlamlarga yuvib tashlang. Chuqur drenaj tizimlarini (1.5-2 m) o\'rnatib, yer osti suvlari sathini pastga tushiring.

Organik modda (kompost, chirindi) sho\'rning ta\'sirini kamaytiradi va tuproq tuzilmasini yaxshilaydi. Tez-tez lekin oz miqdorda sug\'orish tuproq eritmasini suylantiradi. Kaliy va kalsiy o\'g\'itlari natriy ta\'sirini neytrallashtiradi. Gips (kalsiy sulfat) natriyni almashtiradi va yuvishni osonlashtiradi. Sho\'rlanishni muntazam monitoring qilib, choralar ko\'ring.
UZ,
                    'ru' => <<<'RU'
Для выращивания :crop_ru на засолённых почвах (EC > 4 дС/м) выбирайте солеустойчивые сорта. Перед посадкой проведите промывные поливы (150-200 мм), вымывая соли в нижние горизонты. Установите глубокие дренажные системы (1.5-2 м), понижая уровень грунтовых вод.

Органическое вещество (компост, навоз) снижает воздействие солей и улучшает структуру почвы. Частые, но малые поливы разбавляют почвенный раствор. Калийные и кальциевые удобрения нейтрализуют воздействие натрия. Гипс (сульфат кальция) замещает натрий и облегчает промывку. Регулярно мониторьте засоление и принимайте меры.
RU,
                    'oz' => <<<'EN'
To grow :crop_en on saline soils (EC > 4 dS/m), choose salt-tolerant varieties. Before planting, conduct leaching irrigation (150-200 mm), washing salts to lower horizons. Install deep drainage systems (1.5-2 m), lowering groundwater level.

Organic matter (compost, manure) reduces salt impact and improves soil structure. Frequent but small irrigations dilute soil solution. Potassium and calcium fertilizers neutralize sodium impact. Gypsum (calcium sulfate) replaces sodium and facilitates leaching. Regularly monitor salinity and take measures.
EN,
                ],
            ],
            [
                'key' => 'carbon-footprint',
                'tags' => ['sustainability', 'climate-smart'],
                'icon' => 'carbon',
                'title' => [
                    'uz' => ':crop_uz ishlab chiqarishida uglerod izini kamaytirish',
                    'ru' => 'Снижение углеродного следа в производстве :crop_ru',
                    'oz' => 'Reducing Carbon Footprint in :crop_en Production',
                ],
                'preview' => [
                    'uz' => 'Energiya tejash, organik o\'g\'itlar va minimal haydash.',
                    'ru' => 'Экономия энергии, органические удобрения и минимальная обработка.',
                    'oz' => 'Energy savings, organic fertilizers, and minimal tillage.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz ishlab chiqarishida uglerod izini kamaytirish uchun energiya samaradorligini oshiring. Qayta tiklanadigan energiya (quyosh panellari) dan foydalaning. Sintetik o\'g\'itlar ishlab chiqarilishi ko\'p energiya talab qiladi — organik o\'g\'itlarga o\'ting. Mahalliy o\'g\'it va pestitsid manbalarini ishlatib, transport izini kamaytiring.

Minimal haydash yoki to\'g\'ridan-to\'g\'ri ekish tuproqdan CO2 chiqishini 30-40% ga kamaytiradi. Qoplovchi ekinlar va agroleschilik tuproqda uglerodni saqlaydi. Suv samaradorligini oshirish uchun tomchilatib sug\'orish qo\'llang. Yoqilg\'i tejovchi texnikalar va GPS navigatsiya yordamida haydash yo\'llarini optimallashtiring. Uglerod izini hisoblang va kamaytirishga qaratilgan maqsadlar qo\'ying.
UZ,
                    'ru' => <<<'RU'
Для снижения углеродного следа в производстве :crop_ru повышайте энергоэффективность. Используйте возобновляемую энергию (солнечные панели). Производство синтетических удобрений энергоёмко — переходите на органические. Используя местные источники удобрений и пестицидов, снижайте транспортный след.

Минимальная обработка или прямой посев снижают выброс CO2 из почвы на 30-40%. Покровные культуры и агролесоводство секвестрируют углерод в почве. Для повышения водной эффективности применяйте капельное орошение. С помощью топливосберегающей техники и GPS-навигации оптимизируйте пути обработки. Рассчитывайте углеродный след и ставьте цели по его снижению.
RU,
                    'oz' => <<<'EN'
To reduce carbon footprint in :crop_en production, increase energy efficiency. Use renewable energy (solar panels). Synthetic fertilizer production is energy-intensive — switch to organic. Using local fertilizer and pesticide sources, reduce transport footprint.

Minimal tillage or direct seeding reduces CO2 emission from soil by 30-40%. Cover crops and agroforestry sequester carbon in soil. To increase water efficiency, apply drip irrigation. With fuel-efficient equipment and GPS navigation, optimize cultivation paths. Calculate carbon footprint and set reduction targets.
EN,
                ],
            ],
            [
                'key' => 'post-harvest-treatment',
                'tags' => ['postharvest', 'quality-management'],
                'icon' => 'treatment',
                'title' => [
                    'uz' => ':crop_uz hosilini hosildan keyingi ishlov berish',
                    'ru' => 'Послеуборочная обработка урожая :crop_ru',
                    'oz' => 'Post-Harvest Treatment of :crop_en',
                ],
                'preview' => [
                    'uz' => 'Yuvish, sortlash, mum qoplash va o\'rash.',
                    'ru' => 'Мойка, сортировка, вощение и упаковка.',
                    'oz' => 'Washing, sorting, waxing, and packaging.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz hosilini yig\'ib olgandan keyin darhol salqin joyga (12-15°C) o\'tkazing. Yuvish — xlor (50-100 ppm) yoki organik antiseptiklar (sirka kislotasi) bilan ifloslanish va patogenlarni olib tashlang. Quritish — havo bilan puflab yoki sochiq bilan nam oldiriladi.

Sortlash — o\'lcham, rang, shakl va sifat bo\'yicha darajalar ajratiladi. Shikastlangan va kasallangan mahsulotlarni olib tashlang. Mum qoplash (pishloqli mevalar uchun) namlikni saqlaydi va chiroyli ko\'rinish beradi. Qadoqlash — ventilyatsiyalangan qutilarga joylashtirib, transport zararini kamaytiring. Sovutish zanjiriga darhol kiriting.
UZ,
                    'ru' => <<<'RU'
После уборки урожая :crop_ru немедленно переместите в прохладное место (12-15°C). Мойка — хлором (50-100 ppm) или органическими антисептиками (уксусная кислота) удаляет загрязнения и патогены. Сушка — обдувом воздухом или полотенцем удаляется влага.

Сортировка — по размеру, цвету, форме и качеству разделяются классы. Удаляйте повреждённую и больную продукцию. Вощение (для косточковых плодов) сохраняет влагу и придаёт привлекательный вид. Упаковка — размещайте в вентилируемых ящиках, снижая транспортные повреждения. Немедленно введите в холодильную цепь.
RU,
                    'oz' => <<<'EN'
After harvesting :crop_en, immediately move to cool place (12-15°C). Washing — with chlorine (50-100 ppm) or organic antiseptics (acetic acid) removes contamination and pathogens. Drying — with air blowing or towel, moisture is removed.

Sorting — by size, color, shape, and quality, grades are separated. Remove damaged and diseased products. Waxing (for stone fruits) retains moisture and gives attractive appearance. Packaging — place in ventilated boxes, reducing transport damage. Immediately enter cold chain.
EN,
                ],
            ],
            [
                'key' => 'crop-maturity-indices',
                'tags' => ['harvest-management', 'quality-assessment'],
                'icon' => 'maturity',
                'title' => [
                    'uz' => ':crop_uz yetukligini aniqlash belgilari',
                    'ru' => 'Индикаторы зрелости :crop_ru',
                    'oz' => 'Maturity Indices for :crop_en',
                ],
                'preview' => [
                    'uz' => 'Fizikaviy, kimyoviy va fiziologik yetuklik belgilari.',
                    'ru' => 'Физические, химические и физиологические признаки зрелости.',
                    'oz' => 'Physical, chemical, and physiological maturity signs.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_uz to\'g\'ri yetuklikda yig\'ilishi sifat va saqlashga ta\'sir qiladi. Fizikaviy belgilar — rang o\'zgarishi, hajm o\'sishi to\'xtashi, qobiq qalinligi. Kimyoviy belgilar — shakar miqdori (brix 12-14%), kislota pasayishi, kraxmal shakarba aylanishi. Fiziologik belgilar — meva oson ajralishi, urug\' qorayishi.

Refraktometr bilan shakar miqdorini o\'lchang. Penetrometr bilan qattiqligini tekshiring. Yod testi bilan kraxmal holatini aniqlang. Turli maqsadlar uchun yetuklik darajasi farq qiladi: transport uchun texnik yetuklikda, mahalliy bozor uchun to\'liq yetuklikda yig\'iladi. Har kunlik monitoring qilib, optimal yig\'ish vaqtini aniqlang.
UZ,
                    'ru' => <<<'RU'
Сбор :crop_ru в правильной зрелости влияет на качество и хранение. Физические признаки — изменение цвета, прекращение роста размера, толщина кожуры. Химические признаки — содержание сахара (brix 12-14%), снижение кислотности, превращение крахмала в сахар. Физиологические признаки — лёгкое отделение плода, почернение семян.

Измеряйте содержание сахара рефрактометром. Проверяйте твёрдость пенетрометром. Определяйте состояние крахмала йодным тестом. Для разных целей степень зрелости различается: для транспорта собирают в технической зрелости, для местного рынка в полной зрелости. Ежедневно мониторьте, определяя оптимальное время сбора.
RU,
                    'oz' => <<<'EN'
Harvesting :crop_en at correct maturity affects quality and storage. Physical signs — color change, size growth cessation, peel thickness. Chemical signs — sugar content (brix 12-14%), acidity decrease, starch to sugar conversion. Physiological signs — easy fruit detachment, seed darkening.

Measure sugar content with refractometer. Check firmness with penetrometer. Determine starch state with iodine test. For different purposes, maturity level differs: for transport harvest at technical maturity, for local market at full maturity. Monitor daily, determining optimal harvest time.
EN,
                ],
            ],
        ];
    }

    /**
     * Shared article definitions that span multiple crops.
     */
    private function sharedArticlesDefinition(): array
    {
        return [
            [
                'key' => 'rice-irrigation-sync',
                'slugs' => ['rice-early', 'rice-mid'],
                'icon' => 'rice',
                'tags' => ['water-management', 'crop-planning'],
                'title' => [
                    'uz' => ':crop_list_uz navlari uchun suv rejimini sinxronlashtirish',
                    'ru' => 'Синхронизация водного режима для сортов :crop_list_ru',
                    'oz' => 'Synchronising Water Management for :crop_list_en Varieties',
                ],
                'preview' => [
                    'uz' => 'Birlashtirilgan sug‘orish kalendarini tuzib, ozuqa va pestitsidlarni navlarning ehtiyojiga moslashtirish.',
                    'ru' => 'Общий календарь поливов и питание с учётом потребностей каждого сорта.',
                    'oz' => 'A shared irrigation calendar that respects each variety’s nutrient and protection needs.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_list_uz uchun suv berishni 5–7 kunlik interval bilan rejalashtiring va har sug'orishdan oldin qatorlar bo'ylab suv tezligini tekshirib chiqing. Erta navlar uchun o'tkaziladigan qo'shimcha sug'orishlar o'rta navlar tuprog'ini haddan ziyod namlantirmasligi uchun qatorlarga navbatma-navbat suv berish sxemasidan foydalaning. Fosfor va mikroelementlarni barg tahlili asosida bo'lish orqali har bir nav uchun optimal bo'lgan normani tanlang.

Bir vaqtning o'zida purkash ishlarini amalga oshirish zarur bo'lganda, aniq tomchi o'lchamiga ega purkagichlardan foydalanib, navlar orasidagi balandlik farqini inobatga oling. Sug'orish oralig'ida suv analizlarini tasdiqlab, sho'rlanish xavfi aniqlansa, yuvish polivlari tashkil eting. Monitoring jadvali daladagi meteorologik stansiya bilan bog'lanib, navlar bo'yicha gibrid jadvalni doimiy yangilab boradi.
UZ,
                    'ru' => <<<'RU'
Для сортов :crop_list_ru планируйте полив с интервалом 5–7 дней и проверяйте скорость течения воды перед каждым расходом. Дополнительные поливы раннеспелых участков организуйте через ряд, чтобы не переувлажнить поля среднеспелых сортов. Делите фосфор и микроэлементы по листовым анализам, подбирая нормы под каждый сорт.

При необходимости одновременных опрыскиваний используйте опрыскиватели с контролем размера капли и учитывайте разницу в высоте между сортами. Между поливами анализируйте воду: при угрозе засоления проводите промывные поливы. График мониторинга свяжите с метеостанцией в поле и регулярно обновляйте гибридный календарь по сортам.
RU,
                    'oz' => <<<'EN'
Schedule irrigations for :crop_list_en on five- to seven-day intervals and verify canal flow rates before each event. When early plots need extra water, alternate irrigated beds to avoid saturating mid-season blocks. Split phosphorus and micronutrients according to leaf diagnostics so each variety receives the right dose.

If simultaneous spraying is required, use sprayers with controlled droplet size and allow for canopy-height differences. Test water between irrigations; if salinity rises, flush the system with leaching irrigations. Link the monitoring dashboard to the field weather station and refresh the hybrid calendar frequently for each variety.
EN,
                ],
            ],
            [
                'key' => 'orchard-sanitation',
                'slugs' => ['walnut', 'quince', 'persimmon'],
                'icon' => 'orchard',
                'tags' => ['orchard-management', 'disease-management'],
                'title' => [
                    'uz' => 'Bogʻlarda :crop_list_uz uchun sanitariya va saqlash protokoli',
                    'ru' => 'Санитария и хранение для садовых культур: :crop_list_ru',
                    'oz' => 'Sanitation and Storage Protocol for Orchard Crops: :crop_list_en',
                ],
                'preview' => [
                    'uz' => 'Kesish, infeksiya manbalarini yo‘qotish va sovutish zanjirini to‘g‘ri yo‘lga qo‘yish.',
                    'ru' => 'Обрезка, удаление инфекций и налаженный холодильный цепочек.',
                    'oz' => 'Pruning, eliminating inoculum, and running a reliable cold chain.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_list_uz bog'larida to'qimalar qurishini kamaytirish uchun qishdan oldin sanitariya kesimini amalga oshiring va qoziqchalar orqali shamollatishni yaxshilang. Zararlangan novda, qurigan meva va barglarni dala tashqarisida yoqish yoki kompostlash orqali infektsiya manbalarini yo'q qiling. Gullashdan oldingi davrda mis asosidagi preparatlar bilan profilaktik purkash qo'llang.

Saqlash omborlarida haroratni 0–2 °C atrofida, nisbiy namlikni 90–92 % da saqlab, mevalarni nav va yetuklik darajasiga qarab ajrating. Havo aylanishini yaxshilash uchun gorizontal va vertikal kanallarni bo'sh qoldiring, CO₂ konsentratsiyasini sensorlar orqali tekshiring. Mahsulot harakatini kuzatish uchun partiyalarga QR-kod yoki raqamli jurnal biriktirib, FIFO tamoyiliga qat'iy rioya qiling.
UZ,
                    'ru' => <<<'RU'
В садах :crop_list_ru проводите санитарную обрезку до зимы, чтобы сократить подсыхание тканей и улучшить вентиляцию через шпалеры. Удаляйте заражённые ветви, мумифицированные плоды и листья, утилизируя их вне участка или закладывая в горячий компост. До распускания почек выполняйте профилактические обработки медьсодержащими препаратами.

В хранилищах поддерживайте температуру 0–2 °C и влажность 90–92 %, сортируя плоды по степени зрелости. Для эффективной вентиляции оставляйте свободные горизонтальные и вертикальные каналы, контролируйте концентрацию CO₂ датчиками. Помечайте партии QR-кодами или в электронном журнале и строго придерживайтесь принципа FIFO.
RU,
                    'oz' => <<<'EN'
For orchards of :crop_list_en, carry out sanitation pruning before winter to limit dieback and open air channels along trellises. Remove infected wood, mummified fruit, and leaf litter, and dispose of them off-site or in hot compost to eliminate inoculum. Apply copper-based protectants ahead of bud break as a preventative.

Keep storage rooms between 0 and 2 °C with 90–92 percent relative humidity, grading fruit by maturity classes. Maintain clear horizontal and vertical air lanes and monitor CO₂ via sensors. Tag each batch with QR labels or in a digital log and follow strict first-in, first-out movement through the cool chain.
EN,
                ],
            ],
            [
                'key' => 'cucurbit-pollination',
                'slugs' => ['watermelon-early', 'pumpkin'],
                'icon' => 'pollination',
                'tags' => ['pollination', 'crop-protection'],
                'title' => [
                    'uz' => 'Poliz ekinlarida changlatish va himoya: :crop_list_uz tajribasi',
                    'ru' => 'Опыление и защита бахчевых культур: опыт :crop_list_ru',
                    'oz' => 'Pollination and Protection in Cucurbits: Lessons from :crop_list_en',
                ],
                'preview' => [
                    'uz' => 'Asalarilarni jalb qilish, gullash davrida kimyoviy yukni kamaytirish va kasallikni monitoring qilish.',
                    'ru' => 'Привлечение пчёл, снижение химнагрузки в цветение и мониторинг болезней.',
                    'oz' => 'Attract bees, limit chemistry during bloom, and monitor diseases closely.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_list_uz dalalarida gullash oldidan chiziqli gullar aralashmasini ekib, asalarilar uchun suv manbai va qo'nish platformalarini tashkil eting. Kimyoviy ishlovlarni kechqurun yoki ertalab, changchi hasharotlar faol bo'lmaganda bajaring. Un-shudring va bakterial kasalliklarni 7 kunlik intervalda kuzatib, birinchi dog'lar paydo bo'lishi bilan biokarbonat yoki triazol guruhidagi preparatlar bilan ishlov bering.

Sug'orishning tomchilatib usuliga o'tib, barglarni quruq saqlash qo'ziqorin bosimini kamaytiradi. Meva to'plamlarini tuproq bilan bevosita aloqadan himoyalash uchun mulch qatlami yoki taxta qo'llang. Kasallangan mevalarni zudlik bilan olib tashlab, kompostga yuboring, shu bilan zararlanish manbalarini kamaytirasiz.
UZ,
                    'ru' => <<<'RU'
На участках :crop_list_ru высаживайте полосы нектароносov перед цветением и обеспечьте водопой с площадками для посадки пчёл. Проводите химические обработки вечером или ранним утром, когда опылители не активны. Мониторьте мучнистую росу и бактериоз каждые семь дней, а при появлении первых пятен работайте биокарбонатами или препаратами из группы триазолов.

Переход на капельный полив, оставляющий листья сухими, снижает давление грибных заболеваний. Подставляйте мульчу или дощечки под плоды, чтобы избежать контакта с почвой. Удаляйте поражённые плоды сразу и отправляйте их в компост, сокращая источники инфекции.
RU,
                    'oz' => <<<'EN'
Across :crop_list_en fields sow nectar-rich strips before bloom and provide clean water sources with landing pads for bees. Time any pesticide sprays for late evening or early morning when pollinators are inactive. Scout weekly for powdery mildew and bacterial spots, and at the first lesions apply biocarbonate or triazole-based protectants.

Switching to drip irrigation that keeps foliage dry lowers fungal pressure. Place mulch mats or boards beneath fruit to prevent soil contact. Remove infected fruit immediately and compost it to reduce inoculum in the block.
EN,
                ],
            ],
            [
                'key' => 'rotation-legume-cereal',
                'slugs' => ['soybean-main', 'triticum-aestivum-l', 'solanum-melongena'],
                'icon' => 'rotation',
                'tags' => ['crop-rotation', 'soil-fertility'],
                'title' => [
                    'uz' => 'Almashlab ekish zanjiri: :crop_list_uz bo‘yicha dalalar dizayni',
                    'ru' => 'Севооборот с участием :crop_list_ru',
                    'oz' => 'Designing Rotations with :crop_list_en',
                ],
                'preview' => [
                    'uz' => 'Ozuqa aylanishi, tuproq tuzilmasi va qoldiqlarni boshqarish orqali sog‘lom sikl yaratish.',
                    'ru' => 'Кругооборот питательных элементов, структура почвы и управление остатками.',
                    'oz' => 'Close the nutrient loop, protect soil structure, and manage residues well.',
                ],
                'body' => [
                    'uz' => <<<'UZ'
:crop_list_uz ketma-ketligida dukkakli navlarning ildizidagi tuganaklar orqali azot 35–45 kg/ga gacha to'planadi va keyingi g'alla maydonlarining ehtiyojini qisman qoplaydi. Donli navlardan keyin tomatdoshlarga yaqin bo'lgan ekinlarni joylashtirib, kasallik bosimini kamaytirish uchun dala chetlariga bodring yoki poliz o'simliklarini tampon sifatida eking. Har bir bosqichda minimal ishlov va qisman qoldiq qoldirish tuproqning agregat tuzilmasini saqlaydi.

Hosil qoldiqlarini maydalagich bilan 5 sm gacha bo'laklab, karbonitlash jarayonini tezlashtiring va har 3 yilda bir bor chuqur haydash bilan qattiq qatlamlarni yengillashtiring. Qatorlararo sidirga ekish tuproqdagi foydali mikroorganizmlarni qo'llab-quvvatlaydi va namlikni ushlab turadi. Fermer monitoringi uchun har mavsumda tuproq tahlillarini o'simlik qoldiqlaridan keyin o'tkazib, fosfor hamda kaliy balansini tekshirib boring.
UZ,
                    'ru' => <<<'RU'
В чередовании :crop_list_ru бобовые культуры накапливают до 35–45 кг/га азота в корневых клубеньках и частично обеспечивают потребности последующей пшеницы. После зерновых размещайте паслёновые, используя бахчевые растения по краям поля как буфер против болезней. Минимальная обработка и оставление части растительных остатков сохраняют агрегатную структуру почвы.

Измельчайте солому до 5 см, чтобы ускорить минерализацию, и каждые три года выполняйте глубокую вспашку для разрушения плужной подошвы. Подсев сидератов между рядами поддерживает полезную микрофлору и удерживает влагу. Проводите сезонные почвенные анализы после заделки остатков, контролируя баланс фосфора и калия.
RU,
                    'oz' => <<<'EN'
Within a rotation of :crop_list_en, legume nodulation contributes 35–45 kg/ha of nitrogen that supports following wheat blocks. After cereals, slot Solanaceae crops and buffer field edges with cucurbits to dilute disease pressure. Reduced tillage plus partial residue cover protects soil aggregates.

Shred residues to under 5 cm to speed mineralisation and rip deeply every third season to relieve compaction layers. Sow cover strips between rows to foster beneficial microbes and conserve moisture. Run seasonal soil tests after residue incorporation to keep phosphorus and potassium balances in check.
EN,
                ],
            ],
        ];
    }

    private function applyTemplate(array $template, array $replacements, array $extra = []): array
    {
        $article = [
            'title_uz' => $this->format($template['title']['uz'], $replacements),
            'title_ru' => $this->format($template['title']['ru'], $replacements),
            'title_oz' => $this->format($template['title']['oz'], $replacements),
            'preview_uz' => $this->format($template['preview']['uz'], $replacements),
            'preview_ru' => $this->format($template['preview']['ru'], $replacements),
            'preview_oz' => $this->format($template['preview']['oz'], $replacements),
            'body_uz' => $this->format($template['body']['uz'], $replacements),
            'body_ru' => $this->format($template['body']['ru'], $replacements),
            'body_oz' => $this->format($template['body']['oz'], $replacements),
            'attachment' => $template['attachment'] ?? null,
            'icon' => $template['icon'] ?? null,
            'tags' => $template['tags'] ?? [],
            'preview_image' => $template['preview_image'] ?? null,
        ];

        $article = array_merge($article, Arr::except($extra, ['preview_image_seed']));

        if (empty($article['preview_image'])) {
            $seed = $extra['preview_image_seed'] ?? (($replacements[':crop_slug'] ?? '').'-'.($template['key'] ?? 'article'));
            $article['preview_image'] = $this->buildPreviewImage($seed, $template['key'] ?? 'article');
        }

        return $article;
    }

    private function format(string $value, array $replacements): string
    {
        return strtr($value, $replacements);
    }

    private function hashMod(string $value, int $mod): int
    {
        return $mod > 0 ? (int) (crc32($value) % $mod) : 0;
    }

    private function buildPreviewImage(string $seed, string $key): string
    {
        $imageMap = [
            'soil-preparation' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=960&h=540&fit=crop', // Tractor plowing field
            'irrigation-scheduling' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=960&h=540&fit=crop', // Irrigation system
            'nutrition-plan' => 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?w=960&h=540&fit=crop', // Fertilizer/farming
            'pest-monitoring' => 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=960&h=540&fit=crop', // Plant monitoring
            'harvest-postharvest' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=960&h=540&fit=crop', // Harvest
            'climate-resilience' => 'https://images.unsplash.com/photo-1592982537447-7440770cbfc9?w=960&h=540&fit=crop', // Climate/weather
            'rice-irrigation-sync' => 'https://images.unsplash.com/photo-1536882240095-0379873feb4e?w=960&h=540&fit=crop', // Rice field
            'orchard-sanitation' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=960&h=540&fit=crop', // Orchard
            'cucurbit-pollination' => 'https://images.unsplash.com/photo-1563514227147-6d2ff665a6a0?w=960&h=540&fit=crop', // Cucurbit plants
            'rotation-legume-cereal' => 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?w=960&h=540&fit=crop', // Crop rotation field
            'seed-selection' => 'https://images.unsplash.com/photo-1523348837708-15d4a09cfac2?w=960&h=540&fit=crop', // Seeds
            'organic-farming' => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=960&h=540&fit=crop', // Organic vegetables
            'drip-irrigation-setup' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=960&h=540&fit=crop', // Drip irrigation
            'mulching-techniques' => 'https://images.unsplash.com/photo-1592419044706-39796d40f98c?w=960&h=540&fit=crop', // Mulch on soil
            'composting' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=960&h=540&fit=crop', // Compost
            'greenhouse-management' => 'https://images.unsplash.com/photo-1530836176989-4988b0d2c8cb?w=960&h=540&fit=crop', // Greenhouse
            'crop-insurance' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=960&h=540&fit=crop', // Farm planning/insurance
            'cover-cropping' => 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=960&h=540&fit=crop', // Cover crops
            'precision-agriculture' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=960&h=540&fit=crop', // Drone/tech agriculture
            'water-quality' => 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=960&h=540&fit=crop', // Water testing
            'mechanization' => 'https://images.unsplash.com/photo-1581093588401-fbb62a02f120?w=960&h=540&fit=crop', // Farm machinery
            'market-linkages' => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?w=960&h=540&fit=crop', // Market/vegetables
            'climate-smart' => 'https://images.unsplash.com/photo-1611273426858-450d8e3c9fce?w=960&h=540&fit=crop', // Sustainable farming
            'biological-control' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=960&h=540&fit=crop', // Beneficial insects
            'water-harvesting' => 'https://images.unsplash.com/photo-1527004013197-933c4bb611b3?w=960&h=540&fit=crop', // Water pond/reservoir
            'leaf-analysis' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=960&h=540&fit=crop', // Lab testing
            'pollinator-conservation' => 'https://images.unsplash.com/photo-1563514227147-6d2ff665a6a0?w=960&h=540&fit=crop', // Bees/pollinators
            'farm-record-keeping' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=960&h=540&fit=crop', // Farm records/tablet
            'rootstock-selection' => 'https://images.unsplash.com/photo-1521070171885-5fa54d271a4c?w=960&h=540&fit=crop', // Fruit tree/orchard
            'intercropping' => 'https://images.unsplash.com/photo-1595854341625-f33ee10dbf94?w=960&h=540&fit=crop', // Mixed crops
            'frost-protection' => 'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?w=960&h=540&fit=crop', // Winter/frost on plants
            'value-addition' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a780?w=960&h=540&fit=crop', // Processing/packaging
            'soil-testing' => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=960&h=540&fit=crop', // Soil in hands
        ];

        return $imageMap[$key] ?? 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=960&h=540&fit=crop';
    }

    private function formatCropList(Collection $crops, string $locale): string
    {
        $names = $crops->map(function (Crop $crop) use ($locale) {
            $fallback = $crop->name['en'] ?? $crop->name['uz'] ?? $crop->name['ru'] ?? ('Crop '.$crop->id);

            return $crop->name[$locale] ?? $crop->name['en'] ?? $crop->name['uz'] ?? $crop->name['ru'] ?? $fallback;
        })->values();

        if ($names->isEmpty()) {
            return '';
        }

        if ($names->count() === 1) {
            return $names->first();
        }

        $last = $names->pop();
        $separator = match ($locale) {
            'ru' => ' и ',
            'uz' => ' va ',
            default => ' and ',
        };

        return $names->implode(', ').$separator.$last;
    }

    private function ensureCropSlugsExist(array $cropSlugs, array $cropIdMap, string $title): void
    {
        foreach ($cropSlugs as $slug) {
            if (! isset($cropIdMap[$slug])) {
                throw new RuntimeException("Article \"{$title}\" references missing crop slug [{$slug}]");
            }
        }
    }

    private function getTagTranslations(string $slug): array
    {
        $translations = [
            'soil-management' => [
                'uz' => 'Tuproqni boshqarish',
                'ru' => 'Управление почвой',
                'en' => 'Soil Management',
            ],
            'good-agriculture-practices' => [
                'uz' => 'Yaxshi qishloq xo\'jaligi amaliyoti',
                'ru' => 'Передовая агропрактика',
                'en' => 'Good Agriculture Practices',
            ],
            'irrigation' => [
                'uz' => 'Sug\'orish',
                'ru' => 'Орошение',
                'en' => 'Irrigation',
            ],
            'water-management' => [
                'uz' => 'Suv boshqaruvi',
                'ru' => 'Управление водой',
                'en' => 'Water Management',
            ],
            'fertilization' => [
                'uz' => 'O\'g\'itlash',
                'ru' => 'Удобрение',
                'en' => 'Fertilization',
            ],
            'nutrient-management' => [
                'uz' => 'Ozuqa moddalarini boshqarish',
                'ru' => 'Управление питательными веществами',
                'en' => 'Nutrient Management',
            ],
            'pest-management' => [
                'uz' => 'Zararkunandalarni boshqarish',
                'ru' => 'Управление вредителями',
                'en' => 'Pest Management',
            ],
            'integrated-pest-management' => [
                'uz' => 'Integratsiyalashgan zararkunanda boshqaruvi',
                'ru' => 'Интегрированная защита растений',
                'en' => 'Integrated Pest Management',
            ],
            'harvest-management' => [
                'uz' => 'Hosil yig\'ishni boshqarish',
                'ru' => 'Управление уборкой',
                'en' => 'Harvest Management',
            ],
            'postharvest' => [
                'uz' => 'Hosildan keyingi ishlov',
                'ru' => 'Послеуборочная обработка',
                'en' => 'Postharvest',
            ],
            'climate-adaptation' => [
                'uz' => 'Iqlimga moslashish',
                'ru' => 'Адаптация к климату',
                'en' => 'Climate Adaptation',
            ],
            'crop-management' => [
                'uz' => 'Ekin boshqaruvi',
                'ru' => 'Управление культурами',
                'en' => 'Crop Management',
            ],
            'crop-planning' => [
                'uz' => 'Ekin rejalashtirish',
                'ru' => 'Планирование посевов',
                'en' => 'Crop Planning',
            ],
            'orchard-management' => [
                'uz' => 'Bog\' boshqaruvi',
                'ru' => 'Управление садом',
                'en' => 'Orchard Management',
            ],
            'disease-management' => [
                'uz' => 'Kasalliklarni boshqarish',
                'ru' => 'Управление болезнями',
                'en' => 'Disease Management',
            ],
            'pollination' => [
                'uz' => 'Changlatish',
                'ru' => 'Опыление',
                'en' => 'Pollination',
            ],
            'crop-protection' => [
                'uz' => 'Ekinlarni himoya qilish',
                'ru' => 'Защита растений',
                'en' => 'Crop Protection',
            ],
            'crop-rotation' => [
                'uz' => 'Almashlab ekish',
                'ru' => 'Севооборот',
                'en' => 'Crop Rotation',
            ],
            'soil-fertility' => [
                'uz' => 'Tuproq unumdorligi',
                'ru' => 'Плодородие почвы',
                'en' => 'Soil Fertility',
            ],
        ];

        return $translations[$slug] ?? [
            'uz' => Str::title(str_replace('-', ' ', $slug)),
            'ru' => Str::title(str_replace('-', ' ', $slug)),
            'en' => Str::title(str_replace('-', ' ', $slug)),
        ];
    }
}
