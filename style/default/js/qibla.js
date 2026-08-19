(function(){
  'use strict';
  var root = document.querySelector('.qibla-page');
  if (!root) return;

  var KAABA_LAT = 21.4225;
  var KAABA_LON = 39.8262;
  var locateButton = root.querySelector('[data-locate]');
  var orientationButton = root.querySelector('[data-orientation]');
  var needle = root.querySelector('[data-needle]');
  var status = root.querySelector('[data-status]');
  var help = root.querySelector('[data-help]');
  var bearingEl = root.querySelector('[data-bearing]');
  var directionEl = root.querySelector('[data-direction]');
  var accuracyEl = root.querySelector('[data-accuracy]');
  var qiblaBearing = null;
  var orientationActive = false;

  function toRad(value){ return value * Math.PI / 180; }
  function toDeg(value){ return value * 180 / Math.PI; }
  function normalize(value){ return (value + 360) % 360; }
  function calculateBearing(lat, lon){
    var phi1 = toRad(lat);
    var phi2 = toRad(KAABA_LAT);
    var delta = toRad(KAABA_LON - lon);
    var y = Math.sin(delta) * Math.cos(phi2);
    var x = Math.cos(phi1) * Math.sin(phi2) - Math.sin(phi1) * Math.cos(phi2) * Math.cos(delta);
    return normalize(toDeg(Math.atan2(y, x)));
  }
  function directionName(bearing){
    var names = ['الشمال','شمال شرقي','الشرق','جنوب شرقي','الجنوب','جنوب غربي','الغرب','شمال غربي'];
    return names[Math.round(bearing / 45) % 8];
  }
  function rotateNeedle(heading){
    if (qiblaBearing === null) return;
    needle.style.transform = 'rotate(' + normalize(qiblaBearing - (heading || 0)) + 'deg)';
  }
  function handleOrientation(event){
    var heading = typeof event.webkitCompassHeading === 'number' ? event.webkitCompassHeading : (typeof event.alpha === 'number' ? normalize(360 - event.alpha) : null);
    if (heading === null) return;
    orientationActive = true;
    rotateNeedle(heading);
    status.textContent = 'حرّك الهاتف حتى يشير رمز الكعبة إلى أعلى الشاشة';
    help.textContent = 'السهم يتغير مع حركة الهاتف ليقودك إلى اتجاه القبلة.';
  }
  function enableOrientation(){
    if (!window.DeviceOrientationEvent) return;
    window.addEventListener('deviceorientationabsolute', handleOrientation, true);
    window.addEventListener('deviceorientation', handleOrientation, true);
  }
  function requestOrientation(){
    if (!window.DeviceOrientationEvent) return;
    if (typeof window.DeviceOrientationEvent.requestPermission === 'function'){
      window.DeviceOrientationEvent.requestPermission().then(function(result){
        if (result === 'granted'){
          enableOrientation();
          orientationButton.hidden = true;
        }else{
          status.textContent = 'تم تحديد اتجاه القبلة دون بوصلة الحركة';
          help.textContent = 'استخدم زاوية القبلة والاتجاه التقريبي الظاهرين أدناه.';
        }
      }).catch(function(){
        help.textContent = 'تعذر تشغيل بوصلة الحركة؛ يمكنك الاعتماد على الزاوية الظاهرة.';
      });
    }else{
      enableOrientation();
      orientationButton.hidden = true;
    }
  }
  function locationSuccess(position){
    qiblaBearing = calculateBearing(position.coords.latitude, position.coords.longitude);
    var rounded = Math.round(qiblaBearing);
    bearingEl.textContent = String(rounded);
    directionEl.textContent = directionName(qiblaBearing);
    accuracyEl.textContent = position.coords.accuracy ? '±' + Math.round(position.coords.accuracy) + ' م' : 'متاحة';
    rotateNeedle(0);
    status.textContent = 'تم تحديد اتجاه القبلة';
    help.textContent = 'الزاوية محسوبة من موقعك باتجاه الكعبة المشرفة.';
    locateButton.disabled = false;
    locateButton.querySelector('span').textContent = 'تحديث موقعي';
    if (window.DeviceOrientationEvent){
      if (typeof window.DeviceOrientationEvent.requestPermission === 'function'){
        orientationButton.hidden = false;
      }else if (!orientationActive){
        enableOrientation();
      }
    }
  }
  function locationError(error){
    locateButton.disabled = false;
    locateButton.querySelector('span').textContent = 'حاول مرة أخرى';
    if (error && error.code === 1){
      status.textContent = 'لم يتم السماح باستخدام الموقع';
      help.textContent = 'اسمح للموقع بالوصول إلى موقعك من إعدادات Safari، ثم حاول مرة أخرى.';
    }else{
      status.textContent = 'تعذر تحديد موقعك الآن';
      help.textContent = 'تأكد من تشغيل خدمات الموقع واتصال الإنترنت، ثم أعد المحاولة.';
    }
  }
  function locate(){
    if (!navigator.geolocation){
      status.textContent = 'خدمة الموقع غير متاحة في هذا المتصفح';
      help.textContent = 'جرّب فتح الصفحة في Safari أو متصفح حديث يدعم تحديد الموقع.';
      return;
    }
    locateButton.disabled = true;
    locateButton.querySelector('span').textContent = 'جارٍ تحديد الموقع…';
    status.textContent = 'نبحث عن موقعك';
    help.textContent = 'وافق على طلب الموقع عند ظهوره في المتصفح.';
    navigator.geolocation.getCurrentPosition(locationSuccess, locationError, {enableHighAccuracy:true,timeout:15000,maximumAge:60000});
  }

  locateButton.addEventListener('click', locate);
  orientationButton.addEventListener('click', requestOrientation);
})();
