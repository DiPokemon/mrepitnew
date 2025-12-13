(function($){

  function digitsOnly(s) {
    return (s || '').toString().replace(/\D+/g, '');
  }

  // Приводим к E.164 для РФ: +7XXXXXXXXXX
  function toE164ru(rawDigits) {
    let d = digitsOnly(rawDigits);

    // если ввели 10 цифр (без кода) — добавим 7
    if (d.length === 10) d = '7' + d;

    // если ввели 8XXXXXXXXXX — заменим на 7XXXXXXXXXX
    if (d.length === 11 && d[0] === '8') d = '7' + d.slice(1);

    // если уже 7XXXXXXXXXX — ок
    if (d.length === 11 && d[0] === '7') return '+' + d;

    // иначе оставим как есть (на случай других стран)
    return d ? ('+' + d) : '';
  }

  function initMask($scope) {
    const input = $scope.find('input[type="tel"], input[name="form_fields[phone]"], #form-field-phone').get(0);
    if (!input) return;

    // чтобы не инициализировать повторно
    if (input.dataset.itsMaskInit === '1') return;
    input.dataset.itsMaskInit = '1';

    // полезно для мобильной клавиатуры
    input.setAttribute('inputmode', 'tel');
    input.setAttribute('autocomplete', 'tel');
    input.removeAttribute('pattern');
    input.removeAttribute('title');

    // создадим/найдём скрытое поле под "чистый" телефон (цифры / e164)
    // Рекомендую добавить в Elementor отдельное Hidden поле с ID: phone_e164 (или phone_digits)
    // Но если не добавишь — мы создадим его сами рядом с input.
    let hidden = $scope.find('input[name="form_fields[phone_e164]"]').get(0);
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'form_fields[phone_e164]';
      input.closest('.elementor-field-group')?.appendChild(hidden);
    }

    const mask = IMask(input, {
      mask: '+{7} (000) 000-00-00',
      lazy: true, // показывает шаблон сразу
      placeholderChar: '_',
    });
      
      // Elementor tel validation не любит пробелы — убираем их перед отправкой формы
        const form = input.closest('form');
        if (form && !form.dataset.itsPhoneSubmitBind) {
        form.dataset.itsPhoneSubmitBind = '1';

        form.addEventListener('submit', function () {
            // убрать пробелы и подчёркивания (если lazy:false)
            input.value = (input.value || '').replace(/\s+/g, '').replace(/_+/g, '');

            // на всякий случай ещё раз синхронизируем hidden phone_e164
            // (если у тебя есть функция sync() — просто вызови её)
        }, true);
        }


    // обновляем hidden при каждом вводе
    const sync = () => {
      const e164 = toE164ru(mask.unmaskedValue); // unmaskedValue = только цифры (без +, скобок и дефисов)
      hidden.value = e164; // например +71234567890
    };

    mask.on('accept', sync);
    sync();
  }

  // 1) на обычной загрузке
  $(document).ready(function(){
    initMask($(document));
  });

  // 2) на динамической подгрузке Elementor (попапы/виджеты/шаблоны)
  $(window).on('elementor/frontend/init', function () {
    if (!window.elementorFrontend || !elementorFrontend.hooks) return;

    elementorFrontend.hooks.addAction('frontend/element_ready/form.default', function($scope){
      initMask($scope);
    });
  });

})(jQuery);
