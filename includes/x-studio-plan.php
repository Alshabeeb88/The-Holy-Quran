<?php
declare(strict_types=1);

/**
 * The weekly content template for the X publishing studio.
 *
 * This file is the single source of the plan's text. Both the studio page and
 * the storage layer read it from here, so the two can never drift apart: there
 * is no second copy of these texts anywhere in the project.
 *
 * It is source, not runtime state. Nothing here is written to, and including it
 * has no side effects.
 */

/**
 * Arabic names of the week's days, indexed the way the studio counts them:
 * 0 is Sunday and 6 is Saturday.
 */
function qfa_x_studio_plan_day_names(): array {
    return [
        "الأحد",
        "الاثنين",
        "الثلاثاء",
        "الأربعاء",
        "الخميس",
        "الجمعة",
        "السبت",
    ];
}

/**
 * The 21 posts of the weekly template, three for each day.
 *
 * Each entry carries both what the page renders (time_label, type_label) and
 * what the storage schema records (time on a 24 hour clock, the type slug, and
 * the source a link placeholder points at). source_type is left as 'none'
 * wherever the target cannot be derived with certainty from the text itself.
 */
function qfa_x_studio_plan_posts(): array {
    return [
        [
            'day' => 0,
            'time' => "08:00",
            'time_label' => "08:00 ص",
            'type' => "ayah",
            'type_label' => "آية",
            'text' => "﴿وَقُل رَّبِّ زِدْنِي عِلْمًا﴾\n\nدعاء قرآني جامع لطلب العلم النافع والزيادة من فضله سبحانه.\n\n📖 سورة طه: [رابط السورة]",
            'source_type' => "surah",
            'source_ref' => 20,
            'link_placeholders' => [
                "[رابط السورة]",
            ],
        ],
        [
            'day' => 0,
            'time' => "13:00",
            'time_label' => "01:00 م",
            'type' => "dhikr",
            'type_label' => "ذكر",
            'text' => "سبحان الله وبحمده، سبحان الله العظيم 🌿\n\nذكرٌ يسير على اللسان، عظيم في الميزان.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 0,
            'time' => "20:30",
            'time_label' => "08:30 م",
            'type' => "dua",
            'type_label' => "دعاء",
            'text' => "اللهم اغفر لصاحب هذه الصدقة الجارية وارحمه، واجعل القرآن نورًا له ورفعةً في درجاته.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 1,
            'time' => "08:00",
            'time_label' => "08:00 ص",
            'type' => "ayah",
            'type_label' => "آية",
            'text' => "﴿إِنَّ مَعَ الْعُسْرِ يُسْرًا﴾\n\nمهما اشتد الأمر، فرحمة الله أقرب وأوسع.\n\n📖 سورة الشرح: [رابط السورة]",
            'source_type' => "surah",
            'source_ref' => 94,
            'link_placeholders' => [
                "[رابط السورة]",
            ],
        ],
        [
            'day' => 1,
            'time' => "17:00",
            'time_label' => "05:00 م",
            'type' => "site",
            'type_label' => "من الموقع",
            'text' => "حصّن مساءك بذكر الله 🌙\n\nأذكار المساء الصحيحة مع عداد يساعدك على إتمام وردك:\n[رابط أذكار المساء]",
            'source_type' => "adhkar",
            'source_ref' => "evening",
            'link_placeholders' => [
                "[رابط أذكار المساء]",
            ],
        ],
        [
            'day' => 1,
            'time' => "21:00",
            'time_label' => "09:00 م",
            'type' => "dua",
            'type_label' => "دعاء",
            'text' => "اللهم اجعل هذا الحساب بابًا للأجر الذي لا ينقطع، وارحم صاحبه رحمةً واسعة.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 2,
            'time' => "08:00",
            'time_label' => "08:00 ص",
            'type' => "ayah",
            'type_label' => "آية",
            'text' => "﴿فَاذْكُرُونِي أَذْكُرْكُمْ﴾\n\nابدأ يومك بذكر الله؛ ففي الذكر طمأنينة القلوب.\n\n📖 سورة البقرة: [رابط السورة]",
            'source_type' => "surah",
            'source_ref' => 2,
            'link_placeholders' => [
                "[رابط السورة]",
            ],
        ],
        [
            'day' => 2,
            'time' => "13:00",
            'time_label' => "01:00 م",
            'type' => "dhikr",
            'type_label' => "ذكر",
            'text' => "لا حول ولا قوة إلا بالله.\n\nكلمة استعانة وتفويض، يطمئن بها القلب ويقوى بها العبد.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 2,
            'time' => "20:30",
            'time_label' => "08:30 م",
            'type' => "dua",
            'type_label' => "دعاء",
            'text' => "اللهم آنس وحشة صاحب هذه الصدقة الجارية، ونوّر قبره، واجمعه بمن يحب في جنات النعيم.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 3,
            'time' => "08:00",
            'time_label' => "08:00 ص",
            'type' => "ayah",
            'type_label' => "آية",
            'text' => "﴿أَلَا بِذِكْرِ اللَّهِ تَطْمَئِنُّ الْقُلُوبُ﴾\n\n📖 سورة الرعد: [رابط السورة]",
            'source_type' => "surah",
            'source_ref' => 13,
            'link_placeholders' => [
                "[رابط السورة]",
            ],
        ],
        [
            'day' => 3,
            'time' => "17:00",
            'time_label' => "05:00 م",
            'type' => "site",
            'type_label' => "من الموقع",
            'text' => "اقرأ ما تيسر من كتاب الله، واستمع لتلاوة خاشعة من قارئك المفضل.\n\n📖 القرآن الكريم: [رابط الموقع]",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [
                "[رابط الموقع]",
            ],
        ],
        [
            'day' => 3,
            'time' => "21:00",
            'time_label' => "09:00 م",
            'type' => "dua",
            'type_label' => "دعاء",
            'text' => "ربنا آتنا في الدنيا حسنة، وفي الآخرة حسنة، وقنا عذاب النار.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 4,
            'time' => "08:00",
            'time_label' => "08:00 ص",
            'type' => "ayah",
            'type_label' => "آية",
            'text' => "﴿وَمَا تَوْفِيقِي إِلَّا بِاللَّهِ﴾\n\nكل توفيق من الله، فاستعن به وتوكل عليه.\n\n📖 سورة هود: [رابط السورة]",
            'source_type' => "surah",
            'source_ref' => 11,
            'link_placeholders' => [
                "[رابط السورة]",
            ],
        ],
        [
            'day' => 4,
            'time' => "17:00",
            'time_label' => "05:00 م",
            'type' => "dhikr",
            'type_label' => "ذكر",
            'text' => "أستغفر الله العظيم وأتوب إليه 🌿",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 4,
            'time' => "21:00",
            'time_label' => "09:00 م",
            'type' => "dua",
            'type_label' => "دعاء",
            'text' => "اللهم بلّغنا الجمعة بقلوب مطمئنة، وأعمال مقبولة، وذنوب مغفورة.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 5,
            'time' => "08:00",
            'time_label' => "08:00 ص",
            'type' => "friday",
            'type_label' => "الجمعة",
            'text' => "جمعة مباركة 🤍\n\nأكثروا من الصلاة والسلام على رسول الله ﷺ.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 5,
            'time' => "10:00",
            'time_label' => "10:00 ص",
            'type' => "kahf",
            'type_label' => "سورة الكهف",
            'text' => "نورٌ ما بين الجمعتين 🌿\n\nلا تنسَ قراءة سورة الكهف وتدبر آياتها.\n\n📖 سورة الكهف: [رابط السورة]",
            'source_type' => "surah",
            'source_ref' => 18,
            'link_placeholders' => [
                "[رابط السورة]",
            ],
        ],
        [
            'day' => 5,
            'time' => "16:30",
            'time_label' => "04:30 م",
            'type' => "friday",
            'type_label' => "الجمعة",
            'text' => "في يوم الجمعة ساعة لا يوافقها عبد مسلم يسأل الله خيرًا إلا أعطاه؛ فالتمسها وأكثر من الدعاء.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
        [
            'day' => 6,
            'time' => "08:30",
            'time_label' => "08:30 ص",
            'type' => "ayah",
            'type_label' => "آية",
            'text' => "﴿وَهُوَ مَعَكُمْ أَيْنَ مَا كُنتُمْ﴾\n\nمعية الله سكينةٌ للقلب وأمان.\n\n📖 سورة الحديد: [رابط السورة]",
            'source_type' => "surah",
            'source_ref' => 57,
            'link_placeholders' => [
                "[رابط السورة]",
            ],
        ],
        [
            'day' => 6,
            'time' => "13:00",
            'time_label' => "01:00 م",
            'type' => "site",
            'type_label' => "من الموقع",
            'text' => "تعرّف على معاني الآيات من التفاسير الميسرة والموثوقة.\n\n📚 التفاسير: [رابط التفاسير]",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [
                "[رابط التفاسير]",
            ],
        ],
        [
            'day' => 6,
            'time' => "20:30",
            'time_label' => "08:30 م",
            'type' => "dua",
            'type_label' => "دعاء",
            'text' => "اللهم اختم لنا أسبوعنا بعفوك ورضاك، وافتح لنا القادم بالخير والبركة.",
            'source_type' => "none",
            'source_ref' => null,
            'link_placeholders' => [],
        ],
    ];
}

/**
 * The same template shaped the way the studio page renders it: one entry per
 * day holding the day name, a count label, and that day's posts as
 * [time label, type label, text].
 */
function qfa_x_studio_plan_view_days(): array {
    $names = qfa_x_studio_plan_day_names();
    $days = [];

    foreach ($names as $index => $name) {
        $days[$index] = [$name, '', []];
    }

    foreach (qfa_x_studio_plan_posts() as $post) {
        $days[$post['day']][2][] = [$post['time_label'], $post['type_label'], $post['text']];
    }

    foreach ($days as $index => $day) {
        $days[$index][1] = count($day[2]) . ' تغريدات';
    }

    return $days;
}
