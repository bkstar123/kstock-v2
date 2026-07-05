$(document).ready(function () {
    var revenueChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'income-statement-container'
        },
        title: {
            text: 'Doanh thu, lợi nhuận và chi phí'
        },
        subtitle: {
            text: 'Revenue, profits and expenses'
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
                    text: 'Tỷ VND'
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
                name: 'Doanh thu thuần (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Giá vốn bán hàng (Tỷ VND)',
                yAxis: 0,
                color: '#26823e',
                data: []
            },
            {
                name: 'Lợi nhuận gộp',
                yAxis: 0,
                data: []
            },
            {
                name: 'Doanh thu tài chính (Tỷ VND)',
                yAxis: 0,
                color: '#77aab5',
                data: []
            },
            {
                name: 'Chi phí tài chính (bao gồm chi phí lãi vay - Tỷ VND)',
                yAxis: 0,
                color: '#811d8c',
                data: []
            },
            {
                name: 'Chi phí lãi vay (Tỷ VND)',
                yAxis: 0,
                color: '#BF00FF',
                data: []
            },
            {
                name: 'Chi phí bán hàng (Tỷ VND)',
                yAxis: 0,
                color: '#a38d0f',
                data: []
            },
            {
                name: 'Chi phí quản lý doanh nghiệp (Tỷ VND)',
                yAxis: 0,
                color: '#cf650e',
                data: []
            },
            {
                name: 'Lợi nhuận trước thuế (Tỷ VND)',
                yAxis: 0,
                color: '#1b4cd1',
                data: []
            },
            {
                name: 'LNST của cổ đông công ty mẹ (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Biên lợi nhuận gộp (GPM - %)',
                yAxis: 1,
                color: '#154aeb',
                type: 'spline',
                data: []
            },
            {
                name: 'Tỷ suất lợi nhuận ròng của cổ đông công ty mẹ (ROS - %)',
                yAxis: 1,
                type: 'spline',
                color: '#00f04c',
                data: []
            },
            {
                name: 'Lợi nhuận thuần từ HĐKD / LNTT (%)',
                yAxis: 1,
                type: 'spline',
                color: '#dca3e6',
                data: []
            },
            {
                name: 'Chi phí khác (Tỷ VND)',
                yAxis: 0,
                color: '#99ccff',
                data: []
            },
            {
                name: 'Lợi nhuận khác (đã khấu trừ chi phí khác - Tỷ VND)',
                yAxis: 0,
                color: '#3333ff',
                data: []
            },
            {
                name: 'Chi phí thuế thu nhập doanh nghiệp (Tỷ VND)',
                yAxis: 0,
                color: '#ffb3b3',
                data: []
            },
        ]
    });

    var costStructureChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'cost-structure-container'
        },
        title: {
            text: 'Cấu trúc chi phí'
        },
        subtitle: {
            text: 'Cost structure'
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
        ],
        series: [
            {
                name: 'Giá vốn bán hàng / Doanh thu thuần (%)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Chi phí bán hàng / Doanh thu thuần (%)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Chi phí quản lý doanh nghiệp / doanh thu thuần (%)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Chi phí lãi vay / doanh thu thuần (%)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Chi phí bán hàng & Chi phí QLDN / Lợi nhuận gộp (%)',
                yAxis: 0,
                data: []
            }
        ]
    });

    revenueChart.series[0].setData(revenueData);
    revenueChart.series[1].setData(cogsData);
    revenueChart.series[2].setData(grossProfitData);
    revenueChart.series[3].setData(financialRevenueData);
    revenueChart.series[4].setData(financialExpenseData);
    revenueChart.series[5].setData(interestExpenseData);
    revenueChart.series[6].setData(sellingExpenseData);
    revenueChart.series[7].setData(generalAdminExpenseData);
    revenueChart.series[8].setData(eBTData);
    revenueChart.series[9].setData(earningsAfterTaxParentCompanyData);
    revenueChart.series[10].setData(gpmData);
    revenueChart.series[11].setData(ros2Data);
    revenueChart.series[12].setData(operatingProfitToEBTData);
    revenueChart.series[13].setData(otherExpenseData);
    revenueChart.series[14].setData(otherProfitData);
    revenueChart.series[15].setData(taxData);

    costStructureChart.series[0].setData(cogsToRevenueData);
    costStructureChart.series[1].setData(sellingExpenseToRevenueData);
    costStructureChart.series[2].setData(adminExpenseToRevenueData);
    costStructureChart.series[3].setData(interestCostToRevenueData);
    costStructureChart.series[4].setData(selllingEnterpriseManagementExpenseToGrossProfitData);
});