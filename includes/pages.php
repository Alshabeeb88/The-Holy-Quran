<?php
/*
 * Main public pages the administrator can switch on or off from the control
 * panel (admin-pages.php). Disabled page keys are stored in config.php as
 * DISABLED_PAGES. The home page itself can never be disabled.
 *
 * Each page lists:
 *   label    shown in the control panel;
 *   hint     what switching it off affects;
 *   actions  the index.php actions that belong to it;
 *   links    href fragments (regex) that point at it, used to drop its links
 *            from menus, the footer and the home page while it is off.
 */

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(403);
    exit;
}

function qfa_pages(): array {
    return [
        'quran' => [
            'label' => 'القرآن الكريم',
            'hint' => 'فهرس السور وصفحات قراءة السور والاستماع لها.',
            'actions' => ['quran', 'surah'],
            'links' => ['quran\.(?:php|html)', '#quran', '/surah-\d+\.html', '/view-[^"]*\.html', '/reader-[^"]*\.html', '[?&]action=quran(?=&|$)', '(?:/|index\.php)\?surah='],
        ],
        'tafseer' => [
            'label' => 'التفاسير',
            'hint' => 'صفحات التفاسير الستة وتفسير الآيات.',
            'actions' => ['tafseer'],
            'links' => ['tafseer\.php', '#tafseer', '/tafseer-[^"]*\.html', '/t-\d+-\d+-\d+\.html', '[?&]action=tafseer(?=&|$)'],
        ],
        'translations' => [
            'label' => 'القرآن بلغات العالم',
            'hint' => 'قائمة اللغات وصفحات ترجمة معاني السور.',
            'actions' => ['translate', 'languages', 'language'],
            'links' => ['#languages', '/languages?\.html', '/language-[^"]*\.html', '/translate-[^"]*\.html', '/s-[a-z_]+-\d+-\d+\.html', '[?&]action=(?:translate|languages?)(?=&|$)'],
        ],
        'books' => [
            'label' => 'الكتب',
            'hint' => 'مكتبة الكتب الإسلامية بكل لغاتها وتصنيفاتها.',
            'actions' => ['books', 'books_language', 'books_category', 'book'],
            'links' => ['books\.(?:php|html)', '/books-in-[^"]*\.html', '/books-category-[^"]*\.html', '/book-\d+\.html', '[?&]action=(?:books|books_language|books_category|book)(?=&|$)'],
        ],
        'qibla' => [
            'label' => 'اتجاه القبلة',
            'hint' => 'صفحة تحديد اتجاه القبلة.',
            'actions' => ['qibla'],
            'links' => ['qibla\.(?:php|html)', '[?&]action=qibla(?=&|$)'],
        ],
        'tasbeeh' => [
            'label' => 'المسبحة الرقمية',
            'hint' => 'صفحة عداد التسبيح.',
            'actions' => ['tasbeeh'],
            'links' => ['tasbeeh\.(?:php|html)', '[?&]action=tasbeeh(?=&|$)'],
        ],
        'morning_adhkar' => [
            'label' => 'أذكار الصباح',
            'hint' => 'صفحة أذكار الصباح.',
            'actions' => ['morning_adhkar'],
            'links' => ['morning-adhkar\.(?:php|html)', '[?&]action=morning_adhkar(?=&|$)'],
        ],
        'evening_adhkar' => [
            'label' => 'أذكار المساء',
            'hint' => 'صفحة أذكار المساء.',
            'actions' => ['evening_adhkar'],
            'links' => ['evening-adhkar\.(?:php|html)', '[?&]action=evening_adhkar(?=&|$)'],
        ],
        'date_converter' => [
            'label' => 'محول التاريخ',
            'hint' => 'صفحة التحويل بين التاريخ الهجري والميلادي.',
            'actions' => ['date_converter'],
            'links' => ['date-converter\.(?:php|html)', '[?&]action=date_converter(?=&|$)'],
        ],
        'contact' => [
            'label' => 'تواصل معنا',
            'hint' => 'نموذج التواصل واستقبال الرسائل.',
            'actions' => ['contact'],
            'links' => ['contact\.(?:php|html)', '[?&]action=contact(?=&|$)'],
        ],
    ];
}

/** Page keys currently switched off, limited to known pages. */
function qfa_disabled_pages(): array {
    $stored = defined('DISABLED_PAGES') && is_array(DISABLED_PAGES) ? DISABLED_PAGES : [];
    return array_values(array_intersect(array_keys(qfa_pages()), array_map('strval', $stored)));
}

function qfa_page_enabled(string $key): bool {
    return !in_array($key, qfa_disabled_pages(), true);
}

/** The page key an index.php action belongs to, or '' for always-on actions. */
function qfa_page_for_action(string $action): string {
    foreach (qfa_pages() as $key => $page) {
        if (in_array($action, $page['actions'], true)) {
            return $key;
        }
    }
    return '';
}

/*
 * Drop links to disabled pages from rendered HTML.
 *
 * 'remove' is for the theme's header and footer: the link goes, then any menu
 * item or menu group left empty by that. 'unwrap' is for page content: the
 * link is taken off but what it wraps stays, so Quran text, which is wrapped
 * in links to its tafseer, is never lost. Any regex failure returns the HTML
 * unchanged.
 */
function qfa_strip_disabled_links(string $html, string $mode = 'remove'): string {
    $disabled = qfa_disabled_pages();
    if ($disabled === [] || strpos($html, '<a') === false) {
        return $html;
    }

    $pages = qfa_pages();
    $fragments = [];
    foreach ($disabled as $key) {
        foreach ($pages[$key]['links'] as $fragment) {
            $fragments[] = $fragment;
        }
    }
    $href = 'href="(?:[^"]*?)(?:' . implode('|', $fragments) . ')[^"]*"';
    // `$` inside a fragment means the end of the href value.
    $href = str_replace('(?=&|$)', '(?=[&"#])', $href);
    // Link text that never runs into another link, so broken markup elsewhere
    // cannot make one match swallow the rest of the page.
    $inner = '((?:(?!<a\b|</a>).)*)';

    if ($mode === 'unwrap') {
        $out = preg_replace('~<a\b[^>]*' . $href . '[^>]*>' . $inner . '</a>~su', '$1', $html);
        return $out === null ? $html : $out;
    }

    $steps = [
        '~<li class="nav-item"><a\b[^>]*' . $href . '[^>]*>' . $inner . '</a></li>~su',
        '~<a\b[^>]*' . $href . '[^>]*>' . $inner . '</a>~su',
        // Menu groups whose links are all gone.
        '~<li class="nav-item nav-dropdown-item"><details class="nav-dropdown"><summary[^>]*>(?:(?!</summary>).)*</summary><div class="nav-dropdown-menu[^"]*">\s*</div></details></li>~su',
        '~<details class="mobile-nav-group">\s*<summary>(?:(?!</summary>).)*</summary>\s*<div>\s*</div>\s*</details>~su',
    ];
    $out = $html;
    foreach ($steps as $pattern) {
        $out = preg_replace($pattern, '', $out);
        if ($out === null) {
            return $html;
        }
    }
    return $out;
}
