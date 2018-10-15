<?php
define('CONSOLE',0);
define('AUTO',1);
define('WEB',2);
set_time_limit(0);
use function error_log as pr;
class Plug
{
    public $ver='4.11';
    public $cur_state=CONSOLE;

    public $plug_log    = __DIR__.'/plug_log.txt';
    private $file_online = __DIR__.'/online';
    private $file_params = __DIR__.'/params.json';
    private $params_arr = array(
        "power"=>"OFF",
        "tank"=>0,
        "batarey"=>0,
        "engine"=>"OFF",
        "cur_load"=>0.0,
        "sys_temp"=>0,
        "eng_temp"=>0,
        "mainten"=>1,
        "state"=>"run"
    );
    //--- protocol_name=>my params_name---
    private $params_protocol_map = array (
        "fuel_vol"     =>"tank",
        "state_on_off" =>"engine",
        "battery_lvl" =>"batarey",
        "power_on_off" =>"power",
        "eng_temp" =>"eng_temp",
        "eng_serv" =>"mainten",
        "inv_load" =>"cur_load",
    );
    private $socket_name ='/var/run/energo.soc';
    private $socket;
    private $socket_start_arr = array("\xF0","\x7F","\x01");
    private $socket_end_arr   = array("\xF7");
    //---b:statistics-vars---
    private $stat_file=__DIR__.'/chart_data';
    private $electro_stat_index=1;//---add to stat_file
    private $fuel_stat_index=2;//---add to stat_file
    private $cmd_stat   ="UID=WebStat:ou=Test1:parameterName=|p_param_name|:beginTime=|p_begin_value|Z:endTime=|p_end_value|Z;";
    private $stat_arr=[];//----[[x,y],[x,y],[x,y].....] array(array('y'=>10,'x'=>'Jan'),array('y'=>15,'x'=>'Feb'))
    private $query_cnt=96;//--day->24h/96=15min  24h/144=10min
    public $stat_period ='d';//---'d'->day,'w'->week,'m'->month
    private $beginStatTime='';//new DateTime('NOW');
    private $endStatTime='';//new DateTime('NOW');
    private $stat_var_map = array('Electro'=>'Inv_Load','Fuel'=>'Fuel_Con');
    //---e:statistics vars---
    private $cmd_set_str="UID=WebPage:ou=Test1:parameterName=|p_name|:value=|p_value|;";
    private $cmd = array(
        "stat-init" =>"UID=WebStat:ou=WebStat:description=Test web page:wr=FALSE:stat=FALSE:iface=SOCKET;",//--will done from $cmd_stat
        "stat-electro-rasxod" =>"UID=WebStat:ou=Test1:parameterName=Inv_Load:recverName=WebStat;",//--resxod electo energii
        "stat-fuel-rasxod" =>"UID=WebStat:ou=Test1:parameterName=Fuel_Con:recverName=WebStat;",//--resxod electo energii
        "stat-period" =>"UID=WebStat:ou=Test1:periodTime=|p_period|:parameterName=|p_param_name|:beginTime=|p_begin_value|Z:endTime=|p_end_value|Z",
        "stat" =>"UID=WebStat:ou=Test1:parameterName=|p_param_name|:beginTime=|p_begin_value|Z:endTime=|p_end_value|Z;",//--will done from $cmd_stat
        "init" =>"UID=WebPage:ou=WebPage:description=Web page:wr=FALSE:stat=FALSE:iface=SOCKET;",
        "fuel" =>"UID=WebPage:ou=Test1:parameterName=Fuel_Vol:recverName=WebPage;",
        "state" =>"UID=WebPage:ou=Test1:parameterName=State_On_Off:recverName=WebPage;",
        "tank" =>"",
        "batarey" =>"UID=WebPage:ou=Test1:parameterName=Battery_Lvl:recverName=WebPage;",
        "power" =>"UID=WebPage:ou=Test1:parameterName=Power_On_Off:recverName=WebPage;",
        "eng_temp" =>"UID=WebPage:ou=Test1:parameterName=Eng_Temp:recverName=WebPage;",
        "inv_load" =>"UID=WebPage:ou=Test1:parameterName=Inv_Load:recverName=WebPage;",
        "eng_serv" =>"UID=WebPage:ou=Test1:parameterName=Eng_Serv:recverName=WebPage;"
    );
    function __construct() {
       $this->cur_state=AUTO;
       $this->endStatTime=new DateTime('NOW');
       $this->clearStatVars();
    }
    //-------------METHODS---------------
    public function connect() {
        $objDT = new DateTime('NOW');
        //socket_close($this->socket);
        //$this->socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        $this->socket = fsockopen ("unix://".$this->socket_name,-1,$errno, $errstr,0.1);
        stream_set_timeout($this->socket, 0.2);
        if (!$this->socket) {
            pr("\n(ERROR) Socket open error:".$objDT->format('d-m-Y H:i')."  ".$errstr);
            return false;
        }
//        if (!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
//            //echo 'Не могу установить опцию на сокете: '. socket_strerror(socket_last_error()) . PHP_EOL;
//            pr("\n(ERROR)socket option failed:".socket_strerror(socket_last_error())." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
//        }
        pr("\n(!)binded:".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        return true;

    }
    public function  writeDebugCommand($cmd_name) {
        $objDT = new DateTime('NOW');
        $this->writeCommandsOne('init');
        try{
            switch ($cmd_name) {
                case 'can_init':
                    fwrite($this->socket,"\xF0\x7F\x01\xF7");
                    break;
                case 'auto_renew':
                    fwrite($this->socket,"\xF0\x7F\x02\xF7");
                    break;
                case 'auto_stop':
                    fwrite($this->socket,"\xF0\x7F\x03\xF7");
                    break;
                case 'inverter_renew':
                    fwrite($this->socket,"\xF0\x7F\x04495477710674FF52\xF7");
                    break;
                case 'engine_renew':
                    fwrite($this->socket,"\xF0\x7F\x0450498751066CFF49\xF7");
                    break;
                case 'battery_renew':
                    fwrite($this->socket,"\xF0\x7F\x0448464310000E0014\xF7");
                    break;
            }
            pr("\n(DEDUG command was written:".$cmd_name);
        }  catch (Exception $e) {
            pr("\n(ERROR-write)debug cmd: [$cmd_name]:".$e->getMessage()." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        }
    }

    public function  writeCommandValue($cmd_name,$cmd_val) {
        try {
            $objDT = new DateTime('NOW');
            fwrite($this->socket,"\xF0\x7F\x01");//--start str
            $search  = array('|p_name|', '|p_value|');
            $replace = array($cmd_name, $cmd_val);
            $cmd_real_str = str_replace($search, $replace, $this->cmd_set_str);
            fwrite($this->socket,$cmd_real_str);
            fwrite($this->socket,"\xF7");//--end str
            pr("\n(DATA-write)$this->cur_state cmd: [$cmd_name]:".$cmd_val." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        } catch (Exception $e) {
            pr("\n(ERROR-write)wrong cmd: [$cmd_name]:".$e->getMessage()." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        }
    }
    public function writeCommandsOne($cmd_name) {
        try {
            //---if stat--
            pr("\n(writeCommandsOne) cmd: [$cmd_name]");
            if (in_array($cmd_name,['stat'])) {
                fwrite($this->socket,"\xF0\x7E\x08");//--start str
                pr("\n(STAT cmd)writeCommandsOne:");
            } else {//--params---
                fwrite($this->socket,"\xF0\x7F\x01");//--param str
            }

            fwrite($this->socket,$this->cmd[$cmd_name]);
            fwrite($this->socket,"\xF7");//--end str
        } catch (Exception $e) {
            $objDT = new DateTime('NOW');
            pr("\n(ERROR)wrong cmd: [$cmd_name]:".$e->getMessage()." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        }

    }
    public function writeCommandsAll() {

        $this->writeCommandsOne('init');
//        $this->writeCommandsOne('fuel');
//        $this->writeCommandsOne('state');
//        $this->writeCommandsOne('eng_temp');
//        $this->writeCommandsOne('batarey');
//        $this->writeCommandsOne('power');
//        $this->writeCommandsOne('inv_load');
//        $this->writeCommandsOne('eng_serv');

        //$this->writeCommandsOne('engine_params');

    }
    public function makeParams($data) {
        pr("\n(DATA)read data:".$data,3,$this->plug_log);
        echo "\n (!)Have data:".$data;
        //---read json params---
        $this->getCurParams();
        //---razbor data and fill params_arr new values---
        $data_next=str_replace(',','&',strtolower($data));
        $pos_p_name = strpos($data_next,'parametername');
        $data_next=substr($data_next,$pos_p_name);
        pr("\n(DATA)next data:".$data_next.' >> pos_p_name='.$pos_p_name,3,$this->plug_log);
        parse_str($data_next,$res);
        pr("\n(DATA)after parse:".var_export($res,true),3,$this->plug_log);
        $param_name=$res['parametername'];
        if ( $param_name =='') {
            pr("\n(DATA-ERROR) NO parametername after parse:".$param_name." = ",3,$this->plug_log);
            echo "\n(DATA-ERROR) NO parametername ";
            return;
        }
        $param_value=$res['dc'];//---local:value=123----
        if (strpos($param_value, 'value') != false) {
            $res=explode('value=',$param_value);
            $param_value=$res[1];

        }
        //---!!! START CORRECT VALUE ACCORDING MY DATA: 0-"OFF" 1-"ON"
        if (in_array($param_name,array('power_on_off','state_on_off'))) {
            if (intval($param_value)==0) {
                $param_value="OFF";
            } else {
                $param_value="ON";
            }
        } else {//--digital values---
            //---inv_load   cur_load---
            if (in_array($param_name,array('inv_load'))) {
                $param_value=(String)(0.1*intval($param_value));
            }
            //if (strlen($param_value)>2) {
            if (intval($param_value)>100) {
                $param_value=substr($param_value,0,2);
                pr("\n(DATA-NOT-CORRECT)after parse VALUE:".$param_name." = ".$param_value,3,$this->plug_log);
            }

        }
        //---!!! END CORRECT VALUE ACCORDING MY DATA: 0-"OFF"
        pr("\n(DATA)after parse VALUE:".$param_name." = ".$param_value,3,$this->plug_log);
        //---power--check if big----

        try {
            $my_param_name=$this->params_protocol_map[$param_name];

            $this->params_arr[$my_param_name]=$param_value;
            //---write data
            $params_new_str=json_encode($this->params_arr);
            file_put_contents($this->file_params, $params_new_str);
            pr("\n(DATA)written NEW value:".$param_name." = ".$param_value.' >>> '.$params_new_str,3,$this->plug_log);
        } catch (Exception $e) {
            pr("\n(ERROR)param data:".$param_name." > ".$e->getMessage(),3,$this->plug_log);
        }


    }
    protected function work() {
        $conected = false;
        $objDT = new DateTime('NOW');
        pr("\n(!!!)(ver.$this->ver) Start work:".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        echo "\n(ver.$this->ver) Working ...";
        do {
            if (file_exists($this->file_online)) {
                $conected = true;
            } else {
                $conected = false;
            }
            //--MAIN WORK----
            $data=fread($this->socket,1024);
            //$data=stream_get_contents($this->socket,1024);
            if (strlen($data) >0) {
                $this->makeParams($data);
            }


        } while ($conected);
        fclose($this->socket);
    }
    public function stop() {
        unlink($this->file_online);
    }
    public function online() {
        echo "dir is:".__DIR__;
        $onlinefile = fopen($this->file_online, "w+") or die("Unable to create online file!");
        fclose($onlinefile);
    }

    /**
     * Get current params from json file and set arr
     */
    public function getCurParams() {
        try {
            $params_json='';
            if ($this->cur_state == CONSOLE) {echo "\nFile params.json:$this->file_params\n";}

            $params_content=file_get_contents($this->file_params);

            if ($this->cur_state == CONSOLE) {echo "\nFile params.content:".$params_content."\n";}

            $params_json = json_decode($params_content,true);

            foreach ($this->params_arr as $key=>$val) {
                if (isset($params_json[$key])&&($params_json[$key] != $val)) {
                    $this->params_arr[$key]=$params_json[$key];
                    if ($this->cur_state == CONSOLE) {echo "\n Set params: ".$key." to: ".$params_json[$key];}
                }
            }

        } catch (Exception $e) {
            pr("\n(ERROR)reading json data:".$params_json,3,$this->plug_log);
        }


    }
    public function setCurParams($p_name,$p_value) {
        try {
            $this->getCurParams();
            $this->params_arr[$p_name]=$p_value;
            file_put_contents($this->file_params,json_encode($this->params_arr));
        } catch (Exception $e) {
            pr("\n(ERROR)setup json data(parameterName):".$p_name,3,$this->plug_log);
        }

    }
    public function isAction($action) {
        if (isset($_POST[$action])) {
            return true;
        }
        return false;
    }
    public function run() {
        $objDT = new DateTime('NOW');
        pr( "\nStart is: ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        if (file_exists($this->file_online)) {
            if ($this->connect() == true) {
                $this->writeCommandsAll();
                $this->work();
            }

        }
    }
    //----b:Statistics---------
    private function clearStatVars() {
        $this->stat_arr=[];
        $this->stat_period='d';
        $this->cmd['stat']='';
        $this->query_cnt =96;
        $this->endStatTime   = new DateTime('NOW');
        $this->beginStatTime = $this->endStatTime;
    }
    private function writeStatInit() {
        try {
            $objDT = new DateTime('NOW');
            //--register webpage---
//            fwrite($this->socket,"\xF0\x7F\x01");//--param str
//            fwrite($this->socket,$this->cmd['stat-init']);
//            fwrite($this->socket,"\xF7");//--end str
            //--subscribe stat variable---
//            fwrite($this->socket,"\xF0\x7F\x01");//--param str
//            fwrite($this->socket,$this->cmd['stat-fuel-rasxod']);
//            fwrite($this->socket,"\xF7");//--end str
//            //----------stat-electro-rasxod
//            fwrite($this->socket,"\xF0\x7F\x01");//--param str
//            fwrite($this->socket,$this->cmd['electro-rasxod']);
//            fwrite($this->socket,"\xF7");//--end str
            pr("\n(STAT)stat init DONE!: time is:".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        } catch (Exception $e) {
            $objDT = new DateTime('NOW');
            pr("\n(ERROR)wrong cmd: [stat-init]:".$e->getMessage()." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
        }
    }
    public function makeStat() {
        $objDT = new DateTime('NOW');
        pr( "\nMAKE statistics: ".$objDT->format('d-m-Y H:i')." period is:".$this->stat_period,3,$this->plug_log);
        if (file_exists($this->file_online)) {
            if ($this->connect() == true) {
                $this->writeStatInit();
                $this->writeCommandsStat('Electro');
                $this->writeStatFile('Electro');
                $this->writeCommandsStat('Fuel');
                $this->writeStatFile('Fuel');
                pr( "\n(STAT-DONE)Make statistics(Done!): ".$objDT->format('d-m-Y H:i')." period is:".$this->stat_period,3,$this->plug_log);
            }

        }
    }
    private function makeStatParams($data,$x_label) {
        //---seek valueAverage and get value
        //---razbor data and fill params_arr new values---
        $data=str_replace(':','&',strtolower($data));
        $data_next=str_replace(';','&',strtolower($data));
        $pos_p_name = strpos($data_next,'parameter');//---valueAverage ????
        $data_next=substr($data_next,$pos_p_name);
        //pr("\n(STAT DATA)next data:".$data_next.' >> pos_p_name='.$pos_p_name,3,$this->plug_log);

        parse_str($data_next,$res);
        $y=$res['valueaverage'];
        pr("\n(STAT DATA)after parse:".var_export($res,true).'  y='.$y,3,$this->plug_log);

        //---write [x,y] to stat_arr
        array_push($this->stat_arr,array('y'=>$y,'x'=>$x_label));
        //pr("\n(STAT DATA)after parse stat_arr:".var_export($this->stat_arr,true));
    }
    private  function makeStatCmd($param_name,$begin_time,$end_time){
        $search  = array('|p_param_name|', '|p_begin_value|','|p_end_value|');
        $stat_param_name = $this->stat_var_map[$param_name];
        $replace = array($stat_param_name, $begin_time,$end_time);
        $cmd_stat_str = str_replace($search, $replace, $this->cmd_stat);
        $this->cmd['stat'] = $cmd_stat_str;
        pr("\n(STAT)MAKE Stat param_name=".$param_name." Cmd str:".$cmd_stat_str,3,$this->plug_log);
    }

//    private function makeTimePeriod_old($i) {
//        $period_min = 1;
//        $period_interval = new DateInterval("P1D");
//        //pr("\n(STAT)makeTimePeriod(1): i=".$i,3,$this->plug_log);
//        try {
//            if ($this->stat_period == 'd') {
//                //$period_interval = new DateInterval("PT".$period_min."M");
//                $period_min = 24 * 60 / $this->query_cnt;
//            }
//            if ($this->stat_period == 'w') {
//                //$period_interval = new DateInterval("P7D");
//                $period_min = (7 * 24) * 60 / $this->query_cnt;
//            }
//            if ($this->stat_period == 'm') {
//                //$period_interval = new DateInterval("P1M");
//                $period_min = (30 * 24) * 60 / $this->query_cnt;
//            }
//            $period_min = intval($period_min);
//            //pr("\n(STAT)makeTimePeriod(2): i=" . $i . ' period_min=' . $period_min, 3, $this->plug_log);
//            $period_interval = intval($period_interval);
//            $period_interval = new DateInterval("PT" . ($i * $period_min) . "M");
//            if ($i == 1) {
//                //$this->endStatTime = new DateTime('NOW');
//                $this->endStatTime = new DateTime('2017-05-21');
//            } else {
//                $this->endStatTime = $this->endStatTime - (new DateInterval("PT" . $period_min . "M"));
//            }
//            $date_interval = new DateInterval("PT".$period_min."M");
//            //$this->beginStatTime = $this->endStatTime->sub($date_interval);//$period_interval;
//            $this->beginStatTime = $this->endStatTime->modify("-".$period_min." minutes");//$period_interval;
//            //pr("\n(STAT)makeTimePeriod(3): i=" . $i . ' period_min=' . $period_min.' Date interval:'. var_export($date_interval,true).' data-time beginStatTime:'.var_export($this->beginStatTime,true).'  endStatTime='.var_export($this->endStatTime,true), 3, $this->plug_log);
//
//            $beginTime = $this->beginStatTime->format("YmdHis");//$b_year.$b_month.$b_day.$b_hour.$b_min.$b_sek;
//            $endTime   = $this->endStatTime->format("YmdHis");//$e_year.$e_month.$e_day.$e_hour.$e_min.$e_sek;
//            //--test--
//            //$beginTime='201705'.(25-$i).'181100';
//
//            pr("\n(STAT)makeTimePeriod(4): i=" . $i . ' period_min=' . $period_min.' beginTime='.$beginTime.' endTime='.$endTime.' Date interval:'. var_export($date_interval,true), 3, $this->plug_log);
//            $time_arr = array('beginTime' => $beginTime, 'endTime' => $endTime);
//        } catch (Exception $e) {
//            $objDT = new DateTime('NOW');
//            pr("\n(ERROR)makeTimePeriod: []:".$e->getMessage()." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
//
//        }
//        pr("\n(makeTimePeriod) str: beginTime=".$beginTime.' EndTime='.$endTime,3,$this->plug_log);
//        return $time_arr;
//    }
    private function makeTimePeriod($i) {
        $period_min = 1;
        //$period_interval = new DateInterval("P1D");
       // pr("\n(STAT)makeTimePeriod(1): i=".$i,3,$this->plug_log);
        try {
            if ($this->stat_period == 'd') {
                //$period_interval = new DateInterval("PT".$period_min."M");
                $period_min = 24 * 60 / $this->query_cnt;
            }
            if ($this->stat_period == 'w') {
                //$period_interval = new DateInterval("P7D");
                $period_min = (7 * 24) * 60 / $this->query_cnt;
            }
            if ($this->stat_period == 'm') {
                //$period_interval = new DateInterval("P1M");
                $period_min = (20 * 24) * 60 / $this->query_cnt;
            }
            $period_min = intval($period_min);

            if ($i == 1) {
                //$this->endStatTime = date('Y-m-d H:i:s');;

               // $this->beginStatTime = date('Y-m-d H:i:s');;
                //$this->endStatTime = date('YmdHis');
                //$this->beginStatTime = date('YmdHis');
                $this->endStatTime = date('20170522121000');
                $this->beginStatTime = date('20170522121000');
            } else {

                $this->endStatTime = $this->beginStatTime;//StatTime - (new DateInterval("PT" . $period_min . "M"));
            }
            $curTime    = strtotime($this->endStatTime);
            $beforeTime = strtotime('-'.$period_min.' minutes',$curTime);
            $beforeDate=date('YmdHis', $beforeTime);
            $this->beginStatTime=$beforeDate;
            //pr("\n(STAT)makeTimePeriod(3): i=" . $i . ' period_min=' . $period_min.' data-time beginStatTime:'.var_export($this->beginStatTime,true).'  endStatTime='.var_export($this->endStatTime,true), 3, $this->plug_log);

            //$beginTime = $this->beginStatTime->format("YmdHis");//$b_year.$b_month.$b_day.$b_hour.$b_min.$b_sek;
            $beginTime = $this->beginStatTime;//->format("YmdHis");//$b_year.$b_month.$b_day.$b_hour.$b_min.$b_sek;
            //$endTime   = $this->endStatTime->format("YmdHis");//$e_year.$e_month.$e_day.$e_hour.$e_min.$e_sek;
            $endTime   = $this->endStatTime;//->format("YmdHis");//$e_year.$e_month.$e_day.$e_hour.$e_min.$e_sek;

            //pr("\n(STAT)makeTimePeriod(4): i=" . $i . ' period_min=' . $period_min.' beginTime='.$beginTime.' endTime='.$endTime, 3, $this->plug_log);
            $time_arr = array('beginTime' => $beginTime, 'endTime' => $endTime);
        } catch (Exception $e) {
            $objDT = new DateTime('NOW');
            pr("\n(ERROR)makeTimePeriod: []:".$e->getMessage()." ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);

        }
        pr("\n(STAT)(makeTimePeriod)(5) str: beginTime=".$beginTime.' EndTime='.$endTime.' period_min='.$period_min,3,$this->plug_log);
        return $time_arr;
    }
    private function makeXLabel($i) {
        //return $i;
        $res=$this->beginStatTime;
        if ($this->stat_period =='d') {
            $res=substr($res,8,2).'h';//--H hour
        }
        if ($this->stat_period =='w') {
            if (($i % 2) ==0) {
                $res=substr($res,4,2).'-'.substr($res,0,4);//--D + Y
            } else {
                $res=substr($res,4,2);//--D
            }

        }
        if ($this->stat_period =='m') {
            if (($i % 2) ==0) {
                $res=substr($res,4,2).'-'.substr($res,6,2).'-'.substr($res,0,4);//--m + D+ Y
            } else {
                $res=substr($res,6,2);//--D
            }

        }
        return $res;//substr//
    }
    private function writeCommandsStat($param_name) {
        //$this->clearStatVars();
        $this->stat_arr=[];//---clear stat points---
        $objDT = new DateTime('NOW');
        pr( "\n(STAT)writeCommandsStat: ".$param_name.' time is:'.$objDT->format('d-m-Y H:i')." period is:".$this->stat_period.' query_cnt='.$this->query_cnt,3,$this->plug_log);
        for ($i=1;$i <= $this->query_cnt;$i++) {
            //----make time_period_arr
            $time_period_arr=$this->makeTimePeriod($i);
            $begin_time_str=$time_period_arr['beginTime'];
            $end_time_str  =$time_period_arr['endTime'];
            //----make stat cmd(param_name,beginTime,endTime)
            $this->makeStatCmd($param_name,$begin_time_str,$end_time_str);
            //----write stat cmd
            $this->writeCommandsOne('stat');
            pr( "\n(STAT) stat cmd was written to socket (Done!) i=".$i);
            //----read stat avg data
            //sleep(1);
            usleep(900000);
            $data=fread($this->socket,1024);
            //$data=stream_get_contents($this->socket,1024);
            if (strlen($data) >0) {
                $this->makeStatParams($data,$this->makeXLabel($i));
            }
            pr( "\n(STAT-i=($i)-done)writeCommandsStat (Done!): ".$param_name.' time is:'.$objDT->format('d-m-Y H:i')." period is:".$this->stat_period,3,$this->plug_log);

            //-----write to $stat_arr
        }
    }
    private function writeStatFile($param_name) {
        if ($param_name =='Electro') {
            $this->stat_file='chart_data'.'_'.$this->electro_stat_index.'.json';
        }
        if ($param_name =='Fuel') {
            $this->stat_file='chart_data'.'_'.$this->fuel_stat_index.'.json';
        }
        $this->stat_arr = array_reverse($this->stat_arr);
        $out_stat_arr=array("points"=>$this->stat_arr);
        file_put_contents($this->stat_file,json_encode($out_stat_arr));
    }
    //----e:Statistics---------
    function __destruct() {
        if ($this->cur_state == CONSOLE) {echo "\nStop working... \n" ;}
        $objDT = new DateTime('NOW');
        pr( "\n(STOP) working on: ".$objDT->format('d-m-Y H:i'),3,$this->plug_log);
    }
}
$p=new Plug();
if ($p->isAction('power')) {
    $p->cur_state=WEB;
    error_reporting(0);
    $res=array("state"=>"done","mes"=>'Power installed!');
    if ($p->connect()) {
        try {
            $val=intval($_POST['power']);
            $p->writeCommandValue('Power_On_Off',$val);
            $p->setCurParams('power',($val ==1)?"ON":"OFF");
        } catch(Exception $e) {
            $res["state"]='error';
            $res["mes"]  ='Error then writing value...';
        }

    } else {
        $res["state"]='error';
        $res["mes"]  ='No socket connection..';
    }
    echo json_encode($res);
    exit(0);
}

if ($p->isAction('stat')) {
    $p->cur_state=WEB;
    //error_reporting(0);
    set_time_limit(90);
    $res=array("state"=>"done","mes"=>'Stat command sent!');
//    echo json_encode($res);
//    exit(0);
    $objDT = new DateTime('NOW');
    pr("\n(WEB) statictics :".$objDT->format('d-m-Y H:i').' period is:'.$_POST['period'],3,$p->plug_log);
    if ($p->connect()) {
        try {
            if (in_array($_POST['period'],['d','w','m'])) {
                pr("\n(WEB) statictics PERIOD IN:".$objDT->format('d-m-Y H:i').' period is:'.$_POST['period'],3,$p->plug_log);
                $p->stat_period=$_POST['period'];
                $p->makeStat();
            }
            //---Send batch stat comand---
            //$p->writeCommandValue('Stat',$period);
            //$p->setCurParams('power',($val ==1)?"ON":"OFF");
        } catch(Exception $e) {
            $res["state"]='error';
            $res["mes"]  ='Error then sending stat command value...';
        }

    } else {
        $res["state"]='error';
        $res["mes"]  ='No socket connection..';
    }
    pr("\n(WEB) statictics before json out ");
    echo json_encode($res);
    exit(0);
}
//---------------------------B:DEBUG_CMD----------------------------------
if ($p->isAction('debug_cmd')) {
    $p->cur_state = WEB;
    $debug_cmd=$_POST['debug_cmd'];
    set_time_limit(90);
    $res=array("state"=>"done","mes"=>'Debug command sent!');
    $objDT = new DateTime('NOW');
    pr("\n(WEB) debug :".$objDT->format('d-m-Y H:i').' debug_cmd is:'.$debug_cmd,3,$p->plug_log);
    if ($p->connect()) {
        try {
            $p->writeDebugCommand($debug_cmd);
        }  catch(Exception $e) {
            $res["state"]='error';
            $res["mes"]  ='Error then writing debug command...';
        }
    }  else {
        $res["state"]='error';
        $res["mes"]  ='No socket connection...';
    }
    echo json_encode($res);
    exit(0);
}
//---------------------------E:DEBUG_CMD----------------------------------
$options = getopt("m:n:h:c:");
$method=$options['m'];
$sub_name=$options['n'];
$h=$options['h'];
$command=$options['c'];
$debug_command=$options['d'];
if ($h != false) {
    echo <<<END
    *** Linkit connection plug-system  ***
-hh  -help
-m  -m[stop|online|curparams]
     "Action mode.You can [stop|online] service"
     curparams - get current parameters
-c  -c[init|fuel|batarey]
     "Command mode.You can send command to socket"
-d  -d[can_init|auto_renew|auto_stop|inverter_renew|engine_renew|battery_renew]
     "Command mode.You can send debug command to socket"
END;

}
if ($command != false) {
    echo "(Fuel|Batarey) Command...";
    $p->cur_state = CONSOLE;
    if ($p->connect() == true) {
        $p->writeCommandsOne($command);
        if ($p->cur_state == CONSOLE) {echo "Command  $command sent";}
    } else {
        if ($p->cur_state == CONSOLE) {echo "(ERROR) Command  $command ";}
    }
    die();
}
if ($debug_command != false) {
    echo "(Debug) Command...";
    $p->cur_state = CONSOLE;
    if ($p->connect() == true) {
        $p->writeDebugCommand($debug_command);
        if ($p->cur_state == CONSOLE) {echo "Command  $command sent";}
    } else {
        if ($p->cur_state == CONSOLE) {echo "(ERROR) Command  $command ";}
    }
    die();
}
if ($method != false) {
    $p->cur_state=CONSOLE;
    if ($method=='stop') {
        $p->stop();
    }
    if ($method=='online') {
        $p->online();
    }
    if ($method=='curparams') {
        $p->getCurParams();
    }
} else {
    $p->run();
}

//$p->makeParams();
?>