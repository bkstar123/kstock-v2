$(document).ready(function () {
    var cfoToCapexChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'cfo-to-capex-container'
        },
        title: {
            text: ' Dòng tiền thuần hoạt động kinh doanh / CAPEX'
        },
        subtitle: {
            text: 'CFO/CAPEX'
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
                text: 'Lần'
            },
            crosshair: true,
        },
        series: [
            {
                name: 'CFO/CAPEX (Lần)',
                data: [],
                zones: [
                    {
                        value: 1,
                        dashStyle: 'dot',
                    }, 
                ]
            },
        ]
    });
    var capexToNetProfitChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'capex-to-net-profit-container'
        },
        title: {
            text: ' CAPEX / Lợi nhuận ròng'
        },
        subtitle: {
            text: 'CFO/Net Profit'
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
                name: 'CAPEX/Lợi nhuận ròng (%)',
                data: []
            },
        ]
    });
    cfoToCapexChart.series[0].setData(cfoToCapex);
    capexToNetProfitChart.series[0].setData(capexToNetProfitData);
});