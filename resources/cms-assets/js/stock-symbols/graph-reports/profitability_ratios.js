$(document).ready(function () {
    var roaaChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'roaa-container'
        },
        title: {
            text: 'Tỷ suất lợi nhuận trên tổng tài sản bình quân'
        },
        subtitle: {
            text: 'ROAA'
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
                name: 'Tỷ suất lợi nhuận trên tổng tài sản bình quân (ROAA - %)',
                data: []
            },
            {
                name: 'Tỷ suất lợi nhuận trên tổng tài sản (ROA - %)',
                data: []
            }
        ]
    });
    var roeaChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'roea-container'
        },
        title: {
            text: 'Tỷ suất lợi nhuận trên vốn chủ sở hữu bình quân'
        },
        subtitle: {
            text: 'ROEA'
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
                name: 'Tỷ suất lợi nhuận trên vốn chủ sở hữu bình quân (ROEA - %)',
                data: []
            },
            {
                name: 'Tỷ suất lợi nhuận trên vốn chủ sở hữu (ROE - %)',
                data: []
            }
        ]
    });
    var rosChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'ros-container'
        },
        title: {
            text: 'Tỷ suất lợi nhuận ròng của cổ đông công ty mẹ'
        },
        subtitle: {
            text: 'ROS'
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
                name: 'Tỷ suất lợi nhuận ròng của cổ đông công ty mẹ (ROS - %)',
                data: []
            },
            {
                name: 'Tỷ suất lợi nhuận sau thuế thu nhập doanh nghiệp (%)',
                data: []
            }
        ]
    });
    var gpmChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'gpm-container'
        },
        title: {
            text: 'Biên lợi nhuận gộp'
        },
        subtitle: {
            text: 'GPM'
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
                name: 'Biên lợi nhuận gộp (GPM - %)',
                data: []
            }
        ]
    });
    var rotaChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'rota-container'
        },
        title: {
            text: 'Tỷ suất lợi nhuận trước thuế và lãi vay trên tổng tài sản bình quân'
        },
        subtitle: {
            text: 'ROTA'
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
                name: 'Tỷ suất lợi nhuận trước thuế và lãi vay trên tổng tài sản bình quân (ROTA - %)',
                data: []
            }
        ]
    });
    var ebitMarginChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'ebit-margin-container'
        },
        title: {
            text: 'Biên lợi nhuận trước thuế và lãi vay trên doanh thu thuần'
        },
        subtitle: {
            text: 'EBIT / Doanh thu thuần'
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
               name: 'Biên lợi nhuận trước thuế và lãi vay trên doanh thu thuần (%)',
               data: []
           }
       ]
    });
    var roceChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'roce-container'
        },
        title: {
            text: 'Tỷ suất lợi nhuận trên vốn dài hạn bình quân'
        },
        subtitle: {
            text: 'ROCE'
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
               name: 'Tỷ suất lợi nhuận trên vốn dài hạn bình quân (ROCE - %)',
               data: []
           }
        ]
    });
    var ebitdaChart = Highcharts.chart({
        chart: {
            type: 'spline',
            renderTo: 'ebitda-margin-container'
        },
        title: {
            text: 'Biên lợi nhuận trước thuế, lãi vay và khấu hao'
        },
        subtitle: {
            text: 'EBITDA Margin'
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
                name: 'Biên lợi nhuận trước thuế, lãi vay và khấu hao (tính theo bảng CĐKT và báo cáo kết quả HĐKD - %)',
                data: []
            },
            {
                name: 'Biên lợi nhuận trước thuế, lãi vay và khấu hao (tính theo báo cáo lưu chuyển tiền tệ - %)',
                data: []
            }
        ]
    });
    roaaChart.series[0].setData(roaaData);
    roaaChart.series[1].setData(roaData);
    roeaChart.series[0].setData(roeaData);
    roeaChart.series[1].setData(roeData);
    rosChart.series[0].setData(ros2Data);
    rosChart.series[1].setData(rosData);
    gpmChart.series[0].setData(gpmData);
    rotaChart.series[0].setData(rotaData);
    ebitMarginChart.series[0].setData(ebitMarginData);
    roceChart.series[0].setData(roceData);
    ebitdaChart.series[0].setData(ebitda1Data);
    ebitdaChart.series[1].setData(ebitda2Data);
});