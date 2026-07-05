$(document).ready(function () {
    var dupont5Chart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'dupont-level5-container'
        },
        title: {
            text: 'Phân tích Dupont cấp 5'
        },
        subtitle: {
            text: 'Dupont Level 5 Analysis'
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
                    text: 'Lần / Vòng'
                },
                crosshair: true,
            },
            {
                title: {
                    text: '%'
                },
                crosshair: true,
                opposite: true
            }
        ],
        series: [
            {
                name: 'LNST của cổ đông công ty mẹ / LNTT (lần)',
                data: [],
                yAxis: 0,
            },
            {
                name: 'LNST/LNTT (lần)',
                data: [],
                yAxis: 0,
            },
            {
                name: 'LNTT/EBIT (lần)',
                data: [],
                yAxis: 0,
            },
            {
                name: 'EBIT/Doanh thu thuần (%)',
                data: [],
                yAxis: 1,
            },
            {
                name: 'Vòng quay tổng tài sản bình quân (vòng)',
                data: [],
                yAxis: 0,
            },
            {
                name: 'Hệ số đòn bẩy tài chính trung bình (lần)',
                data: [],
                yAxis: 0,
            },
            {
                name: 'ROEA (%)',
                data: [],
                type: 'column',
                yAxis: 1,
            },
        ]
    });

    dupont5Chart.series[0].setData(earningAfterTaxParentCompanyToEBTData);
    dupont5Chart.series[1].setData(earningAfterTaxToEBTData);
    dupont5Chart.series[2].setData(earningBeforeTaxToEBITData);
    dupont5Chart.series[3].setData(eBITMarginData);
    dupont5Chart.series[4].setData(averageTotalAssetTurnoverData);
    dupont5Chart.series[5].setData(averageFinancialLeverageData);
    dupont5Chart.series[6].setData(roeaData);
});