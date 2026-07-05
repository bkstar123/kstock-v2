$(document).ready(function () {
    var liquidityChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'liquidity-container'
        },
        title: {
            text: 'Các hệ số thanh toán'
        },
        subtitle: {
            text: 'Liquidity/Solvency Ratios'
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
                name: 'Hệ số thanh toán tổng quát (Lần)',
                data: [],
                zones: [
                    {
                        value: 1,
                        dashStyle: 'dot'
                    }, 
                ]
            },
            {
                name: 'Hệ số thanh toán hiện hành (Lần)',
                data: [],
                zones: [
                    {
                        value: 1,
                        dashStyle: 'dot'
                    }, 
                ]
            },
            {
                name: 'Hệ số thanh toán nhanh (giảm trừ hàng tồn kho - Lần)',
                data: [],
                zones: [
                    {
                        value: 0.5,
                        dashStyle: 'dot'
                    }, 
                ]
            },
            {
                name: 'Hệ số thanh toán nhanh (giảm trừ hàng tồn kho và các khoản phải thu - Lần)',
                data: [],
                zones: [
                    {
                        value: 0.5,
                        dashStyle: 'dot'
                    }, 
                ]
            },
            {
                name: 'Hệ số thanh toán tiền mặt (Lần)',
                data: [],
                zones: [
                    {
                        value: 0.5,
                        dashStyle: 'dot'
                    }, 
                ]
            },
        ]
    });

    var interestCoverageRatioChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'interest-coverage-ratio-container'
        },
        title: {
            text: 'Hệ số chi trả lãi vay'
        },
        subtitle: {
            text: 'Interest Coverage Ratio'
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
                name: 'Hệ số chi trả lãi vay (Lần)',
                data: [],
                zones: [
                    {
                        value: 2,
                        dashStyle: 'dot'
                    }, 
                ]
            },
        ]
    });

    liquidityChart.series[0].setData(overallSolvencyRatioData);
    liquidityChart.series[1].setData(currentRatioData);
    liquidityChart.series[2].setData(quickRatioData);
    liquidityChart.series[3].setData(quickRatio2Data);
    liquidityChart.series[4].setData(cashRatioData);
    interestCoverageRatioChart.series[0].setData(interestCoverageRatioData);
});