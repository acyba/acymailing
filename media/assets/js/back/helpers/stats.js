const acym_helperStats = {
    setLineChartOpenTimeWeek: function () {
        let dataOpenTime = document.getElementById('acym__stats__global__open-time__data');
        if (dataOpenTime === null) return;
        
        let data = document.getElementById('acym__stats__global__open-time__data').value;

        if (data === undefined) return;

        data = acym_helper.parseJson(data);

        let labels = [];
        let dataList = [];
        let labelsNotFormatted = {
            sunday: [],
            other: []
        };
        let dataListNotFormatted = {
            sunday: [],
            other: []
        };

        let days = {
            0: ACYM_JS_TXT.ACYM_SUNDAY,
            1: ACYM_JS_TXT.ACYM_MONDAY,
            2: ACYM_JS_TXT.ACYM_TUESDAY,
            3: ACYM_JS_TXT.ACYM_WEDNESDAY,
            4: ACYM_JS_TXT.ACYM_THURSDAY,
            5: ACYM_JS_TXT.ACYM_FRIDAY,
            6: ACYM_JS_TXT.ACYM_SATURDAY
        };

        let hours = {
            1: '3h',
            2: '6h',
            3: '9h',
            4: '12h',
            5: '15h',
            6: '18h',
            7: '21h'
        };

        data.map((dayData, indexDay) => {
            let labelToFill = indexDay === 0 ? 'sunday' : 'other';
            dayData.map((hourData, indexHour) => {
                if (indexHour === 0) {
                    labelsNotFormatted[labelToFill].push(days[indexDay]);
                } else {
                    labelsNotFormatted[labelToFill].push(hours[indexHour]);
                }
                dataListNotFormatted[labelToFill].push(hourData);
            });
        });

        labels = labelsNotFormatted.other.concat(labelsNotFormatted.sunday);
        dataList = dataListNotFormatted.other.concat(dataListNotFormatted.sunday);

        let chart = new Chart(document.getElementById('chartjs-0'), {
            'type': 'line',
            'data': {
                'labels': labels,
                'datasets': [
                    {
                        'label': ACYM_JS_TXT.ACYM_OPEN_PERCENTAGE,
                        'data': dataList,
                        'fill': false,
                        'borderColor': 'rgb(0, 165, 255)',
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
                                display: false
                            },
                            ticks: { //label on the axesY
                                display: true,
                                fontColor: '#0a0a0a'
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
};
