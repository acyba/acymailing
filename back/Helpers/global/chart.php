<?php

function acym_lineChart(array $dataMonth, array $dataDay, array $dataHour, bool $ajax = false): string
{
    acym_initializeChart();

    $month = [];
    $openMonth = [];
    $clickMonth = [];

    foreach ($dataMonth as $key => $data) {
        $month[] = '"'.$key.'"';
        $openMonth[] = '"'.$data['open'].'"';
        $clickMonth[] = '"'.$data['click'].'"';
    }

    $day = [];
    $openDay = [];
    $clickDay = [];

    foreach ($dataDay as $key => $data) {
        $day[] = '"'.$key.'"';
        $openDay[] = '"'.$data['open'].'"';
        $clickDay[] = '"'.$data['click'].'"';
    }

    $hour = [];
    $openHour = [];
    $clickHour = [];

    foreach ($dataHour as $key => $data) {
        $hour[] = '"'.$key.'"';
        $openHour[] = '"'.$data['open'].'"';
        $clickHour[] = '"'.$data['click'].'"';
    }

    $randNumber = rand(1000, 9000);
    $idCanvas = 'acy_canvas_rand_id'.$randNumber;
    $idLegend = 'acy_legend_rand_id'.$randNumber;
    $return = '';

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


    $return .= '<div class="acym__chart__line__container">
                    <div class="acym__chart__line__choose__by">
                        <p class="acym__chart__line__choose__by__one '.$selectedChartMonth.'" onclick="acymChartLineUpdate(this, \'month\')">'.acym_translation('ACYM_BY_MONTH').'</p>
                        <p class="acym__chart__line__choose__by__one '.$selectedChartDay.'" onclick="acymChartLineUpdate(this, \'day\')">'.acym_translation('ACYM_BY_DAY').'</p>
                        <p class="acym__chart__line__choose__by__one '.$selectedChartHour.'" onclick="acymChartLineUpdate(this, \'hour\')">'.acym_translation('ACYM_BY_HOUR').'</p>
                    </div>
                    <div class="acym__chart__line__legend" id="'.$idLegend.'"></div>
                    <canvas id="'.$idCanvas.'" height="400" width="400"></canvas>
                </div>';

    $return .= '<script>
                '.($ajax ? '' : 'document.addEventListener("DOMContentLoaded", function () {').'
                    var ctx = document.getElementById("'.$idCanvas.'").getContext("2d");
                    
                    //Background color under the line
                    var gradientBlue = ctx.createLinearGradient(0, 0, 0, 400);
                    gradientBlue.addColorStop(0, "rgba(128,182,244,0.5)"); 
                    gradientBlue.addColorStop(0.5, "rgba(128,182,244,0.25)"); 
                    gradientBlue.addColorStop(1, "rgba(128,182,244,0)"); 
                    
                    var gradientRed = ctx.createLinearGradient(0, 0, 0, 400);
                    gradientRed.addColorStop(0., "rgba(255,82,89,0.5)"); 
                    gradientRed.addColorStop(0.5, "rgba(255,82,89,0.25)"); 
                    gradientRed.addColorStop(1, "rgba(255,82,89,0)"); 
                    
                    //config of the chart line
                    var config = {
                        type: "line",
                        data: {
                            labels: ["'.acym_translation('ACYM_SENT').'", '.implode(',', $displayed).'],
                            datasets: [{ //We place the open before, because there are less than the clicks
                                label: "'.acym_translation('ACYM_CLICK').'",
                                data: ["0", '.implode(',', $clickDisplayed).'],
                                borderColor: "#00a4ff",
                                fill: true,
                                backgroundColor: gradientBlue,
                                borderWidth: 3,
                                pointBackgroundColor: "#ffffff",
                                pointRadius: 5,
                            },{
                                label: "'.acym_translation('ACYM_OPEN').'",
                                data: ["0", '.implode(',', $openDisplayed).'],
                                borderColor: "#ff5259",
                                fill: true,
                                backgroundColor: gradientRed,
                                borderWidth: 3,
                                pointBackgroundColor: "#ffffff",
                                pointRadius: 5,
                            },]
                        }, options: {
                            responsive: true,
                             legend: { //We make custom legends
                                display: false,
                             }, 
                            tooltips: { //on hover the dot
                                backgroundColor: "#fff",
                                borderWidth: 2,
                                borderColor: "#303e46",
                                titleFontSize: 16,
                                titleFontColor: "#303e46",
                                bodyFontColor: "#303e46",
                                bodyFontSize: 14,
                                displayColors: false
                            },
                            maintainAspectRatio: false, //to fit in the div
                            scales: {
                                yAxes: [{
                                    gridLines: {
                                        display: false
                                    },
                                    ticks: { //label on the axesY
                                        display: true,
                                        fontColor: "#0a0a0a"
                                    }
                                }],
                                xAxes: [{
                                    gridLines: {
                                        display: false
                                    },
                                    ticks: { //label on the axesX
                                        display: true,
                                        fontSize: 14,
                                        fontColor: "#0a0a0a"
                                    }
                                }],
                            },
                            legendCallback: function(chart) { //custom legends
                                var text = [];
                                for (var i = 0; i < chart.data.datasets.length; i++) {
                                  if (chart.data.datasets[i].label) {
                                    text.push(\'<div onclick="updateDataset(event, \'+ chart.legend.legendItems[i].datasetIndex + \', this)" class="acym_chart_line_labels"><div class="acym_chart_line_labels_circle" style="background-color: \' + chart.data.datasets[i].borderColor + \'"></div><span>\' + chart.data.datasets[i].label+\'</span></div>\');

                                  }
                                }
                                return text.join("");
                            },
                        }
                    };
                    var chart = new Chart(ctx, config);
                    document.getElementById("'.$idLegend.'").innerHTML = (chart.generateLegend());
                    updateDataset = function(e, datasetIndex, element) { //hide and show dataset for the custom legends
                        element = element.children[1];
                        var index = datasetIndex;
                        var ci = e.view.chart;
                        var meta = ci.getDatasetMeta(index);
                        
                        meta.hidden = meta.hidden === null? !ci.data.datasets[index].hidden : null;
                        
                        if(element.style.textDecoration == "line-through"){
                            element.style.textDecoration = "none";
                        }else{
                            element.style.textDecoration = "line-through";
                        }
                        
                        ci.update();
                    };
                    acymChartLineUpdate = function(elem, by){
                        document.getElementById("acym__time__linechart__input").value = by;
                    	var chartLineLabels = document.getElementsByClassName("acym_chart_line_labels");
                    	for	(var i = 0; i < chartLineLabels.length; i++){
                    		chartLineLabels[i].getElementsByTagName("span")[0].style.textDecoration = "none";
                    	}
                        if(by === "month"){
                            var labels = ["'.acym_translation('ACYM_SENT').'", '.implode(',', $month).'];
                            var dataOpen = ["0", '.implode(',', $openMonth).'];
                            var dataClick = ["0", '.implode(',', $clickMonth).'];
                        }else if(by == "day"){
                            var labels = ["'.acym_translation('ACYM_SENT').'", '.implode(',', $day).'];
                            var dataOpen = ["0", '.implode(',', $openDay).'];
                            var dataClick = ["0", '.implode(',', $clickDay).'];
                        }else if(by == "hour"){
                            var labels = ["'.acym_translation('ACYM_SENT').'", '.implode(',', $hour).'];
                            var dataOpen = ["0", '.implode(',', $openHour).'];
                            var dataClick = ["0", '.implode(',', $clickHour).'];
                        }
                        chart.config.data.labels = labels,
                        chart.config.data.datasets = [{ //We place the open before, because there are less than the clicks
                                label: "'.acym_translation('ACYM_CLICK').'",
                                data: dataClick,
                                borderColor: "#00a4ff",
                                fill: true,
                                backgroundColor: gradientBlue,
                                borderWidth: 3,
                                pointBackgroundColor: "#ffffff",
                                pointRadius: 5,
                            },{
                                label: "'.acym_translation('ACYM_OPEN').'",
                                data: dataOpen,
                                borderColor: "#ff5259",
                                fill: true,
                                backgroundColor: gradientRed,
                                borderWidth: 3,
                                pointBackgroundColor: "#ffffff",
                                pointRadius: 5,
                            }
                        ];
                        chart.update();
                        var allChooseBy = document.getElementsByClassName("acym__chart__line__choose__by__one");
                        for(var i = 0; i < allChooseBy.length;i++){
                            allChooseBy[i].classList.remove("selected__choose_by");
                        }
                        elem.classList.add("selected__choose_by");
                    }
                    document.querySelector(".selected__choose_by").click();
                '.($ajax ? '' : '});').'
                </script>';

    return $return;
}

function acym_initializeChart(): void
{
    static $loaded = false;

    if (!$loaded) {
        acym_addScript(false, ACYM_JS.'libraries/chart.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'chart.min.js'), ['defer' => false, 'needTagScript' => true]);
        $loaded = true;
    }
}

function acym_roundChart($percentage, string $type = '', string $class = '', string $topLabel = ''): string
{
    if (empty($percentage) && $percentage !== 0) {
        return '';
    }

    acym_initializeChart();

    $randNumber = rand(1000, 9000);
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
        } elseif ($percentage < $valueHigh && $percentage >= $valueLow) {
            $color = $orange;
        } elseif ($percentage < $valueLow) {
            $color = $isInverted ? $green : $red;
        }
    }

    $return = '<div class="'.$class.' acym__chart__doughnut text-center">
                        <p class="text-center acym__chart__doughnut__container__top-label">'.$topLabel.'</p>
                        <div class="acym__chart__doughnut__container" id="'.$id.'">
                            <canvas id="'.$idCanvas.'" width="200" height="200"></canvas>
                        </div>
                </div>';
    $return .= '<script>
            //Override to add text in the middle of chart
            document.addEventListener("DOMContentLoaded", function () {
            Chart.pluginService.register({
                beforeDraw: function(chart){
                    if(chart.config.options.elements.center){
                        //Get ctx from string
                        var ctx = chart.chart.ctx;
        
                        //Get options from the center object in options
                        var centerConfig = chart.config.options.elements.center;
                        var fontStyle = centerConfig.fontStyle || "Arial";
                        var txt = centerConfig.text;
                        var color = centerConfig.color || "#000";
                        //Set font settings to draw it correctly.
                        ctx.textAlign = "center";
                        ctx.textBaseline = "middle";
                        var centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
                        var centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);
                        ctx.font = "15px " + fontStyle;
                        ctx.fillStyle = color;
        
                        //Draw text in center
                        ctx.fillText(txt, centerX, centerY);
                    }
                }
            });
            var ctx = document.getElementById("'.$idCanvas.'").getContext("2d");
            var config = {
                type: "doughnut", data: {
                    datasets: [{
                        data: ['.$percentage.', (100 - '.$percentage.')], //Data of chart
                         backgroundColor: ["'.$color.'", "#f1f1f1"], //Two color of chart
                         borderWidth: 0 //no border
                    }]
                }, options: {
                    responsive: true,
                     legend: {
                        display: false,
                     }, 
                    elements: {
                        //This is for the text
                        center: {
                            text: "'.$percentage.'%", color: "#363636", 
                            fontStyle: "Poppins", 
                            sidePadding: 70 
                        }
                    }, 
                    cutoutPercentage: 90, //thickness donut
                    tooltips: {
                        enabled: false //disable the tooltips on hover
                    }
                }
            };
            var chart = new Chart(ctx, config);
            });
        </script>';


    return $return;
}

function acym_pieChart(array $data = [], string $class = '', string $topLabel = '', bool $cap = true, bool $perList = false): string
{
    if (empty($data)) {
        return '';
    }

    acym_initializeChart();

    $randNumber = rand(1000, 9000);
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

    $allNumbers = implode(',', $data);
    $allLabels = "'".implode("', '", $allLabelsArray)."'";
    $allColors = "'".implode("', '", $colors)."'";

    $return = '<div class="'.$class.' acym__chart__pie grid-x">
                        <p class="text-center acym__chart__pie__container__top-label cell medium-6">'.$topLabel.'</p>
                        <div class="acym__chart__pie__container grid-x cell" id="'.$id.'">
                            <div class="acym__chart__pie__canvas_container cell medium-6">                            
                                <canvas id="'.$idCanvas.'" width="200" height="200"></canvas> 
                            </div>
                            <div class="acym__chart__pie__legend cell medium-6 padding-left-1" id="'.$idLegend.'"></div>
                        </div>
                </div>';

    $return .= '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById("'.$idCanvas.'").getContext("2d");
            const config = {
                type: "pie",
                 data: {
                    datasets: [{
                        data: ['.$allNumbers.'],
                        backgroundColor: ['.$allColors.'],
                    }],
                    labels: ['.$allLabels.']
                }, options: {
                    responsive: true,
                    legend: {
                        display: false,
                     }, 
                    tooltips: {
                        backgroundColor: "#fff",
                        borderWidth: 2,
                        borderColor: "#303e46",
                        titleFontSize: 16,
                        titleFontColor: "#303e46",
                        bodyFontColor: "#303e46",
                        bodyFontSize: 14,
                    },
                    legendCallback: function(chart) {
                        const dataSets = chart.data.datasets;
                        const colors = dataSets[0].backgroundColor;
                        const numbers = dataSets[0].data;
                        const labels = chart.data.labels;
                        const text = [];
                        
                        if (colors.length !== labels.length) {
                            return "";
                        }
                        
                        for (let i = 0; i < labels.length; i++) {
                            text.push(\'<div class="acym_chart_pie_labels"><div class="acym_chart_pie_labels_circle" style="background-color: \' + colors[i] + \'"></div>\' + labels[i] + " (" + numbers[i] + ")" + \'</div>\');
                        }
                        
                        return text.join("");
                    },
                }
            };
            const chart = new Chart(ctx, config);
            document.getElementById("'.$idLegend.'").innerHTML = (chart.generateLegend());
        });
</script>';

    return $return;
}

function acym_barChart(array $data = [], string $topLabel = ''): string
{
    if (empty($data)) {
        return '';
    }

    acym_initializeChart();

    $randNumber = rand(1000, 9000);
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

    $allNumbers = implode(',', $data);
    $allLabels = "'".implode("', '", $allLabelsArray)."'";
    $allColors = "'".implode("', '", $colors)."'";

    $return = '<div class="acym__chart__pie grid-x">
                        <p class="text-center acym__chart__pie__container__top-label cell medium-6">'.$topLabel.'</p>
                        <div class="acym__chart__pie__container grid-x cell" id="'.$id.'">
                            <div class="acym__chart__pie__canvas_container cell medium-6">                            
                                <canvas id="'.$idCanvas.'" width="200" height="200"></canvas> 
                            </div>
                            <div class="acym__chart__pie__legend cell medium-6 padding-left-1" id="'.$idLegend.'"></div>
                        </div>
                </div>';
    $return .= '<script>

        document.addEventListener("DOMContentLoaded", function () {
            var ctx = document.getElementById("'.$idCanvas.'").getContext("2d");
            var config = {
                type: "bar",
                 data: {
                    datasets: [{
                        data: ['.$allNumbers.'], //Data of chart
                        backgroundColor: ['.$allColors.'],
                    }],
                    labels: ['.$allLabels.']
                }, options: {
                    responsive: true,
                    legend: {
                        display: false,
                     }, 
                    tooltips: {
                        backgroundColor: "#fff",
                        borderWidth: 2,
                        borderColor: "#303e46",
                        titleFontSize: 16,
                        titleFontColor: "#303e46",
                        bodyFontColor: "#303e46",
                        bodyFontSize: 14,
                    },
                    legendCallback: function(chart) {
                        let dataSets = chart.data.datasets;
                        let colors = dataSets[0].backgroundColor;
                        let numbers = dataSets[0].data;
                        let labels = chart.data.labels;
                        let text = [];
                        
                        if (colors.length !== labels.length) {
                            return "";
                        }
                        
                        for (let i = 0; i < labels.length; i++) {
                            text.push(\'<div class="acym_chart_pie_labels"><div class="acym_chart_pie_labels_circle" style="background-color: \' + colors[i] + \'"></div>\' + labels[i] + " (" + numbers[i] + "%)" + \'</div>\');
                        }
                        
                        return text.join("");
                    },
                  
                    scales: {
                      xAxes: [
                        {
                            gridLines:{
                                drawTicks:false
                            },
                            ticks: {
                            display: false
                            }
                        }
                    ],
                      yAxes: [
                        {
                            gridLines:{
                                drawTicks:false, 
                            },
                          ticks: {
                            min: 0,
                            max: 100,
                            padding: 10
                          },
                        },
                      ],
                    },

                }
            };
            var chart = new Chart(ctx, config);
            document.getElementById("'.$idLegend.'").innerHTML = (chart.generateLegend());
        });
</script>';

    return $return;
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
