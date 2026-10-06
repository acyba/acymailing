const acym_helperDelay = {
    units: {
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60,
        second: 1
    },
    setDelayFieldsGlobal: function () {
        jQuery(document).on('change', '.acym__delay__value, .acym__delay__type', function () {
            const $hidden = jQuery(this).nextAll('.acym__delay__hidden').first();
            if ($hidden.length === 0) return;

            const unit = acym_helperDelay.units[acym_helperDelay.getField($hidden, 'type').val()] || 1;
            $hidden.val(acym_helperDelay.getField($hidden, 'value').val() * unit).trigger('change');
        });
    },
    setDelayValue: function ($hidden, value) {
        const $type = acym_helperDelay.getField($hidden, 'type');
        const availableUnits = $type.find('option').map(function () {
            return this.value;
        }).get();

        let unitToDisplay = availableUnits[0];
        let valueToDisplay = value;

        for (const [unit, seconds] of Object.entries(acym_helperDelay.units)) {
            if (!availableUnits.includes(unit) || value < seconds || value % seconds !== 0) continue;

            unitToDisplay = unit;
            valueToDisplay = value / seconds;
            break;
        }

        acym_helperDelay.getField($hidden, 'value').val(valueToDisplay);
        $type.val(unitToDisplay).trigger('change.select2');
        $hidden.val(value);
    },
    getField: function ($hidden, field) {
        return $hidden.prevAll('.acym__delay__' + field).first();
    }
};
