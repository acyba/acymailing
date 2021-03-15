jQuery(document).ready(function ($) {
    function Init() {
        setColorPicker();
        setSubscriptionEvolutionChart();
        acym_helperSelectionMultilingual.init('list');
    }

    Init();

    function setColorPicker() {
        let $colorField = $('#acym__list__settings__color-picker');
        if (typeof $colorField.spectrum == 'function') {
            $colorField
                .spectrum({
                    showInput: true,
                    preferredFormat: 'hex'
                });
        }
    }

    function setSubscriptionEvolutionChart() {
        let dataInput = document.getElementById('acym__list__settings__stats-evol__data');
        if (dataInput === null) return;

        let data = acym_helper.parseJson(dataInput.value);
        let labels = [];
        let months = {
            1: ACYM_JS_TXT.ACYM_JANUARY,
            2: ACYM_JS_TXT.ACYM_FEBRUARY,
            3: ACYM_JS_TXT.ACYM_MARCH,
            4: ACYM_JS_TXT.ACYM_APRIL,
            5: ACYM_JS_TXT.ACYM_MAY,
            6: ACYM_JS_TXT.ACYM_JUNE,
            7: ACYM_JS_TXT.ACYM_JULY,
            8: ACYM_JS_TXT.ACYM_AUGUST,
            9: ACYM_JS_TXT.ACYM_SEPTEMBER,
            10: ACYM_JS_TXT.ACYM_OCTOBER,
            11: ACYM_JS_TXT.ACYM_NOVEMBER,
            12: ACYM_JS_TXT.ACYM_DECEMBER
        };

        let dataList = [
            [],
            []
        ];
        data.map((evolData, indexEvol) => {
            evolData.map((monthData, indexMonth) => {
                let month = monthData.substr(0, monthData.indexOf('_'));
                let nbUser = monthData.substr(monthData.indexOf('_') + 1);
                dataList[indexEvol].push(nbUser);

                if (indexEvol == 0) {
                    labels.push(months[month]);
                }
            });
        });

        let chart = new Chart(document.getElementById('chartjs-evol'), {
            'type': 'line',
            'data': {
                'labels': labels,
                'datasets': [
                    {
                        'label': ACYM_JS_TXT.ACYM_NEW_SUBSCRIBERS,
                        'data': dataList[0],
                        'fill': false,
                        'borderColor': '#0dba61',
                        'lineTension': 0.1
                    },
                    {
                        'label': ACYM_JS_TXT.ACYM_NEW_UNSUBSCRIBERS,
                        'data': dataList[1],
                        'fill': false,
                        'borderColor': '#ff5259',
                        'lineTension': 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                legend: { //We make custom legends
                    display: false
                },
                maintainAspectRatio: false,
                tooltips: { //on hover the dot
                    backgroundColor: '#fff',
                    borderWidth: 2,
                    borderColor: '#303e46',
                    titleFontSize: 16,
                    titleFontColor: '#303e46',
                    bodyFontColor: '#303e46',
                    bodyFontSize: 14,
                    displayColors: false
                },
                scales: {
                    yAxes: [
                        {
                            gridLines: {
                                display: true
                            },
                            ticks: { //label on the axesY
                                display: true,
                                fontColor: '#0a0a0a',
                                min: 0,
                                userCallback: function (label, index, labels) {
                                    if (Math.floor(label) === label) {
                                        return label;
                                    }
                                }
                            }
                        }
                    ],
                    xAxes: [
                        {
                            gridLines: {
                                display: false
                            },
                            ticks: { //label on the axesX
                                display: true,
                                fontSize: 14,
                                fontColor: '#0a0a0a'
                            }
                        }
                    ]
                }
            }
        });
    }
});
