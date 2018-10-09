/**
 * Created by andrey on 04.04.2017.
 */
$(function() {
    initCharts();
});

function initCharts() {
    console.log('Init charts........');
    //chartWidth=$(window).width()/3;
    //chartHeight=chartWidth;
    //$("#electroChart").width(chartWidth);
    //$("#electroChart").height(chartHeight);
    //$("#fuelChart").width(chartWidth);
    //$("#fuelChart").height(chartHeight);
    //----------------electro-------------
    ctxElectro = document.getElementById("electroChart");
    chartInstanceElectro = new Chart(ctxElectro, {
        type: 'line',
        data: {
            //labels: getMonthsArray(),
            datasets: [
                {
                    label: 'Chart',
                    fill: false,
                    lineTension: 0.1,
                    backgroundColor: "rgba(75,192,192,0.4)",
                    borderColor: "rgba(75,192,192,1)",
                    borderCapStyle: 'butt',
                    borderDash: [],
                    borderDashOffset: 0.0,
                    borderJoinStyle: 'miter',
                    pointBorderColor: "rgba(75,192,192,1)",
                    pointBackgroundColor: "#fff",
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(75,192,192,1)",
                    pointHoverBorderColor: "rgba(220,220,220,1)",
                    pointHoverBorderWidth: 2,
                    pointRadius: 1,
                    pointHitRadius: 10,
                    data: [7.5,2.8,4.9,8.2,9.1,9.5,10.0,7.6,8.7,1,5,7.8,8.2],
                    spanGaps: false
                }
            ]
        },
        options: {
            responsive: true
        }
    });
    //-----------------fuel------------------
    ctxFuel = document.getElementById("fuelChart");
    chartInstanceFuel = new Chart(ctxFuel, {
        type: 'line',
        //data: getLocalData('fuel'),
        data: {
            //labels: getMonthsArray(),
            datasets: [
                {
                    label: 'Chart',
                    fill: false,
                    lineTension: 0.1,
                    backgroundColor: "rgba(75,192,192,0.4)",
                    borderColor: "rgba(75,192,192,1)",
                    borderCapStyle: 'butt',
                    borderDash: [],
                    borderDashOffset: 0.0,
                    borderJoinStyle: 'miter',
                    pointBorderColor: "rgba(75,192,192,1)",
                    pointBackgroundColor: "#fff",
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(75,192,192,1)",
                    pointHoverBorderColor: "rgba(220,220,220,1)",
                    pointHoverBorderWidth: 2,
                    pointRadius: 1,
                    pointHitRadius: 10,
                    data: [1.5,7.8,2.9,3.2,9.1,9.5,10.0,8.6,8.7,7,5,7.8,8.2],
                    spanGaps: false
                }
            ]
        },
        options: {
            responsive: true
        }
    });
}
function getMonthsArray() {
    var months= ["January", "February", "March", "April", "May", "June", "July"];
    return months;
}
function getDataArray(chart_type) {
    var data=[];
    if (chart_type=='electro') {
        data=[7.5,7.8,7.9,8.2,9.1,9.5,10.0,8.6,8.7,7,5,7.8,8.2];
    }
    if (chart_type=='fuel') {
        data=[2,3,8,8,9,5,22,21,20,7,5,8,10];
    }
    return data;
}
function getLocalData(chart_type) {
    var label='Chart';
    if (chart_type=='electro') {
        label='Electricity kW';
    }
    if (chart_type=='fuel') {
        label='Fuel,liter';
    }
    var data = {
        labels: getMonthsArray(),
        datasets: [
            {
                label: label,
                fill: false,
                lineTension: 0.1,
                backgroundColor: "rgba(75,192,192,0.4)",
                borderColor: "rgba(75,192,192,1)",
                borderCapStyle: 'butt',
                borderDash: [],
                borderDashOffset: 0.0,
                borderJoinStyle: 'miter',
                pointBorderColor: "rgba(75,192,192,1)",
                pointBackgroundColor: "#fff",
                pointBorderWidth: 1,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: "rgba(75,192,192,1)",
                pointHoverBorderColor: "rgba(220,220,220,1)",
                pointHoverBorderWidth: 2,
                pointRadius: 1,
                pointHitRadius: 10,
                data: getDataArray(chart_type),
                spanGaps: false
            }
        ]
    };
    return data;
}
//-----------Stat-----------------------------------------------
Date.prototype.formatMMDDYYYY = function() {
    return (this.getMonth() + 1) +
        "/" +  this.getDate() +
        "/" +  this.getFullYear();
};
function getElectroStatData() {
    //--get electro data
    console.log("getElectroStatData...");
    $.ajax({
        url: '/params/chart_data_1.json',
        dataType: 'json',
    }).done(function (results) {
        var labels = [], data = [];
        results["points"].forEach(function (point) {
            //labels.push(new Date(packet.timestamp).formatMMDDYYYY());
            console.log('Readed data',point);
            labels.push(point.x);
            data.push(parseFloat(point.y));
        });
        chartInstanceElectro.data.labels=labels;//[1,2,3,4,5];
        chartInstanceElectro.data.datasets[0].data=data;//[1,2,3,4,5];
        chartInstanceElectro.update();
    });
};
function getFuelStatData() {
    //--get fuel data--
    console.log("getFuelStatData...");
    $.ajax({
        url: '/params/chart_data_2.json',
        dataType: 'json',
    }).done(function (results) {
        var labels = [], data = [];
        results["points"].forEach(function (point) {
            //labels.push(new Date(packet.timestamp).formatMMDDYYYY());
            console.log('Readed data',point);
            labels.push(point.x);
            data.push(parseFloat(point.y));
        });
        chartInstanceFuel.data.labels=labels;//[1,2,3,4,5];
        chartInstanceFuel.data.datasets[0].data=data;//[1,2,3,4,5];
        chartInstanceFuel.update();
    });
};
function getDataCharts() {
    app.$data.isStatLoading=true;
    console.log('getDataCharts...',app.$data.isStatLoading);

    app.$data.statLoadingValue=0;
    //----------send stat action---
    $.ajax({
        type: "POST",
        url: "params/plug.php",
        //timeout:10000,
        dataType: 'json',
        data: {stat:1,period:$("#stat_period option:selected").val()}}
        ).done(function(res){
            console.log('(success)Stat action return:',res);
            if (res.state=='done') {
                 getElectroStatData();
                 getFuelStatData();
            } else {
               console.log('(Error)Stat data...');
            }
        }
        ).fail(function(jqXHR, textStatus) {
        console.log('(fail)Stat action return:', textStatus);
    });
    app.$data.statLoadingValue=0;
    app.$data.intervalID=setInterval(function(){
        app.$data.statLoadingValue=app.$data.statLoadingValue+20;
        if (app.$data.statLoadingValue >100) {
            clearInterval(app.$data.intervalID)
            getFuelStatData();
            getElectroStatData();
            app.$data.isStatLoading=false;
        }

        }, 1000);



}