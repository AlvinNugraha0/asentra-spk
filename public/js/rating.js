// ASENTRA SPK — rating option selected state
(function () {
    'use strict';

    document.querySelectorAll('.rating-option input[type="radio"]').forEach(function (input) {
        input.addEventListener('change', function () {
            const name = input.name;
            document.querySelectorAll('input[name="' + name + '"]').forEach(function (radio) {
                radio.closest('.rating-option').classList.remove('selected');
            });
            input.closest('.rating-option').classList.add('selected');
        });
    });
})();
