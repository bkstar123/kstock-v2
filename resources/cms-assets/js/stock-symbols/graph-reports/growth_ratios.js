$(document).ready(function () {
    var growthQoQChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'growthQoQ-container'
        },
        title: {
            text: 'Các chỉ số tăng trưởng so với quý liền kề (QoQ)'
        },
        subtitle: {
            text: 'Growth ratios (QoQ)'
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
                name: 'Tăng trưởng doanh thu thuần QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng hàng tồn kho QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng giá vốn bán hàng QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng lợi nhuận gộp QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng chi phí hoạt động QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng chi phí lãi vay QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng lợi nhuận trước thuế QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng tổng tài sản QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng nợ dài hạn QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng nợ phải trả QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng nợ vay QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng vốn điều lệ QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng VCSH QoQ (%)',
                data: []
            },
            {
                name: 'Tăng trưởng dòng tiền tự do (FCF) QoQ (%)',
                data: []
            },
        ]
    });
    growthQoQChart.series[0].setData(revenueGrowthQoQData);
    growthQoQChart.series[1].setData(inventoryGrowthQoQData);
    growthQoQChart.series[2].setData(cogsGrowthQoQData);
    growthQoQChart.series[3].setData(grossProfitGrowthQoQData);
    growthQoQChart.series[4].setData(operatingExpenseGrowthQoQData);
    growthQoQChart.series[5].setData(interestExpenseGrowthQoQData);
    growthQoQChart.series[6].setData(eBTGrowthQoQData);
    growthQoQChart.series[7].setData(netProfitOfParentShareHolderGrowthQoQData);
    growthQoQChart.series[8].setData(totalAssetsGrowthQoQData);
    growthQoQChart.series[9].setData(longTermLiabilityGrowthQoQData);
    growthQoQChart.series[10].setData(liabilityGrowthQoQData);
    growthQoQChart.series[11].setData(debtGrowthQoQData);
    growthQoQChart.series[12].setData(charterCapitalGrowthQoQData);
    growthQoQChart.series[13].setData(equityGrowthQoQData);
    growthQoQChart.series[14].setData(fcfGrowthQoQData);

    var growthYoYChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'growthYoY-container'
        },
        title: {
            text: 'Các chỉ số tăng trưởng so với cùng kỳ năm trước (YoY)'
        },
        subtitle: {
            text: 'Growth ratios (YoY)'
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
                name: 'Tăng trưởng doanh thu thuần YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng hàng tồn kho YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng giá vốn bán hàng YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng lợi nhuận gộp YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng chi phí hoạt động YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng chi phí lãi vay YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng lợi nhuận trước thuế YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng lợi nhuận sau thuế của cổ đông công ty mẹ YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng tổng tài sản YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng nợ dài hạn YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng nợ phải trả YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng nợ vay YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng vốn điều lệ YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng VCSH YoY (%)',
                data: []
            },
            {
                name: 'Tăng trưởng dòng tiền tự do (FCF) YoY (%)',
                data: []
            },
        ]
    });
    growthYoYChart.series[0].setData(revenueGrowthYoYData);
    growthYoYChart.series[1].setData(inventoryGrowthYoYData);
    growthYoYChart.series[2].setData(cogsGrowthYoYData);
    growthYoYChart.series[3].setData(grossProfitGrowthYoYData);
    growthYoYChart.series[4].setData(operatingExpenseGrowthYoYData);
    growthYoYChart.series[5].setData(interestExpenseGrowthYoYData);
    growthYoYChart.series[6].setData(eBTGrowthYoYData);
    growthYoYChart.series[7].setData(netProfitOfParentShareHolderGrowthYoYData);
    growthYoYChart.series[8].setData(totalAssetsGrowthYoYData);
    growthYoYChart.series[9].setData(longTermLiabilityGrowthYoYData);
    growthYoYChart.series[10].setData(liabilityGrowthYoYData);
    growthYoYChart.series[11].setData(debtGrowthYoYData);
    growthYoYChart.series[12].setData(charterCapitalGrowthYoYData);
    growthYoYChart.series[13].setData(equityGrowthYoYData);
    growthYoYChart.series[14].setData(fcfGrowthYoYData);
});