$(document).ready(function () {
    var longTermAssetStructureChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'long-term-assets-structure-container'
        },
        title: {
            text: ' Cấu trúc tài sản dài hạn'
        },
        subtitle: {
            text: 'Long Term Assets Structure'
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
                name: 'Các khoản phải thu dài hạn/Tài sản dài hạn (%)',
                data: []
            },
            {
                name: 'Tài sản cố định/Tài sản dài hạn (%)',
                data: []
            },
            {
                name: 'Bất động sản đầu tư/Tài sản dài hạn (%)',
                data: []
            },
            {
                name: 'Tài sản dở dang dài hạn/Tài sản dài hạn (%)',
                data: []
            },
            {
                name: 'Các khoản đầu tư tài chính dài hạn/Tài sản dài hạn (%)',
                data: []
            },
            {
                name: 'Các tài sản dài hạn khác/Tài sản dài hạn (%)',
                data: []
            },
        ]
    });

    var fixedAssetStructureChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'fixed-assets-structure-container'
        },
        title: {
            text: ' Cấu trúc tài sản cố định'
        },
        subtitle: {
            text: 'Fixed Assets Structure'
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
                name: 'Tài sản cố định hữu hình/Tài sản cố định (%)',
                data: []
            },
            {
                name: 'Tài sản cố định cho thuê tài chính/Tài sản cố định (%)',
                data: []
            },
            {
                name: 'Tài sản cố định vô hình/Tài sản cố định (%)',
                data: []
            },
        ]
    });
    longTermAssetStructureChart.series[0].setData(longTermReceivablesData);
    longTermAssetStructureChart.series[1].setData(fixedAssetsData);
    longTermAssetStructureChart.series[2].setData(investingRealEstatesData);
    longTermAssetStructureChart.series[3].setData(longTermAssetsinProgressData);
    longTermAssetStructureChart.series[4].setData(longTermFinancialInvestingData);
    longTermAssetStructureChart.series[5].setData(otherLongTermAssetsData);

    fixedAssetStructureChart.series[0].setData(tangibleFixedAssetsData);
    fixedAssetStructureChart.series[1].setData(financialLendingFixedAssetsData);
    fixedAssetStructureChart.series[2].setData(intangibleFixedAssetsData);
});