/**
 * Created by andrey on 03.04.2017.
 */
$( document ).ready(function() {

    app = new Vue({
        el: '#app',
        data: {
            menu:[{label:'Main',isActive:true},
                  {label:'Current state',isActive:false},
                  {label:'Statistics',isActive:false},
                  {label:'Setup', isActive:false},
                  {label:'DEBUG', isActive:false}

            ],
            debug_menu:[{label:'Can Init',isActive:false,isDisabled:false,cmd:'can_init'},
                {label:'AUTO RENEW',isActive:false,isDisabled:false,cmd:'auto_renew'},
                {label:'AUTO STOP',isActive:false,isDisabled:false,cmd:'auto_stop'},
                {label:'INVERTER RENEW', isActive:false,isDisabled:false,cmd:'inverter_renew'},
                {label:'ENGINE RENEW', isActive:false,isDisabled:false,cmd:'engine_renew'},
                {label:'BATTERY RENEW', isActive:false,isDisabled:false,cmd:'battery_renew'}

            ],
            menuActiveIndex:0,
            error_mes:"",
            error_color:'color-green',//color-green,color-yellow
            power_url:'params/plug.php',
            power_pressed:false,
            power_str:"OFF",
            engine_str:"OFF",
            g_pict:{tank:{x1:0,y1:0},batarey:{x1:0,y1:0},engine:{text_x:0,text_y:0}},
            g_pict_dimension:[240,320,370,768],
            g_pict_font:'32px arial',
            cur_pict_width:320,
            generator_name:'Mountain Cabin',
            generator_name_title:'Mountain Cabin',
            today:calcToday(),
            isControlActive:false,
            isStatLoading:false,
            intervalID:0,
            statLoadingValue:0,
            motor_state:0,
            motor_state_str:'OFF',
            message: 'Mountaine Cabine',
            tank_fuel_ps:100,
            tank_top_fuel_ps:100,
            engine_params:[['Current Load',0.3,'kW','cur_load'],
                           ['System Temperature',38,'C\u00B0','sys_temp'],
                           ['Engine Temperature',85,'C\u00B0','eng_temp'],
                           ['Next Maintenance',28,'day','mainten'],
            ],
            fuel_params:[
                ['Total kW*h consumed',0.5,'kW'],
                ['Total fuel consumed',18,'liter'],
                ['Average kW*h per day',0.5,'kW'],
                ['Average fuel per day',1.8,'liter'],
            ]
        },
        methods: {
            menuActive:function(index) {
                $("#menu_modal").removeClass('is-active');
                this.menuActiveIndex=index;
                for (var i=0; i< this.menu.length;i++ ) {
                    if (i == index) {
                        this.menu[i].isActive=true;
                    } else {
                        this.menu[i].isActive=false;
                    }
                }
                if (index==2) {//--controls
                    initCharts();
                }
                if (index==3) {//--controls
                    initToday();
                }
                console.log(index);
            },
            debug_menuActive:function(index) {
                for (var i=0; i< this.debug_menu.length;i++ ) {
                    if (i == index) {
                        this.debug_menu[i].isActive=true;
                        this.debug_menu[i].isDisabled=false;
                        this.send_debug_cmd(i);
                    } else {
                        this.debug_menu[i].isActive=false;
                        this.debug_menu[i].isDisabled=true;
                    }
                }
            },
            send_debug_cmd:function(index) {
              var self=this;
              console.log('send '+ self.debug_menu[index].cmd);
              setTimeout(function(){
                  for (var i=0; i< self.debug_menu.length;i++ ) {
                      self.debug_menu[i].isActive=false;
                      self.debug_menu[i].isDisabled=false;
                  }
              },3000);
            },
            btnMenuClickOpen:function() {
                $("#menu_modal").addClass('is-active');
            },
            btnMenuClickClose:function() {
                $("#menu_modal").removeClass('is-active');
            },
            btnFillTank_top: function () {
                //this.message = this.message.split('').reverse().join('')
                alert(this.tank_top_fuel_ps);
                showFillTank_top(this.tank_top_fuel_ps);
            },
            btnFillRemove:function() {
                $("#"+tank_top_fill.attr('id')).remove();
            },
            btnMotorClick:function() {
                if (this.motor_state==0) {
                    this.motor_state=1;
                    this.motor_state_str='ON';
                } else {
                    this.motor_state=0;
                    this.motor_state_str='OFF';
                }
            },
            setPower:function() {
                this.power_pressed = true;

                if (this.power_str =="OFF") {
                    this.power_str ="ON";
                } else {
                    this.power_str ="OFF";
                }
                $.ajax({
                    type: "POST",
                    url: app.$data.power_url,
                    data: {power:((this.power_str=='ON')?1:0)},
                    success: function(res){
                        console.log('(success)Power action return:',res);
                        if (res.state=='done') {
                            app.$data.power_pressed = false;
                            app.$data.error_color = 'color-green';
                            app.$data.error_mes='';
                        } else {
                            app.$data.error_color = 'color-yellow';
                            app.$data.error_mes=res.mes;
                        }
                    },
                    fail:function(res){
                        console.log('(fail)Power action return:',res);
                        app.$data.power_pressed = false;
                        app.$data.error_color = 'color-red';
                        app.$data.error_mes = 'Power setup error!';
                    },
                    dataType: 'json'
                });

                console.log("Power is:"+this.power_str);


            },
            saveControls:function() {
                this.generator_name_title=this.generator_name;
            }
        }
    });


    console.log('Screen width:'+$(window).width());
    initAllTank();
    initGetParams();
    //initTank_top();
    //showViewTank_top();
    //showFillTank_top(40);
    //initTank();
    //showViewTank();
    //showFillTank(50);
});
function initGetParams() {
    window.onmessage = function(e) {
        console.log("Try to set params e.data="+e.data);
        //var cmd = JSON.parse(e.data);
        cmd = (e.data);
        console.log("cmd=",cmd);
        if (app.$data.power_str != cmd.power) { //---power---
            app.setPower(cmd.power)
        }
        if (cmd.hasOwnProperty('tank')) { //---tank---
            fillTank(cmd.tank)
        }
        if (cmd.hasOwnProperty('batarey')) { //---batarey---
            fillBatarey(cmd.batarey)
        }
        if (cmd.hasOwnProperty('engine')) { //---engine---
            drawEngine(cmd.engine)
        }
        for (var i=0;i<app.$data.engine_params.length;i++) { //---engine parameters---
            var prop_name = app.$data.engine_params[i][3];
            if (cmd.hasOwnProperty(prop_name)) {
                app.$data.engine_params[i][1] = cmd[prop_name];
                $("#e-params-"+i).html(cmd[prop_name]);
            }
        }

    }
}
function setAllTankPictWidth() {
    var cur_screen_width= $(window).width();
    var  cur_width =app.$data.g_pict_dimension[0];
    for (var i=0;i<app.$data.g_pict_dimension.length;i++) {
        if (cur_screen_width > app.$data.g_pict_dimension[i]) {
            cur_width =app.$data.g_pict_dimension[i]
        }

    }
    return cur_width;
}
function calcToday() {
    var now = new Date();
    var today_str=now.toLocaleDateString()+" "+now.toLocaleTimeString();//now.toLocaleFormat();
    //var today_str=jQuery('#datetimepicker').val();
    //year=now.getFullYear();
    //month=now.getMonth();
    //day=now.getDay();
    return today_str;
}
function initToday() {
    jQuery('#datetimepicker').datetimepicker(
        {
            format:'d.m.Y H:i'
        }
    );
    //jQuery('#datetimepicker').val(new Date());
    console.info("DateTime init....");
};
function initTank_top() {
    console.log('InitTank_top....');
    draw_tank_top = SVG('tank_top').size(300, 200);
    x1=50;
    y1=0;
    h=180;
    l=200;
    l_skos=20;
    otstup=20;
    h_vistup=20;
}
function showViewTank_top() {

    //var rect = draw.rect(200, 100).attr({ fill: '#1A640C' });

    kontur=[[x1,y1],[x1+(l/2),y1],
        [x1+(l/2)+otstup,y1+otstup],
        [x1+(l/2)+(2*otstup),y1+otstup],
        [x1+(l/2)+(2*otstup),y1+(otstup/2)],
        [x1+(l/2)+(3*otstup),y1+(otstup/2)],
        [x1+(l/2)+(3*otstup),y1+(otstup)],
        [x1+(l/2)+(4*otstup),y1+(otstup)],
        [x1+(l/2)+(4*otstup),y1+h],
        [x1-otstup,y1+h],
        [x1-otstup,y1+(otstup)],
        [x1,y1],

    ];

    tank_top = draw_tank_top.polyline([kontur]).fill('none').stroke({ width: 4 });

}
function showFillTank_top(ps) {
    console.log('showFillTank_top '+ps);
    var fill_padding=4;
    var ps_padding=(h/100)*ps;//--смешения для процентов

    var fill_x1=x1-otstup+fill_padding;
    var fill_y1=y1+otstup+1+(h-ps_padding);

    var fill_width=l-fill_padding*2;
    var fill_h=ps_padding-otstup-1;
    //if (tank_top_fill.length > 0){
      // $("#"+tank_top_fill.attr('id')).remove();
    //  document.getElementById(tank_top_fill.attr('id')).remove()
    //}
    tank_top_fill = draw_tank_top.rect(fill_width,fill_h).fill('green').move(fill_x1,fill_y1);
    console.log('tank_top_fill='+tank_top_fill);
    //---show text-----
    draw_tank_top.text(ps.toString()+" %").move(x1+l/6,y1+2);
}
//---------------------------
function initTank() {
    draw_tank = SVG('tank').size(300, 300);
    tank_width=160;
    tank_h=200;
}
function showViewTank() {
    tank_x1=0;
    tank_y1=0;
    draw_tank.rect(tank_width/2,tank_h/6).fill('none').stroke({ width: 4 }).move(tank_x1+tank_width/4,tank_y1);
    draw_tank.rect(tank_width,tank_h).fill('none').radius(10).stroke({ width: 4 }).move(tank_x1,tank_y1+tank_h/6);

}
function showFillTank(ps){
    console.log('showFillTank '+ps);
    var gorlovina_h=tank_h/6;
    var fill_padding=4;
    var ps_padding=(tank_h/100)*ps;//--смешения для процентов

    var fill_x1=tank_x1+fill_padding;
    var fill_y1=tank_y1+gorlovina_h+(tank_h-ps_padding)+1;

    var fill_width=tank_width-fill_padding*2;
    var fill_h=tank_y1+ps_padding-2;
    //if (tank_top_fill != undefined){
    //    $("#"+tank_top_fill.attr('id')).remove();
    //}
    tank_fill = draw_tank.rect(fill_width,fill_h).fill('green').move(fill_x1,fill_y1);
    //---show text-----
    draw_tank.text(ps.toString()+" %").move(tank_x1+tank_width/2.5,y1+tank_h/2);
}
//====================================================================================
function initAllTank() {
    console.log('InitAllTank...');
    cur_pict_width = setAllTankPictWidth();
    console.log('Set pict width:'+cur_pict_width);
    //--correcting pict----
    // $("#tank").width(cur_pict_width);
    // $("#tank").height(cur_pict_width);
    // $("#g_all_img").attr('src','images/g_all_one_'+cur_pict_width+'.png');
    // $("#g_all_img").width(cur_pict_width);
    // $("#g_all_img").height(cur_pict_width);

    //canvas.addEventListener("mousedown", getPosition, false);
    //canvas.addEventListener('mousedown', function(evt) {
    //    var mousePos = getMousePos(canvas, evt);
    //    var message = 'Mouse position: ' + mousePos.x + ',' + mousePos.y;
    //    console.log(message);
    //    if (clickOnEngine(mousePos)==true) {
    //        if (app.$data.engine_str =='ON'){
    //            drawEngine('OFF');
    //            app.$data.engine_str='OFF';
    //        } else {
    //            drawEngine('ON');
    //            app.$data.engine_str='ON';
    //        }
    //
    //    }
    //}, false);
    var ctx = document.getElementById('tank').getContext('2d');
    var image = document.getElementById('g_all_img');
    ctx.drawImage(image, 0,0);
    initGPictKoord();
    fillTank(40);
    fillBatarey(70);
    //var img = new Image();
    //img.src = 'images/g_all_one.png';
    //img.addEventListener("load", function() {
    //    ctx.drawImage(img,0,0);
    //    console.log('draw img....')
    //}, false);

}
function getMousePos(canvas, evt) {
    var rect = canvas.getBoundingClientRect();
    return {
        x: evt.clientX - rect.left,
        y: evt.clientY - rect.top
    };
}



function clickOnEngine(mousePos){
    var x1=$("#tank").width()/ 2,  x2=$("#tank").width()-5;
    var y1=$("#tank").height()/ 2, y2=$("#tank").height()-5;
    if ((mousePos.x > x1)&&(mousePos.x < x2)) {
        if ((mousePos.y > y1)&&(mousePos.y < y2)) {
            return true;
        }
    }
    return false;
}
function drawEngine(state_str){
    var canvas = document.getElementById('tank');
    var ctx = document.getElementById('tank').getContext('2d');
    //---clear---
    clearFill('engine');

    var img = new Image();
    img.src = 'images/motor_'+app.$data.cur_pict_width+'_'+state_str+'.png';
    img.addEventListener("load", function() {
        ctx.drawImage(img,img.width,img.height);
        //---text ON/OFF----
        ctx.fillStyle = 'black';
        ctx.font = app.$data.g_pict_font;
        ctx.fillText(state_str, app.$data.g_pict.engine.text_x,  app.$data.g_pict.engine.text_y);

        console.log('draw motor img....')
    }, false);
}
function initGPictKoord() {
    app.$data.g_pict.tank.x1 = $("#tank").width()*0.02;
    app.$data.g_pict.tank.y1 = $("#tank").height()*0.12;
    app.$data.g_pict.tank.width  = $("#tank").width()*0.96;
    app.$data.g_pict.tank.height = $("#tank").height()*0.15;

    app.$data.g_pict.tank.text_x = $("#tank").width()*0.42;
    app.$data.g_pict.tank.text_y = $("#tank").height()*0.19;

    app.$data.g_pict.batarey.x1 = $("#tank").width()*0.085;
    app.$data.g_pict.batarey.y1 = ($("#tank").height()*0.45);
    app.$data.g_pict.batarey.width  = ($("#tank").width() *0.33);
    app.$data.g_pict.batarey.height = $("#tank").height()*0.46;

    app.$data.g_pict.batarey.text_x = $("#tank").width()*0.15;
    app.$data.g_pict.batarey.text_y = $("#tank").height()*0.65;

    app.$data.g_pict.engine.text_x = $("#tank").width()*0.62;
    app.$data.g_pict.engine.text_y = $("#tank").height()*0.53;
}
function clearFill(what) {
    var ctx = document.getElementById('tank').getContext('2d');
    if (what=='tank') {
        //---clear---
        ctx.fillStyle = 'white';
        ctx.fillRect(app.$data.g_pict.tank.x1-1,  app.$data.g_pict.tank.y1-1,  app.$data.g_pict.tank.width+1,   app.$data.g_pict.tank.height);
    }
    if (what == 'batarey') {
        ctx.fillStyle = 'white';
        ctx.fillRect(app.$data.g_pict.batarey.x1-1,  app.$data.g_pict.batarey.y1, app.$data.g_pict.batarey.width+1, app.$data.g_pict.batarey.height);
    }
    if (what == 'engine') {
        ctx.fillStyle = 'white';
        engine_x1=$("#tank").width()*0.46;
        engine_y1=($("#tank").height()/2)-($("#tank").height()*0.20);
        engine_width = ($("#tank").width()*0.52);
        engine_hight = 10+$("#tank").height()/2;
        ctx.fillRect(engine_x1, engine_y1, engine_width, engine_hight);
    }
}
function fillTank(ps) {
    if ((ps < 0) || (ps > 100)) return;
    var ctx = document.getElementById('tank').getContext('2d');
    //---clear---
    clearFill('tank');
    ctx.fillStyle = 'white';

    //--plot-----
    ctx.fillStyle = '#70AC36';
    x100=app.$data.g_pict.tank.x1;
    y100=app.$data.g_pict.tank.y1;
    //y_bottom=y100+app.$data.g_pict.tank.height;
    y_ps=y100+((app.$data.g_pict.tank.height-(app.$data.g_pict.tank.height*ps)/100));
    h_ps=ps*app.$data.g_pict.tank.height/100;

    ctx.fillRect(x100, y_ps, app.$data.g_pict.tank.width, h_ps);
    //---text ps----
    ctx.fillStyle = 'black';
    ctx.font = app.$data.g_pict_font;
    ctx.fillText(ps.toString()+' %',app.$data.g_pict.tank.text_x, app.$data.g_pict.tank.text_y);
}
function fillBatarey(ps){
    if ((ps < 0) || (ps > 100)) return;
    var ctx = document.getElementById('tank').getContext('2d');
    //---clear---
    clearFill('batarey');
    //--plot-----
    ctx.fillStyle = '#70AC36';
    x100=app.$data.g_pict.batarey.x1;
    y100=app.$data.g_pict.batarey.y1;
    y_ps=y100+((app.$data.g_pict.batarey.height-(app.$data.g_pict.batarey.height*ps)/100));
    h_ps=ps*app.$data.g_pict.batarey.height/100;

    ctx.fillRect(x100, y_ps, app.$data.g_pict.batarey.width, h_ps);
    //---text ps----
    ctx.fillStyle = 'black';
    ctx.font = app.$data.g_pict_font;//'32px serif';
    ctx.fillText(ps.toString()+' %', app.$data.g_pict.batarey.text_x,  app.$data.g_pict.batarey.text_y);
}