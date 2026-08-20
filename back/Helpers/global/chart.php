<?php
// context verification

function acym_lineChart(array $dataMonth, array $dataDay, array $dataHour, bool $ajax = false): void
{
    acym_initializeChart();

    $month = [];
    $openMonth = [];
    $clickMonth = [];

    foreach ($dataMonth as $key => $data) {
        $month[] = $key;
        $openMonth[] = $data['open'];
        $clickMonth[] = $data['click'];
    }

    $day = [];
    $openDay = [];
    $clickDay = [];

    foreach ($dataDay as $key => $data) {
        $day[] = $key;
        $openDay[] = $data['open'];
        $clickDay[] = $data['click'];
    }

    $hour = [];
    $openHour = [];
    $clickHour = [];

    foreach ($dataHour as $key => $data) {
        $hour[] = $key;
        $openHour[] = $data['open'];
        $clickHour[] = $data['click'];
    }

    $randNumber = acym_rand(1000, 9000);
    $idCanvas = 'acy_canvas_rand_id'.$randNumber;
    $idLegend = 'acy_legend_rand_id'.$randNumber;

    $nbDataDay = count($dataDay);
    $nbDataHour = count($dataHour);
    $selectedChartHour = "";
    $selectedChartDay = "";
    $selectedChartMonth = "";

    if ($nbDataHour < 49) {
        $selectedChartHour = "selected__choose_by";
        $displayed = $hour;
        $clickDisplayed = $clickHour;
        $openDisplayed = $openHour;
    } elseif ($nbDataDay < 63) {
        $selectedChartDay = "selected__choose_by";
        $displayed = $day;
        $clickDisplayed = $clickDay;
        $openDisplayed = $openDay;
    } else {
        $selectedChartMonth = "selected__choose_by";
        $displayed = $month;
        $clickDisplayed = $clickMonth;
        $openDisplayed = $openMonth;
    }
    ?>

	<div class="acym__chart__line__container">
		<div class="acym__chart__line__choose__by">
			<p class="acym__chart__line__choose__by__one <?php echo acym_escape($selectedChartMonth); ?>" onclick="acymChartLineUpdate(this, 'month')">
                <?php echo acym_escapeHtml(acym_translation('ACYM_BY_MONTH')); ?>
			</p>
			<p class="acym__chart__line__choose__by__one <?php echo acym_escape($selectedChartDay); ?>" onclick="acymChartLineUpdate(this, 'day')">
                <?php echo acym_escapeHtml(acym_translation('ACYM_BY_DAY')); ?>
			</p>
			<p class="acym__chart__line__choose__by__one <?php echo acym_escape($selectedChartHour); ?>" onclick="acymChartLineUpdate(this, 'hour')">
                <?php echo acym_escapeHtml(acym_translation('ACYM_BY_HOUR')); ?>
			</p>
		</div>
		<div class="acym__chart__line__legend" id="<?php echo acym_escape($idLegend); ?>"></div>
		<canvas id="<?php echo acym_escape($idCanvas); ?>" height="400" width="400"></canvas>
	</div>

	<script>
        <?php echo $ajax ? '' : 'document.addEventListener("DOMContentLoaded", function () {'; ?>
        const ctx = document.getElementById(<?php echo json_encode($idCanvas); ?>).getContext('2d');

        //Background color under the line
        const gradientBlue = ctx.createLinearGradient(0, 0, 0, 400);
        gradientBlue.addColorStop(0, 'rgba(128,182,244,0.5)');
        gradientBlue.addColorStop(0.5, 'rgba(128,182,244,0.25)');
        gradientBlue.addColorStop(1, 'rgba(128,182,244,0)');

        const gradientRed = ctx.createLinearGradient(0, 0, 0, 400);
        gradientRed.addColorStop(0., 'rgba(255,82,89,0.5)');
        gradientRed.addColorStop(0.5, 'rgba(255,82,89,0.25)');
        gradientRed.addColorStop(1, 'rgba(255,82,89,0)');

        const config = {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_merge([acym_translation('ACYM_SENT')], $displayed)); ?>,
                datasets: [
                    {
                        label: <?php echo json_encode(acym_translation('ACYM_CLICK')); ?>,
                        data: <?php echo json_encode(array_merge(['0'], $clickDisplayed)); ?>,
                        borderColor: '#00a4ff',
                        fill: true,
                        backgroundColor: gradientBlue,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointRadius: 5
                    },
                    {
                        label: <?php echo json_encode(acym_translation('ACYM_OPEN')); ?>,
                        data: <?php echo json_encode(array_merge(['0'], $openDisplayed)); ?>,
                        borderColor: '#ff5259',
                        fill: true,
                        backgroundColor: gradientRed,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: '#fff',
                    borderWidth: 2,
                    borderColor: '#303e46',
                    titleFontSize: 16,
                    titleFontColor: '#303e46',
                    bodyFontColor: '#303e46',
                    bodyFontSize: 14,
                    displayColors: false
                },
                maintainAspectRatio: false,
                scales: {
                    yAxes: [
                        {
                            gridLines: {
                                display: false
                            },
                            ticks: {
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
                            ticks: {
                                display: true,
                                fontSize: 14,
                                fontColor: '#0a0a0a'
                            }
                        }
                    ]
                },
                legendCallback: function (chart) {
                    const text = [];
                    for (let i = 0 ; i < chart.data.datasets.length ; i++) {
                        if (chart.data.datasets[i].label) {
                            text.push(`<div onclick="updateDataset(event, ${chart.legend.legendItems[i].datasetIndex}, this)" class="acym_chart_line_labels">
									<div class="acym_chart_line_labels_circle" style="background-color: ${chart.data.datasets[i].borderColor}"></div>
									<span>${chart.data.datasets[i].label}</span>
								</div>`);
                        }
                    }
                    return text.join('');
                }
            }
        };

        const chart = new Chart(ctx, config);
        document.getElementById(<?php echo json_encode($idLegend); ?>).innerHTML = chart.generateLegend();

        updateDataset = function (e, datasetIndex, element) {
            element = element.children[1];
            const index = datasetIndex;
            const ci = e.view.chart;
            const meta = ci.getDatasetMeta(index);

            meta.hidden = meta.hidden === null ? !ci.data.datasets[index].hidden : null;

            if (element.style.textDecoration === 'line-through') {
                element.style.textDecoration = 'none';
            } else {
                element.style.textDecoration = 'line-through';
            }

            ci.update();
        };

        acymChartLineUpdate = function (elem, by) {
            document.getElementById('acym__time__linechart__input').value = by;
            const chartLineLabels = document.getElementsByClassName('acym_chart_line_labels');
            for (let i = 0 ; i < chartLineLabels.length ; i++) {
                chartLineLabels[i].getElementsByTagName('span')[0].style.textDecoration = 'none';
            }

            let labels = [];
            let dataOpen = [];
            let dataClick = [];

            if (by === 'month') {
                labels = <?php echo json_encode(array_merge([acym_translation('ACYM_SENT')], $month)); ?>;
                dataOpen = <?php echo json_encode(array_merge(['0'], $openMonth)); ?>;
                dataClick = <?php echo json_encode(array_merge(['0'], $clickMonth)); ?>;
            } else if (by === 'day') {
                labels = <?php echo json_encode(array_merge([acym_translation('ACYM_SENT')], $day)); ?>;
                dataOpen = <?php echo json_encode(array_merge(['0'], $openDay)); ?>;
                dataClick = <?php echo json_encode(array_merge(['0'], $clickDay)); ?>;
            } else if (by === 'hour') {
                labels = <?php echo json_encode(array_merge([acym_translation('ACYM_SENT')], $hour)); ?>;
                dataOpen = <?php echo json_encode(array_merge(['0'], $openHour)); ?>;
                dataClick = <?php echo json_encode(array_merge(['0'], $clickHour)); ?>;
            }

            chart.config.data.labels = labels;
            chart.config.data.datasets = [
                {
                    label: <?php echo json_encode(acym_translation('ACYM_CLICK')); ?>,
                    data: dataClick,
                    borderColor: '#00a4ff',
                    fill: true,
                    backgroundColor: gradientBlue,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointRadius: 5
                },
                {
                    label: <?php echo json_encode(acym_translation('ACYM_OPEN')); ?>,
                    data: dataOpen,
                    borderColor: '#ff5259',
                    fill: true,
                    backgroundColor: gradientRed,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointRadius: 5
                }
            ];
            chart.update();
            const allChooseBy = document.getElementsByClassName('acym__chart__line__choose__by__one');
            for (let i = 0 ; i < allChooseBy.length ; i++) {
                allChooseBy[i].classList.remove('selected__choose_by');
            }
            elem.classList.add('selected__choose_by');
        };

        document.querySelector('.selected__choose_by').click();
        <?php echo $ajax ? '' : '});'; ?>
	</script>
    <?php
}

function acym_initializeChart(): void
{
    static $loaded = false;

    if (!$loaded) {
        acym_addScript(false, ACYM_JS.'libraries/chart.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'chart.min.js'), ['defer' => false, 'needTagScript' => true]);
        $loaded = true;
    }
}

function acym_displayRoundChart($percentage, string $type = '', string $class = '', string $topLabel = ''): void
{
    if (empty($percentage) && $percentage !== 0) {
        return;
    }

    acym_initializeChart();

    $randNumber = acym_rand(1000, 9000);
    $id = 'acy_round_chart_rand_id'.$randNumber;
    $idCanvas = 'acy_canvas_rand_id'.$randNumber;

    $green = '#3dea91';
    $red = '#ff5259';
    $orange = '#ffab15';
    $defaultColor = '#00a4ff';

    $isFixColor = false;
    $isInverted = false;

    switch ($type) {
        case 'click':
            $valueHigh = 5;
            $valueLow = 1;
            break;
        case 'open':
            $valueHigh = 30;
            $valueLow = 18;
            break;
        case 'delivery':
            $valueHigh = 90;
            $valueLow = 70;
            break;
        case 'fail':
            $valueHigh = 30;
            $valueLow = 10;
            $isInverted = true;
            break;
        case 'unsubscribe':
            $valueHigh = 10;
            $valueLow = 1;
            $isInverted = true;
            break;
        default:
            $isFixColor = true;
    }

    $color = $defaultColor;
    if (!$isFixColor) {
        if ($percentage >= $valueHigh) {
            $color = $isInverted ? $red : $green;
        } elseif ($percentage >= $valueLow) {
            $color = $orange;
        } else {
            $color = $isInverted ? $green : $red;
        }
    }
    ?>

	<div class="<?php echo acym_escape($class); ?> acym__chart__doughnut text-center">
		<p class="text-center acym__chart__doughnut__container__top-label"><?php echo acym_escapeHtmlWithAllowedTags($topLabel, ['span' => ['class' => true], 'label' => []]); ?></p>
		<div class="acym__chart__doughnut__container" id="<?php echo acym_escape($id); ?>">
			<canvas id="<?php echo acym_escape($idCanvas); ?>" width="200" height="200"></canvas>
		</div>
	</div>

	<script>
        // Override to add text in the middle of chart
        document.addEventListener('DOMContentLoaded', function () {
            Chart.pluginService.register({
                beforeDraw: function (chart) {
                    if (chart.config.options.elements.center) {
                        const ctx = chart.chart.ctx;

                        //Get options from the center object in options
                        const centerConfig = chart.config.options.elements.center;
                        const fontStyle = centerConfig.fontStyle || 'Arial';
                        const txt = centerConfig.text;
                        const color = centerConfig.color || '#000';
                        //Set font settings to draw it correctly.
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        const centerX = (
                            (
                                chart.chartArea.left + chart.chartArea.right
                            ) / 2
                        );
                        const centerY = (
                            (
                                chart.chartArea.top + chart.chartArea.bottom
                            ) / 2
                        );
                        ctx.font = '15px ' + fontStyle;
                        ctx.fillStyle = color;

                        //Draw text in center
                        ctx.fillText(txt, centerX, centerY);
                    }
                }
            });

            const ctx = document.getElementById(<?php echo json_encode($idCanvas); ?>).getContext('2d');
            const config = {
                type: 'doughnut',
                data: {
                    datasets: [
                        {
                            data: <?php echo json_encode([$percentage, 100 - $percentage]); ?>,
                            backgroundColor: <?php echo json_encode([$color, "#f1f1f1"]); ?>,
                            borderWidth: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    legend: {
                        display: false
                    },
                    elements: {
                        center: {
                            text: <?php echo json_encode($percentage.'%'); ?>,
                            color: '#363636',
                            fontStyle: 'Poppins',
                            sidePadding: 70
                        }
                    },
                    cutoutPercentage: 90, // Thickness
                    tooltips: {
                        enabled: false // Disable tooltips on hover
                    }
                }
            };

            new Chart(ctx, config);
        });
	</script>
    <?php
}

function acym_pieChart(array $data = [], string $class = '', string $topLabel = '', bool $cap = true, bool $perList = false): void
{
    if (empty($data)) {
        return;
    }

    acym_initializeChart();

    $randNumber = acym_rand(1000, 9000);
    $id = 'acy_pie_chart_rand_id'.$randNumber;
    $idCanvas = 'acy_canvas_rand_id'.$randNumber;
    $idLegend = 'acy_legend_rand_id'.$randNumber;

    $allLabelsArray = [];
    $colors = [];

    // Sort values higher to lower
    asort($data);
    $data = array_reverse($data, true);

    // Move Others to the bottom
    if (isset($data['ACYM_OTHER'])) {
        $otherValue = $data['ACYM_OTHER'];
        unset($data['ACYM_OTHER']);
        $data['ACYM_OTHER'] = $otherValue;
    }

    $position = 0;
    $othersValue = 0;
    $nbOthers = 0;
    if ($perList) {
        foreach ($data as $itemId => $item) {
            if ($position > 9 && $cap) {
                $othersValue += (float)$item['value'];
                unset($data[$itemId]);
                $nbOthers++;
                continue;
            }
            $data[$itemId] = (float)$item['value'];
            $allLabelsArray[] = $item['label'];
            $colors[] = $item['color'];
            $position++;
        }

        if ($othersValue > 0) {
            $othersValue = $othersValue / $nbOthers;
        }
    } else {
        foreach ($data as $label => $number) {
            if ($position > 9 && $cap) {
                $othersValue += (float)$number;
                unset($data[$label]);
                continue;
            }
            $data[$label] = (float)$number;
            $allLabelsArray[] = acym_translation($label);
            $colors[] = acym_getChartColor($position);
            $position++;
        }
    }

    // We capped the number of elements shown, add the remaining values as "Others" at the end
    if ($othersValue > 0) {
        $data['ACYM_OTHER'] = $othersValue;
        $allLabelsArray[] = acym_translation('ACYM_OTHER');
        $colors[] = acym_getChartColor($position);
    }
    ?>
	<div class="<?php echo acym_escape($class); ?> acym__chart__pie grid-x">
		<p class="text-center acym__chart__pie__container__top-label cell medium-6"><?php echo acym_escapeHtmlWithAllowedTags($topLabel, ['span' => ['class' => true], 'label' => []]); ?></p>
		<div class="acym__chart__pie__container grid-x cell" id="<?php echo acym_escape($id); ?>">
			<div class="acym__chart__pie__canvas_container cell medium-6">
				<canvas id="<?php echo acym_escape($idCanvas); ?>" width="200" height="200"></canvas>
			</div>
			<div class="acym__chart__pie__legend cell medium-6 padding-left-1" id="<?php echo acym_escape($idLegend); ?>"></div>
		</div>
	</div>

	<script>
        document.addEventListener('DOMContentLoaded', function () {
            const chart = new Chart(document.getElementById(<?php echo json_encode($idCanvas); ?>).getContext('2d'), {
                type: 'pie',
                data: {
                    datasets: [
                        {
                            data: <?php echo json_encode(array_values($data)); ?>,
                            backgroundColor: <?php echo json_encode($colors); ?>,
                        }
                    ],
                    labels: <?php echo json_encode($allLabelsArray); ?>
                },
                options: {
                    responsive: true,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        backgroundColor: '#fff',
                        borderWidth: 2,
                        borderColor: '#303e46',
                        titleFontSize: 16,
                        titleFontColor: '#303e46',
                        bodyFontColor: '#303e46',
                        bodyFontSize: 14
                    },
                    legendCallback: function (chart) {
                        const dataSets = chart.data.datasets;
                        const colors = dataSets[0].backgroundColor;
                        const numbers = dataSets[0].data;
                        const labels = chart.data.labels;
                        const text = [];

                        if (colors.length !== labels.length) {
                            return '';
                        }

                        for (let i = 0 ; i < labels.length ; i++) {
                            text.push(`
								<div class="acym_chart_pie_labels">
									<div class="acym_chart_pie_labels_circle" style="background-color: ${colors[i]}"></div>
									${labels[i]} (${numbers[i]})
								</div>`);
                        }

                        return text.join('');
                    }
                }
            });

            document.getElementById(<?php echo json_encode($idLegend); ?>).innerHTML = chart.generateLegend();
        });
	</script>
    <?php
}

function acym_displayBarChart(array $data = [], string $topLabel = ''): void
{
    if (empty($data)) {
        return;
    }

    acym_initializeChart();

    $randNumber = acym_rand(1000, 9000);
    $id = 'acy_pie_chart_rand_id'.$randNumber;
    $idCanvas = 'acy_canvas_rand_id'.$randNumber;
    $idLegend = 'acy_legend_rand_id'.$randNumber;

    $allLabelsArray = [];
    $colors = [];

    asort($data);
    $data = array_reverse($data, true);
    $position = 0;
    $cappedValue = 0;
    $nbOther = 0;
    foreach ($data as $itemId => $item) {
        if ($position > 9) {
            $cappedValue += (float)$item['value'];
            unset($data[$itemId]);
            $nbOther++;
            continue;
        }
        $data[$itemId] = (float)$item['value'];
        $allLabelsArray[] = $item['label'];
        $colors[] = $item['color'];
        $position++;
    }

    if ($cappedValue > 0) {
        $cappedValue = $cappedValue / $nbOther;
        $data[acym_translation('ACYM_OTHER')] = $cappedValue;
        $allLabelsArray[] = acym_translation('ACYM_OTHER');
        $colors[] = acym_getChartColor($position);
    }
    ?>

	<div class="acym__chart__pie grid-x">
		<p class="text-center acym__chart__pie__container__top-label cell medium-6"><?php echo acym_escapeHtmlWithAllowedTags($topLabel, ['span' => ['class' => true], 'label' => []]); ?></p>
		<div class="acym__chart__pie__container grid-x cell" id="<?php echo acym_escape($id); ?>">
			<div class="acym__chart__pie__canvas_container cell medium-6">
				<canvas id="<?php echo acym_escape($idCanvas); ?>" width="200" height="200"></canvas>
			</div>
			<div class="acym__chart__pie__legend cell medium-6 padding-left-1" id="<?php echo acym_escape($idLegend); ?>"></div>
		</div>
	</div>

	<script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById("<?php echo acym_escape($idCanvas); ?>").getContext('2d');
            const config = {
                type: 'bar',
                data: {
                    datasets: [
                        {
                            data: <?php echo json_encode(array_values($data)); ?>,
                            backgroundColor: <?php echo json_encode($colors); ?>,
                        }
                    ],
                    labels: <?php echo json_encode($allLabelsArray); ?>
                },
                options: {
                    responsive: true,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        backgroundColor: '#fff',
                        borderWidth: 2,
                        borderColor: '#303e46',
                        titleFontSize: 16,
                        titleFontColor: '#303e46',
                        bodyFontColor: '#303e46',
                        bodyFontSize: 14
                    },
                    legendCallback: function (chart) {
                        let dataSets = chart.data.datasets;
                        let colors = dataSets[0].backgroundColor;
                        let numbers = dataSets[0].data;
                        let labels = chart.data.labels;
                        let text = [];

                        if (colors.length !== labels.length) {
                            return '';
                        }

                        for (let i = 0 ; i < labels.length ; i++) {
                            text.push(`<div class="acym_chart_pie_labels">
                            	<div class="acym_chart_pie_labels_circle" style="background-color: ${colors[i]}"></div>
                            	${labels[i]} (${numbers[i]}%)
                            	</div>`);
                        }

                        return text.join('');
                    },

                    scales: {
                        xAxes: [
                            {
                                gridLines: {
                                    drawTicks: false
                                },
                                ticks: {
                                    display: false
                                }
                            }
                        ],
                        yAxes: [
                            {
                                gridLines: {
                                    drawTicks: false
                                },
                                ticks: {
                                    min: 0,
                                    max: 100,
                                    padding: 10
                                }
                            }
                        ]
                    }

                }
            };
            const chart = new Chart(ctx, config);
            document.getElementById(<?php echo json_encode($idLegend); ?>).innerHTML = (
                chart.generateLegend()
            );
        });
	</script>
    <?php
}

function acym_getChartColor(int $position): string
{
    $colors = [
        '#845EC2',
        '#D65DB1',
        '#FF6F91',
        '#FF9671',
        '#FFC75F',
        '#F9F871',
        '#8BE884',
        '#00CFA9',
        '#00AFC6',
        '#008AC9',
        '#2261AC',
    ];

    return $colors[$position % 11];
}
