$(document).ready(function () {
    var cashflowsChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'cash-flows-statement-container'
        },
        title: {
            text: 'Lưu chuyển tiền thuần từ HĐKD, đầu tư và tài chính'
        },
        subtitle: {
            text: 'CFO, CFI, CFF'
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
        ],
        series: [
            {
                name: 'Luu chuyển tiền thuần từ hoạt động kinh doanh (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Luu chuyển tiền thuần từ hoạt động đầu tư (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Luu chuyển tiền thuần từ hoạt động tài chính (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Lưu chuyển tiền thuần trong kỳ (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Tiền và tương đương tiền cuối kỳ (Tỷ VND)',
                yAxis: 0,
                data: []
            },
        ]
    });

    var cfoCashflowsChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'cfo-cash-flows-container'
        },
        title: {
            text: 'Một số thành phần của dòng tiền HĐKD (CFO)'
        },
        subtitle: {
            text: 'CFO component cash flows'
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
        ],
        series: [
            {
                name: 'Khấu hao tài sản cố định (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: '(Lãi)/Lỗ chênh lệch tỷ giá hối đoái chưa thực hiện (Tỷ VND)',
                yAxis: 0,
                data: [],
            },
            {
                name: '(Lãi)/Lỗ từ hoạt động đầu tư (Tỷ VND)',
                yAxis: 0,
                data: [],
            },
            {
                name: 'Thay đổi các khoản phải thu (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Thay đổi hàng tồn kho (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Thay đổi các khoản phải trả (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Tiền lãi vay đã trả (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Thuế thu nhập doanh nghiệp đã nộp (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            
        ]
    });

    var cfiCashflowsChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'cfi-cash-flows-container'
        },
        title: {
            text: 'Một số thành phần của dòng tiền đầu tư (CFI)'
        },
        subtitle: {
            text: 'CFI components'
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
        ],
        series: [
            {
                name: 'Tiền chi để mua sắm, xây dựng TSCĐ và các tài sản dài hạn khác (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Tiền thu từ thanh lý, nhượng bán TSCĐ và các tài sản dài hạn khác (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Tiền chi cho vay, mua các công cụ nợ của đơn vị khác (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Tiền thu hồi cho vay, bán lại các công cụ nợ của các đơn vị khác (Tỷ VND)',
                yAxis: 0,
                data: []
            },
        ]
    });

    var cffCashflowsChart = Highcharts.chart({
        chart: {
            type: 'column',
            renderTo: 'cff-cash-flows-container'
        },
        title: {
            text: 'Một số thành phần của dòng tiền tài chính (CFF)'
        },
        subtitle: {
            text: 'CFF component cash flows'
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
        ],
        series: [
            {
                name: 'Tiền chi trả nợ gốc vay (Tỷ VND)',
                yAxis: 0,
                data: []
            },
            {
                name: 'Tiền vay ngắn hạn, dài hạn nhận được (Tỷ VND)',
                yAxis: 0,
                data: [],
            },
        ]
    });

    cashflowsChart.series[0].setData(cfoData);
    cashflowsChart.series[1].setData(cfiData);
    cashflowsChart.series[2].setData(cffData);
    cashflowsChart.series[3].setData(cashMovingData);
    cashflowsChart.series[4].setData(cashEndData);

    cfoCashflowsChart.series[0].setData(deprecationData);
    cfoCashflowsChart.series[1].setData(changeFromCurrencyConversionRateData);
    cfoCashflowsChart.series[2].setData(changeFromInvestingActivityData);
    cfoCashflowsChart.series[3].setData(receivableAccountChangenData);
    cfoCashflowsChart.series[4].setData(inventoryAccountChangenData);
    cfoCashflowsChart.series[5].setData(payableAccountChangenData);
    cfoCashflowsChart.series[6].setData(paidInterestData);
    cfoCashflowsChart.series[7].setData(paidTaxData);   

    cfiCashflowsChart.series[0].setData(payForCapexData); //CFI
    cfiCashflowsChart.series[1].setData(receiveFromCapexData); //CFI
    cfiCashflowsChart.series[2].setData(payForLoanToolData); //CFI
    cfiCashflowsChart.series[3].setData(receiveForLoanToolData); //CFI

    cffCashflowsChart.series[0].setData(payForDebtPrincipalData); //CFF
    cffCashflowsChart.series[1].setData(loanData); // CFF
});