$(document).ready(function () {
    var currentAssetStructureChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'current-assets-structure-container'
        },
        title: {
            text: ' Cấu trúc tài sản ngắn hạn'
        },
        subtitle: {
            text: 'Current Asset Structure'
        },
        xAxis: {
            title: {
                text: 'Period',                   
            },
            type: 'category',
            crosshair: true,
        },
        yAxis: {
            title: {
                text: '%'
            },
            crosshair: true,
        },
        series: [
            {
                name: 'Tiền và các khoản tương đương tiền/Tài sản ngắn hạn (%)',
                data: []
            },
            {
                name: 'Đầu tư tài chính ngắn hạn/Tài sản ngắn hạn (%)',
                data: []
            },
            {
                name: 'Phải thu ngắn hạn/Tài sản ngắn hạn (%)',
                data: []
            },
            {
                name: 'Hàng tồn kho/Tài sản ngắn hạn (%)',
                data: []
            },
            {
                name: 'Tài sản ngắn hạn khác/Tài sản ngắn hạn (%)',
                data: []
            },
        ]
    });
    currentAssetStructureChart.series[0].setData(cashAndEquivalentData);
    currentAssetStructureChart.series[1].setData(currentFinancialInvestingData);
    currentAssetStructureChart.series[2].setData(currentReceivableAccountData);
    currentAssetStructureChart.series[3].setData(inventoriesData);
    currentAssetStructureChart.series[4].setData(otherCurrentAssetsData);
});