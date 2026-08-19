(function () {
  'use strict';
  const names = {Fajr:'الفجر',Sunrise:'الشروق',Dhuhr:'الظهر',Asr:'العصر',Maghrib:'المغرب',Isha:'العشاء'};
  const prayerOrder = ['Fajr','Dhuhr','Asr','Maghrib','Isha'];
  let currentTimings = null;

  const cleanTime = v => (v || '--:--').split(' ')[0].substring(0,5);
  const toMinutes = t => { const p = cleanTime(t).split(':').map(Number); return p[0]*60+p[1]; };

  function render(data, label) {
    if (!data || !data.timings) return;
    currentTimings = data.timings;
    document.querySelectorAll('[data-prayer]').forEach(el => { el.textContent = cleanTime(data.timings[el.dataset.prayer]); });
    if (data.date) {
      document.getElementById('gregorian-date').textContent = data.date.readable || '';
      const h = data.date.hijri;
      if (h) document.getElementById('hijri-date').textContent = `${h.day} ${h.month.ar || h.month.en} ${h.year} هـ`;
    }
    if (label) document.getElementById('prayer-location').textContent = label;
    updateNext();
  }

  function updateNext() {
    if (!currentTimings) return;
    const now = new Date();
    const current = now.getHours()*60 + now.getMinutes() + now.getSeconds()/60;
    let nextKey = null, target = null;
    for (const key of prayerOrder) {
      const mins = toMinutes(currentTimings[key]);
      if (mins > current) { nextKey = key; target = mins; break; }
    }
    if (!nextKey) { nextKey = 'Fajr'; target = 24*60 + toMinutes(currentTimings.Fajr); }
    const diff = Math.max(0, Math.floor((target-current)*60));
    const hh = String(Math.floor(diff/3600)).padStart(2,'0');
    const mm = String(Math.floor((diff%3600)/60)).padStart(2,'0');
    const ss = String(diff%60).padStart(2,'0');
    document.getElementById('next-prayer-name').textContent = names[nextKey];
    document.getElementById('next-prayer-countdown').textContent = `${hh}:${mm}:${ss}`;
    document.querySelectorAll('.prayer-times > div').forEach(d => d.classList.remove('is-next'));
    const el = document.querySelector(`[data-prayer="${nextKey}"]`);
    if (el) el.parentElement.classList.add('is-next');
  }

  async function fetchCity(city, country) {
    const status = document.getElementById('prayer-status');
    status.textContent = 'جاري تحديث المواقيت…';
    try {
      const url = `https://api.aladhan.com/v1/timingsByCity?city=${encodeURIComponent(city)}&country=${encodeURIComponent(country)}&method=4`;
      const r = await fetch(url); const j = await r.json();
      if (j.code !== 200) throw new Error('API');
      render(j.data, `${city}، ${country}`);
      status.textContent = 'طريقة الحساب: أم القرى - مكة';
      localStorage.setItem('qfa_prayer_place', JSON.stringify({city,country}));
    } catch(e) { status.textContent = 'تعذر تحديث المواقيت. تحقق من المدينة والدولة.'; }
  }

  async function fetchCoords(lat, lon) {
    const status = document.getElementById('prayer-status');
    status.textContent = 'جاري تحديد مواقيت موقعك…';
    try {
      const r = await fetch(`https://api.aladhan.com/v1/timings?latitude=${lat}&longitude=${lon}&method=4`); const j = await r.json();
      if (j.code !== 200) throw new Error('API');
      render(j.data, 'موقعك الحالي');
      status.textContent = 'تم تحديد المواقيت حسب موقعك • طريقة أم القرى';
    } catch(e) { status.textContent = 'تعذر جلب المواقيت حسب الموقع.'; }
  }


  function setTheme(theme) {
    const normalized = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', normalized);
    document.documentElement.style.setProperty('--breadcrumb-inline-color', normalized === 'dark' ? '#f1f5f3' : '#0b6555');
    document.documentElement.style.setProperty('--breadcrumb-active-inline-color', normalized === 'dark' ? '#b7c7c1' : '#66756f');
    try { localStorage.setItem('qfa_theme', normalized); } catch(e) {}
    const btn = document.getElementById('theme-toggle');
    if (btn) {
      const dark = normalized === 'dark';
      btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
      btn.setAttribute('aria-label', dark ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن');
      const label = btn.querySelector('.theme-toggle-label');
      if (label) label.textContent = dark ? 'الوضع الفاتح' : 'الوضع الداكن';
    }
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', normalized === 'dark' ? '#101a17' : '#0c4f42');
  }

  function initThemeToggle() {
    let current = document.documentElement.getAttribute('data-theme') || 'light';
    setTheme(current);
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
      current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      setTheme(current);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initThemeToggle();
    const form = document.getElementById('prayer-search');
    if (!form) return;
    try {
      const saved = JSON.parse(localStorage.getItem('qfa_prayer_place') || 'null');
      if (saved) { document.getElementById('prayer-city').value=saved.city; document.getElementById('prayer-country').value=saved.country; fetchCity(saved.city,saved.country); }
      else fetchCity('Riyadh','Saudi Arabia');
    } catch(e) { fetchCity('Riyadh','Saudi Arabia'); }
    form.addEventListener('submit', e => { e.preventDefault(); fetchCity(document.getElementById('prayer-city').value.trim(),document.getElementById('prayer-country').value.trim()); });
    document.getElementById('use-location').addEventListener('click', () => {
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition(p => fetchCoords(p.coords.latitude,p.coords.longitude), () => { document.getElementById('prayer-status').textContent='لم يتم السماح بالوصول للموقع.'; }, {enableHighAccuracy:false,timeout:10000});
    });
    setInterval(updateNext, 1000);
  });
})();

/* V40.2 - Compact the two sticky mobile bars after scrolling. */
(function () {
  'use strict';
  var compact = false;
  function updateMobileBars() {
    var next = window.innerWidth <= 991 && window.scrollY > 60;
    if (next === compact) return;
    compact = next;
    document.documentElement.classList.toggle('qfa-mobile-compact', compact);
  }
  window.addEventListener('scroll', updateMobileBars, { passive: true });
  window.addEventListener('resize', updateMobileBars, { passive: true });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', updateMobileBars); else updateMobileBars();
})();

/* V39 - Fast in-page search for Quran, tafseer, languages and books. */
(function () {
  'use strict';
  function normalize(value) {
    return (value || '').toString().toLowerCase().replace(/[\u064B-\u065F\u0670]/g, '')
      .replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي')
      .replace(/[٠-٩]/g, function (n) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(n); }).trim();
  }
  function searchBox(placeholder, target) {
    if (!target || target.dataset.qfaSearchReady === '1') return;
    var items = Array.prototype.slice.call(target.children).filter(function (item) { return item.matches('[class*="col-"]'); });
    if (!items.length) return;
    target.dataset.qfaSearchReady = '1';
    var box = document.createElement('div');
    box.className = 'qfa-section-search';
    box.innerHTML = '<i class="fas fa-search" aria-hidden="true"></i><input type="search" autocomplete="off" inputmode="search" aria-label="' + placeholder + '" placeholder="' + placeholder + '"><button type="button" aria-label="مسح البحث" hidden><i class="fas fa-times" aria-hidden="true"></i></button><span class="qfa-search-count" aria-live="polite"></span>';
    target.parentNode.insertBefore(box, target);
    var input = box.querySelector('input'), clear = box.querySelector('button'), count = box.querySelector('.qfa-search-count');
    function filter() {
      var term = normalize(input.value), visible = 0;
      items.forEach(function (item) { var show = !term || normalize(item.textContent).indexOf(term) !== -1; item.hidden = !show; if (show) visible++; });
      clear.hidden = !term;
      count.textContent = term ? (visible ? visible + ' نتيجة' : 'لا توجد نتائج') : '';
      box.classList.toggle('has-no-results', !!term && visible === 0);
    }
    input.addEventListener('input', filter);
    clear.addEventListener('click', function () { input.value = ''; filter(); input.focus(); });
  }
  function firstGrid(scope) {
    if (!scope) return null;
    var grids = scope.querySelectorAll('.row');
    for (var i = 0; i < grids.length; i++) {
      for (var j = 0; j < grids[i].children.length; j++) {
        if (grids[i].children[j].matches('[class*="col-"]')) return grids[i];
      }
    }
    return null;
  }
  function gridAfter(element) {
    if (!element || !element.parentNode) return null;
    var grids = element.parentNode.querySelectorAll('.row');
    for (var i = 0; i < grids.length; i++) {
      for (var j = 0; j < grids[i].children.length; j++) {
        if (grids[i].children[j].matches('[class*="col-"]')) return grids[i];
      }
    }
    return null;
  }
  function initSectionSearch() {
    searchBox('ابحث باسم السورة أو رقمها', firstGrid(document.getElementById('quran')));
    searchBox('ابحث عن سورة في التفاسير', firstGrid(document.getElementById('tafseer')));
    searchBox('ابحث باسم اللغة', firstGrid(document.getElementById('languages')));
    searchBox('ابحث باسم السورة أو رقمها داخل هذه اللغة', document.querySelector('.translation-surah-grid'));
    var allBookGrids = document.querySelectorAll('.books > .row');
    for (var b = 0; b < allBookGrids.length; b++) searchBox('ابحث باسم الكتاب أو المؤلف', allBookGrids[b]);
    var path = location.pathname.toLowerCase(), body = document.querySelector('.internal-page .card-custom .card-body');
    if (/\/(quran\.php|quran\.html)$/.test(path)) searchBox('ابحث باسم السورة أو رقمها', firstGrid(body));
    else if (/\/tafseer(?:-|\.)/.test(path)) {
      var tafseerGrid = gridAfter(document.querySelector('.tafseer-list'));
      searchBox('ابحث عن سورة في التفسير', tafseerGrid || firstGrid(body));
    }
    else if (/\/languages?(?:\.|$)/.test(path)) searchBox('ابحث باسم اللغة', firstGrid(body));
    else if (/\/books(?:\.|-|\/)/.test(path)) {
      var booksGrid = document.querySelector('.books-languages > .row, .books > .row, .languages .books > .row');
      searchBox('ابحث باسم الكتاب أو المؤلف أو اللغة', booksGrid || firstGrid(body));
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSectionSearch); else initSectionSearch();
})();

/* V13: Hijri/Gregorian converter + contact form */
(function () {
  'use strict';

  function pad2(v){ return String(v).padStart(2,'0'); }
  function todayISO(){
    const d=new Date();
    return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
  }
  function isoToApi(iso){
    const p=(iso||'').split('-');
    if(p.length!==3) return '';
    return `${p[2]}-${p[1]}-${p[0]}`;
  }
  function monthAr(obj){ return obj && (obj.ar || obj.en) ? (obj.ar || obj.en) : ''; }
  function weekdayAr(obj){ return obj && (obj.ar || obj.en) ? (obj.ar || obj.en) : ''; }

  function setConverterLoading(loading){
    document.querySelectorAll('#date-converter button[type="submit"]').forEach(function(btn){
      if(!btn.dataset.originalText) btn.dataset.originalText=btn.innerHTML;
      btn.disabled=loading;
      btn.innerHTML=loading ? '<i class="fas fa-spinner fa-spin"></i> جاري التحويل…' : btn.dataset.originalText;
    });
  }

  function showConverterResult(main, sub, error){
    const box=document.getElementById('converter-result');
    const mainEl=document.getElementById('converter-result-main');
    const subEl=document.getElementById('converter-result-sub');
    if(!box || !mainEl || !subEl) return;
    mainEl.textContent=main;
    subEl.textContent=sub || '';
    box.classList.toggle('is-error', !!error);
  }

  async function apiConvert(endpoint){
    const r=await fetch(`https://api.aladhan.com/v1/${endpoint}`, {headers:{'Accept':'application/json'}});
    const j=await r.json();
    if(!r.ok || Number(j.code)!==200 || !j.data) throw new Error('conversion');
    return j.data;
  }

  async function convertGregorian(iso, updateHijriFields){
    const apiDate=isoToApi(iso);
    if(!apiDate) return;
    setConverterLoading(true);
    try{
      const data=await apiConvert(`gToH/${encodeURIComponent(apiDate)}`);
      const h=data.hijri || {};
      const main=`${h.day || ''} ${monthAr(h.month)} ${h.year || ''} هـ`.trim();
      const sub=[weekdayAr(h.weekday), `الموافق ${apiDate} م`].filter(Boolean).join(' • ');
      showConverterResult(main, sub, false);
      if(updateHijriFields){
        const day=document.getElementById('hijri-day');
        const month=document.getElementById('hijri-month');
        const year=document.getElementById('hijri-year');
        if(day && h.day) day.value=parseInt(h.day,10);
        if(month && h.month && h.month.number) month.value=parseInt(h.month.number,10);
        if(year && h.year) year.value=parseInt(h.year,10);
      }
    }catch(e){
      showConverterResult('تعذر تحويل التاريخ', 'تحقق من اتصال الإنترنت وحاول مرة أخرى.', true);
    }finally{ setConverterLoading(false); }
  }

  async function convertHijri(day, month, year){
    const apiDate=`${pad2(day)}-${pad2(month)}-${year}`;
    setConverterLoading(true);
    try{
      const data=await apiConvert(`hToG/${encodeURIComponent(apiDate)}`);
      const g=data.gregorian || {};
      const main=`${g.day || ''} ${monthAr(g.month)} ${g.year || ''} م`.trim();
      const sub=[weekdayAr(g.weekday), `الموافق ${apiDate} هـ`].filter(Boolean).join(' • ');
      showConverterResult(main, sub, false);
    }catch(e){
      showConverterResult('تعذر تحويل التاريخ', 'تأكد من صحة اليوم والشهر والسنة الهجرية.', true);
    }finally{ setConverterLoading(false); }
  }

  function initDateConverter(){
    const wrap=document.getElementById('date-converter');
    if(!wrap) return;
    const gForm=document.getElementById('gregorian-to-hijri-form');
    const hForm=document.getElementById('hijri-to-gregorian-form');
    const gInput=document.getElementById('gregorian-input');
    const tabs=wrap.querySelectorAll('[data-converter-tab]');
    if(gInput){ gInput.value=todayISO(); convertGregorian(gInput.value, true); }

    tabs.forEach(function(tab){
      tab.addEventListener('click', function(){
        tabs.forEach(t=>{t.classList.remove('is-active');t.setAttribute('aria-selected','false');});
        tab.classList.add('is-active'); tab.setAttribute('aria-selected','true');
        const isG=tab.dataset.converterTab==='g2h';
        if(gForm) gForm.classList.toggle('is-hidden', !isG);
        if(hForm) hForm.classList.toggle('is-hidden', isG);
      });
    });

    if(gForm) gForm.addEventListener('submit', function(e){
      e.preventDefault();
      if(gInput && gInput.value) convertGregorian(gInput.value, false);
    });
    if(hForm) hForm.addEventListener('submit', function(e){
      e.preventDefault();
      const d=parseInt(document.getElementById('hijri-day').value,10);
      const m=parseInt(document.getElementById('hijri-month').value,10);
      const y=parseInt(document.getElementById('hijri-year').value,10);
      if(d>=1 && d<=30 && m>=1 && m<=12 && y>0) convertHijri(d,m,y);
    });
  }

  function initContactForm(){
    const form=document.getElementById('contact-form');
    if(!form) return;
    const status=document.getElementById('contact-status');
    const btn=form.querySelector('button[type="submit"]');
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      if(!form.reportValidity()) return;
      const original=btn.innerHTML;
      btn.disabled=true;
      btn.innerHTML='<i class="fas fa-spinner fa-spin"></i><span>جاري الإرسال…</span>';
      status.className='contact-status';
      status.textContent='';
      try{
        const r=await fetch(form.action, {method:'POST', body:new FormData(form), headers:{'Accept':'application/json'}});
        const j=await r.json();
        if(!r.ok || !j.ok) throw new Error(j.message || 'send');
        status.className='contact-status is-success';
        status.textContent=j.message || 'تم استلام رسالتك بنجاح، شكرًا لتواصلك.';
        form.reset();
      }catch(err){
        status.className='contact-status is-error';
        status.textContent='تعذر إرسال الرسالة حاليًا. حاول مرة أخرى بعد قليل.';
      }finally{
        btn.disabled=false;
        btn.innerHTML=original;
      }
    });
  }

  function initNativeSharing(){
    document.addEventListener('click', async function(e){
      const button=e.target.closest('.native-share-button');
      if(!button) return;
      const box=button.closest('.share-content');
      if(!box) return;
      const text=box.getAttribute('data-share-text') || document.title;
      const url=box.getAttribute('data-share-url') || window.location.href;
      if(navigator.share){
        try{
          await navigator.share({title:document.title,text:text,url:url});
        }catch(err){
          if(err && err.name !== 'AbortError'){
            const x=box.querySelector('.x-share-link');
            if(x) window.open(x.href,'_blank','noopener');
          }
        }
      }else{
        const x=box.querySelector('.x-share-link');
        if(x) window.open(x.href,'_blank','noopener');
      }
    });
  }

  function initXSharing(){
    document.addEventListener('click', function(e){
      const link=e.target.closest('.x-share-link');
      if(!link) return;

      const isIOS=/iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
      if(!isIOS) return;

      e.preventDefault();
      const appUrl=link.getAttribute('data-x-app-url');
      const webUrl=link.href;
      if(!appUrl){
        window.location.href=webUrl;
        return;
      }

      let fallbackTimer;
      const cancelFallback=function(){
        if(document.hidden && fallbackTimer){
          clearTimeout(fallbackTimer);
          fallbackTimer=null;
        }
      };
      document.addEventListener('visibilitychange', cancelFallback, {once:true});
      fallbackTimer=setTimeout(function(){
        if(!document.hidden) window.location.href=webUrl;
      }, 1400);
      window.location.href=appUrl;
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
	initNativeSharing();
	initXSharing();
    initDateConverter();
    initContactForm();
  });
})();


// V19 hard breadcrumb contrast enforcement (independent from stylesheet cache)
(function(){
  function enforceBreadcrumb(){
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    var linkColor = dark ? '#f1f5f3' : '#0b6555';
    var activeColor = dark ? '#b7c7c1' : '#66756f';
    document.querySelectorAll('.custom-breadcrumb .breadcrumb a').forEach(function(a){
      a.style.setProperty('color', linkColor, 'important');
      a.querySelectorAll('span').forEach(function(sp){ sp.style.setProperty('color', linkColor, 'important'); sp.style.setProperty('opacity','1','important'); });
    });
    document.querySelectorAll('.custom-breadcrumb .breadcrumb-item.active').forEach(function(el){ el.style.setProperty('color', activeColor, 'important'); });
  }
  document.addEventListener('DOMContentLoaded', enforceBreadcrumb);
  var btn=document.getElementById('theme-toggle');
  if(btn){ btn.addEventListener('click', function(){ setTimeout(enforceBreadcrumb, 0); }); }
})();


// V21: canonical utility-page routing + breadcrumb self-healing.
(function(){
  function siteRoot(){
    var script = document.querySelector('script[src*="site-modern.js"]');
    if(script){
      try{
        var u = new URL(script.src, window.location.href);
        var marker = '/style/';
        var i = u.pathname.indexOf(marker);
        if(i !== -1){ return u.pathname.substring(0, i + 1); }
      }catch(e){}
    }
    var p = window.location.pathname;
    return p.substring(0, p.lastIndexOf('/') + 1);
  }
  function canonicalizeUtilityLinks(){
    var root = siteRoot();
    document.querySelectorAll('a[href]').forEach(function(a){
      var href = a.getAttribute('href') || '';
      if(href === '#contact-us' || /\/#contact-us$/.test(href)) a.setAttribute('href', root + 'contact.html');
      if(href === '#date-converter' || /\/#date-converter$/.test(href)) a.setAttribute('href', root + 'date-converter.html');
    });
    if(window.location.hash === '#contact-us'){
      window.location.replace(root + 'contact.html');
      return true;
    }
    if(window.location.hash === '#date-converter'){
      window.location.replace(root + 'date-converter.html');
      return true;
    }
    return false;
  }
  function healBreadcrumb(){
    var ol = document.querySelector('.custom-breadcrumb .breadcrumb');
    if(!ol) return;
    var root = siteRoot();
    var firstLink = ol.querySelector('a');
    var hasHome = Array.prototype.some.call(ol.querySelectorAll('a,span'), function(el){
      return (el.textContent || '').trim() === 'الرئيسية';
    });
    if(!hasHome){
      var li = document.createElement('li');
      li.className = 'breadcrumb-item breadcrumb-home-v21';
      var a = document.createElement('a');
      a.href = root;
      a.textContent = 'الرئيسية';
      a.className = 'breadcrumb-link-force';
      li.appendChild(a);
      ol.insertBefore(li, ol.firstChild);
    }
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    ol.querySelectorAll('a').forEach(function(a){
      a.style.setProperty('color', dark ? '#f1f5f3' : '#0b6555', 'important');
    });
  }
  document.addEventListener('DOMContentLoaded', function(){
    if(canonicalizeUtilityLinks()) return;
    healBreadcrumb();
    setTimeout(healBreadcrumb, 50);
  });
  window.addEventListener('pageshow', function(){
    if(canonicalizeUtilityLinks()) return;
    healBreadcrumb();
  });
})();


// V22 hard cleanup: standalone utility pages only.
(function(){
  function siteRoot(){
    var script = document.querySelector('script[src*="site-modern.js"]');
    if(script){
      var src = script.getAttribute('src') || '';
      var marker = 'style/default/js/site-modern.js';
      var i = src.indexOf(marker);
      if(i >= 0) return src.slice(0, i);
    }
    var path = window.location.pathname || '/';
    var m = path.match(/^(.*\/)(?:[^\/]+\.html)?$/);
    return m ? m[1] : '/';
  }
  var root = siteRoot();

  function repairLegacyRoutes(){
    document.querySelectorAll('a[href]').forEach(function(a){
      var raw = a.getAttribute('href') || '';
      if(raw === '#contact-us' || raw.endsWith('/#contact-us')) a.setAttribute('href', root + 'contact.html');
      if(raw === '#date-converter' || raw.endsWith('/#date-converter')) a.setAttribute('href', root + 'date-converter.html');
    });
    if(window.location.hash === '#contact-us') { window.location.replace(root + 'contact.html'); return true; }
    if(window.location.hash === '#date-converter') { window.location.replace(root + 'date-converter.html'); return true; }
    return false;
  }

  function removeLegacyHomeTools(){
    // The old inline tools are not part of the modern home anymore.
    var path = (window.location.pathname || '').replace(/\/+$/, '/');
    var isHome = path === root || path === root.replace(/\/$/,'') || /\/index(?:\.php|\.html)?$/.test(path);
    if(!isHome) return;
    document.querySelectorAll('.site-tools-section, #site-tools').forEach(function(el){ el.remove(); });
  }

  function ensureBreadcrumbHome(){
    document.querySelectorAll('.custom-breadcrumb .breadcrumb').forEach(function(ol){
      var hasHome = Array.from(ol.querySelectorAll('a')).some(function(a){ return (a.textContent || '').trim() === 'الرئيسية'; });
      if(!hasHome){
        var li=document.createElement('li');
        li.className='breadcrumb-item';
        var a=document.createElement('a');
        a.href=root;
        a.className='breadcrumb-link-force';
        a.textContent='الرئيسية';
        li.appendChild(a);
        ol.insertBefore(li, ol.firstChild);
      }
    });
  }

  function run(){
    if(repairLegacyRoutes()) return;
    removeLegacyHomeTools();
    ensureBreadcrumbHome();
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
  window.addEventListener('pageshow', run);
})();

/* V34 - Lite YouTube */
(function () {
  function initLiteYouTube() {
    document.querySelectorAll('.youtube-lite').forEach(function (button) {
      if (button.dataset.youtubeReady === '1') return;
      button.dataset.youtubeReady = '1';

      button.addEventListener('click', function () {
        var id = button.getAttribute('data-youtube-id');
        if (!id) return;

        var iframe = document.createElement('iframe');

        iframe.src =
          'https://www.youtube-nocookie.com/embed/' +
          encodeURIComponent(id) +
          '?autoplay=1&rel=0&modestbranding=1';

        iframe.title = 'البث المباشر للحرم المكي';
        iframe.allow =
          'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';

        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.allowFullscreen = true;

        button.replaceWith(iframe);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLiteYouTube);
  } else {
    initLiteYouTube();
  }
})();

/* V35.1 - Final mobile navbar anchor navigation fix for iOS/Safari */
(function () {
  'use strict';

  function initStableMobileAnchorNavigation() {
    var nav = document.getElementById('navbarNav');
    var stickyNav = document.querySelector('.custom-nav');
    if (!nav || !stickyNav) return;

    var links = nav.querySelectorAll('a.nav-link[href*="#"]');

    function getHash(link) {
      var raw = link.getAttribute('href') || '';
      var i = raw.indexOf('#');
      if (i < 0) return '';
      var hash = raw.slice(i);
      return /^#[A-Za-z0-9_-]+$/.test(hash) ? hash : '';
    }

    function scrollToTarget(target, hash) {
      /* Wait until Bootstrap has fully collapsed and Safari has recalculated layout. */
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          var navHeight = Math.ceil(stickyNav.getBoundingClientRect().height || 0);
          var absoluteTop = window.pageYOffset + target.getBoundingClientRect().top;
          var top = Math.max(0, Math.round(absoluteTop - navHeight - 10));

          try {
            window.history.replaceState(null, '', hash);
          } catch (e) {}

          /* Explicit coordinates avoid Safari's anchor + sticky-header jump. */
          window.scrollTo({
            top: top,
            left: 0,
            behavior: 'smooth'
          });
        });
      });
    }

    links.forEach(function (link) {
      if (link.dataset.mobileNavFinal === '1') return;
      link.dataset.mobileNavFinal = '1';

      link.addEventListener('click', function (event) {
        if (window.innerWidth >= 992) return;

        var hash = getHash(link);
        if (!hash) return;

        var target = document.querySelector(hash);
        if (!target) return;

        event.preventDefault();

        if (nav.classList.contains('show') && window.bootstrap && window.bootstrap.Collapse) {
          var collapse = window.bootstrap.Collapse.getOrCreateInstance(nav, { toggle: false });
          var finished = false;

          function finish() {
            if (finished) return;
            finished = true;
            nav.removeEventListener('hidden.bs.collapse', finish);
            scrollToTarget(target, hash);
          }

          nav.addEventListener('hidden.bs.collapse', finish, { once: true });
          collapse.hide();

          /* Fallback for older iOS/Safari if the Bootstrap event is delayed. */
          window.setTimeout(finish, 550);
        } else {
          scrollToTarget(target, hash);
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStableMobileAnchorNavigation);
  } else {
    initStableMobileAnchorNavigation();
  }
})();

/* V36 - Independent mobile navigation for iOS/Safari stability */
(function () {
  'use strict';

  function syncMobileThemeButton() {
    var btn = document.querySelector('.mobile-theme-toggle');
    if (!btn) return;
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
    btn.setAttribute('aria-label', dark ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن');
    var icon = btn.querySelector('.mobile-theme-icon');
    if (icon) icon.textContent = dark ? '☾' : '☀';
  }

  function initMobileSafeNav() {
    var nav = document.querySelector('.mobile-safe-nav');
    if (!nav) return;

    var menuBtn = nav.querySelector('.mobile-menu-toggle');
    var menu = nav.querySelector('.mobile-safe-menu');
    var themeBtn = nav.querySelector('.mobile-theme-toggle');

    if (menuBtn && menu) {
      menuBtn.addEventListener('click', function () {
        var open = menuBtn.getAttribute('aria-expanded') === 'true';
        menuBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
        menu.hidden = open;
        nav.classList.toggle('is-open', !open);
      });

      menu.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
          menuBtn.setAttribute('aria-expanded', 'false');
          menu.hidden = true;
          nav.classList.remove('is-open');
        });
      });
    }

    if (themeBtn) {
      themeBtn.addEventListener('click', function () {
        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        if (typeof setTheme === 'function') {
          setTheme(next);
        } else {
          document.documentElement.setAttribute('data-theme', next);
          try { localStorage.setItem('qfa_theme', next); } catch (e) {}
        }
        syncMobileThemeButton();
        var desktopBtn = document.getElementById('theme-toggle');
        if (desktopBtn) {
          var label = desktopBtn.querySelector('.theme-toggle-label');
          desktopBtn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
          if (label) label.textContent = next === 'dark' ? 'الوضع الفاتح' : 'الوضع الداكن';
        }
      });
    }

    syncMobileThemeButton();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileSafeNav);
  } else {
    initMobileSafeNav();
  }
window.addEventListener('pageshow', syncMobileThemeButton);
})();

/* V37 - Keep grouped navigation tidy */
(function () {
  'use strict';

  function initGroupedNavigation() {
    var desktopGroups = Array.prototype.slice.call(document.querySelectorAll('.nav-dropdown'));
    var mobileGroups = Array.prototype.slice.call(document.querySelectorAll('.mobile-nav-group'));

    desktopGroups.forEach(function (group) {
      group.addEventListener('toggle', function () {
        if (!group.open) return;
        desktopGroups.forEach(function (other) {
          if (other !== group) other.open = false;
        });
      });
    });

    mobileGroups.forEach(function (group) {
      group.addEventListener('toggle', function () {
        if (!group.open) return;
        mobileGroups.forEach(function (other) {
          if (other !== group) other.open = false;
        });
      });
    });

    document.addEventListener('click', function (event) {
      if (event.target.closest('.nav-dropdown')) return;
      desktopGroups.forEach(function (group) { group.open = false; });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      desktopGroups.forEach(function (group) { group.open = false; });
      mobileGroups.forEach(function (group) { group.open = false; });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGroupedNavigation);
  } else {
    initGroupedNavigation();
  }
})();
