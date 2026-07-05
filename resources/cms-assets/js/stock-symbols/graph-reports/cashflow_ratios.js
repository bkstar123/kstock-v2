$(document).ready(function () {
    var cashflowChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'cash-flow-ratio-container'
        },
        title: {
            text: ' Hệ số dòng tiền hoạt động kinh doanh và dòng tiền tự do trên doanh thu thuần'
        },
        subtitle: {
            text: 'CFO/Revenue - FCF/Revenue'
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
                    text: 'FCF/CFO (%)'
                },
                crosshair: true,
                opposite: true
            }
        ],
        series: [
            {
                name: 'CFO/Doanh thu thuần (%)',
                data: [],
                yAxis: 0,
                zones: [
                    {
                        value: 0,
                        dashStyle: 'dot'
                    }, 
                ]
            },
            {
                name: 'FCF/Doanh thu thuần (%)',
                data: [],
                yAxis: 0,
                zones: [
                    {
                        value: 0,
                        dashStyle: 'dot'
                    }, 
                ]
            },
            {
                name: 'FCF/CFO (%)',
                data: [],
                yAxis: 1,
                type: 'spline',
                dataLabels: {
                    enabled: true
                },
                zones: [
                    {
                        value: 0,
                        dashStyle: 'dot'
                    }, 
                    {
                        value: 100,
                    }, 
                    {
                        dashStyle: 'dot'
                    }, 
                ]
            }
        ]
    });
    cashflowChart.series[0].setData(cfoToRevenueData);
    cashflowChart.series[1].setData(fcfToRevenueData);
    cashflowChart.series[2].setData(fcfToCfoData);
});