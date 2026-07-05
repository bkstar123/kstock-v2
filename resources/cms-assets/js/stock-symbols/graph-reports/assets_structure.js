$(document).ready(function () {
    var assetStructureChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'assets-structure-container'
        },
        title: {
            text: ' Cấu trúc tài sản'
        },
        subtitle: {
            text: 'Assets Structure'
        },
        xAxis: {
            title: {
                text: 'Period',                   
            },
            type: 'category',
            crosshair: true,
        },
        yAxis: [
            {
                title: {
                    text: '%'
                },
                crosshair: true,
            },
            {
                title: {
                    text: 'Tỷ VND'
                },
                crosshair: true,
                opposite: true
            },
        ],
        series: [
            {
                name: 'Tài sản ngắn hạn / Tổng tài sản (%)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Tài sản dài hạn/Tổng tài sản (%)',
                yAxis: 0,
                data: []
            },
            {
                type: 'spline',
                name: 'Tổng tài sản (Tỷ VND)',
                yAxis: 1,
                data: []
            },
        ]
    });

    assetStructureChart.series[0].setData(currentAssetsData);
    assetStructureChart.series[1].setData(longTermAssetsData);
    assetStructureChart.series[2].setData(totalAssetData);
});