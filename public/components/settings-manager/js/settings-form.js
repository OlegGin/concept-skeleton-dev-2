(function () {
    const typeSelect = document.querySelector('.js-setting-data-type');
    const hiddenInput = document.getElementById('setting_value');
    const form = typeSelect?.closest('form');

    if (!typeSelect || !hiddenInput || !form) {
        return;
    }

    const panels = {
        string: document.querySelector('.js-setting-value-string'),
        text: document.querySelector('.js-setting-value-text'),
        number: document.querySelector('.js-setting-value-number'),
        bool: document.querySelector('.js-setting-value-bool'),
        json: document.querySelector('.js-setting-value-json'),
    };

    const inputs = {
        string: document.getElementById('setting_value_string'),
        text: document.getElementById('setting_value_text'),
        int: document.getElementById('setting_value_number'),
        float: document.getElementById('setting_value_number'),
        bool: document.getElementById('setting_value_bool'),
        json: document.getElementById('setting_value_json'),
    };

    const panelForType = {
        string: 'string',
        text: 'text',
        int: 'number',
        float: 'number',
        bool: 'bool',
        json: 'json',
    };

    function activeType() {
        return typeSelect.value;
    }

    function visibleInput(type) {
        return inputs[type] ?? inputs.string;
    }

    function syncHiddenValue() {
        const input = visibleInput(activeType());
        hiddenInput.value = input ? input.value : '';
    }

    function togglePanels() {
        const activePanel = panelForType[activeType()] ?? 'string';

        Object.entries(panels).forEach(([key, panel]) => {
            if (!panel) {
                return;
            }

            panel.classList.toggle('d-none', key !== activePanel);
        });

        syncHiddenValue();
    }

    typeSelect.addEventListener('change', togglePanels);

    form.querySelectorAll('.js-setting-value-input').forEach((input) => {
        input.addEventListener('input', syncHiddenValue);
        input.addEventListener('change', syncHiddenValue);
    });

    form.addEventListener('submit', syncHiddenValue);

    togglePanels();
})();
