<?php
namespace Rf;

class IDB{
    public $db_link_id=false;
    private $DB_Server;
    private $dbuser;
    private $dbpw;
    private $dbName="";
    private $dbCharSet="";
    private $res=false;
    private $rows;
    private $time_start=0; //啟始執行時間
    private $query_string=""; //執行的Sql語法
    protected $logFlag=""; //預設不進行Log
    protected $btuFlag="n"; //預設不轉碼
    public $gcml="1024";
    public $arrColumns; //特定欄位轉碼，跟setBtu('y')搭配使用
    public $data;
    function __construct($db_server_short_name=""){
        switch($db_server_short_name){
        default:
              $this->DB_Server = env("DB_HOST","127.0.0.1");
              $this->dbName = env("DB_DATABASE","");
              $this->dbuser = env("DB_USERNAME","");
              $this->dbpw = env("DB_PASSWORD","");
              $this->dbCharSet=env("DB_CHARSET","utf8mb4");
              $this->dbPort=env("DB_PORT","3306");
            break;
        }
    }
    //設定db名稱
    public function setDbName($dbName){
        $this->dbName=$dbName;
    }
    public function setDbServer($dbServer){
       $this->DB_Server=$dbServer;
    }
    public function setDbPassword($dbpw){
       $this->dbpw=$dbpw;
    }
    public function setDbUser($dbuser){
       $this->dbpw=$dbuser;
    }
    //取得mysqli連線ID
    public function id(){
        if(empty($this->dbName)) {
            echo "db name is empty";
            return false;
        }
        $this->db_link_id=mysqli_connect($this->DB_Server,$this->dbuser,$this->dbpw);
        mysqli_set_charset($this->db_link_id, $this->dbCharSet);
        mysqli_select_db($this->db_link_id,$this->dbName) or die(mysqli_error());
        return $this->db_link_id;
    }
    public function data($column=""){
        if(empty($column)){
            return $this->data;
        }else{
            if(is_numeric($column)){
                $tempData=$this->data;
                if(count($tempData)>0){
                    return $tempData[0];
                }
                return array();
            }
            //回傳單列單一欄位
            if(count($this->data)>0){
                $tempData=$this->data;
                return $tempData[0][$column];
            }else{
                return "";
            }
        }
    }
    public function setBtu($v="n"){
        $this->btuFlag=$v;
        return $this;
    }
    public function query($query_str){
        $this->query_string = $query_str; 
        $this->data=array();
        $this->db_link_id=mysqli_connect($this->DB_Server,$this->dbuser,$this->dbpw);
        mysqli_set_charset($this->db_link_id, $this->dbCharSet);
        mysqli_select_db($this->db_link_id,$this->dbName) or die(mysqli_error($this->db_link_id));
        
        //每次Qury都是由0開始
        mysqli_query($this->db_link_id,'SET @rownum=0') or die(mysqli_error($this->db_link_id));

        if('1024'!=$this->gcml){
            $sql_set_session = sprintf("SET SESSION group_concat_max_len=%s",$this->gcml);
            mysqli_query($this->db_link_id,$sql_set_session) or die(mysqli_error($this->db_link_id));
        }

        $this->time_start = microtime(true); 
        $this->res=mysqli_query($this->db_link_id,$query_str);
        //檢測是否要進行Log
        if(""!=$this->logFlag){
            $this->backtrace = debug_backtrace();
            $this->log();
        }

        if(!$this->res){
            throw new \Exception(mysqli_error($this->db_link_id));
        }

        //如果是select並且是true時
        $query_str = substr(trim($query_str), 0, 10);//修正big5造成的preg_match誤判

        $i=0;
        while($rows=mysqli_fetch_assoc($this->res)){
            foreach($rows as $k=>$v){
                if($this->btuFlag=="y"){
                    if(count($this->arrColumns)==0) {
                        $v=$this->btu($v);
                    }
                    if(count($this->arrColumns)>0) {
                            //如果Array內有值才轉換
                        if (in_array($k,$this->arrColumns)){
                            $v=$this->btu($v);
                        }
                    }
                }
                $this->data[$i][$k]=$v;
            }
            $i++;
        }
        //還原
        mysqli_data_seek( $this->res, 0 );
        return $this;
    }
    //回傳Bootstrap-Table需要的JSON資料 Beta Testing
    public function bt($total=0, $data="")
    {
        $json_string="";
        if(empty($data)){
            $json_string = '{"total":'.$total.',"rows":'.json_encode($this->data).'}';
        }else{
            $json_string = '{"total":'.$total.',"rows":'.json_encode($data).'}';
        }
        return json_decode($json_string, true);
    }

    //回傳資料更動的筆數(Insert & Update)
    public function affected_rows(){
        if($this->db_link_id){
            return mysqli_affected_rows($this->db_link_id);
        }else{
            return 0;
        }
    }
    public function insert_id(){
        if($this->db_link_id){
            return mysqli_insert_id($this->db_link_id);
        }else{
            return false;
        }
    }
    //回傳numrows
    public function num(){
        if(count($this->data())==0){
            return 0;
        }
        if($this->res){
            return mysqli_num_rows($this->res);
        }else{
            return 0;
        }
    }
    //回傳搜尋結果的數字(Select)
    public function count()
    {
        echo mysqli_num_rows($this->res);
    }

    public function close()
    {
        mysqli_close($this->db_link_id);
    }
    //用來設定需轉碼的欄位，傳入陣列
    public function needConvert($arrColumns)
    {
        $this->arrColumns=$arrColumns;
        return $this;
    }

    public function utf8Converter($v)
    {

        try{
            $v = iconv("Big5-HKSCS","UTF8", $v);
            $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
            //移除所有斷行及tab
//          $v = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $v);
            $v = stripslashes($v);
                return $v;
        }catch(Exception $e){
            $v=mb_convert_encoding($v, 'HTML-ENTITIES', 'big5');
            $v=mb_convert_encoding($v, "UTF-8", "HTML-ENTITIES");
            $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
            //移除所有斷行及tab
            //$v = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $v);
            $v=stripslashes($v);
            return $v;
        }
    }

    public function utb($source){
        $source=htmlspecialchars($source);
        $source= str_replace("\\r\\n", "<br/>", $source);
        $source=mysqli_real_escape_string($this->id(),mb_convert_encoding($source,'big5','utf-8'));//轉成Big5，並且Addslashes解決許功蓋問題
        $source= str_replace("<br/>", "\n", $source);//從新斷行存回
        return $source;
    }

    public function escape($q){
        $q=mysqli_real_escape_string($this->id(),$q);
        return $q;
    }

    //big5轉utf8用
    public function htu($v){
        $v = @iconv("big5-hkscs","UTF8", $v);
        preg_match_all('/&#\\d+;/u', $v,$matches);
        foreach($matches[0] as $index=>$entitie){
            $r = mb_convert_encoding($entitie, "UTF-8", "HTML-ENTITIES");
            if($r!='?'){
                $v = str_replace($entitie,$r,$v);
            }
        }
        $v=stripslashes($v);
        return $v;
    }

    public function btu($req){
        return $this->utf8Converter($req);
        //下面是舊的
        return addslashes(mb_convert_encoding(stripslashes(trim($req)), "UTF-8", "big5"));
    }

    function first($v=''){
        $temp = $this->data(1);
        if($v!=''){
            return $temp[$v];
        }
        return $temp;
    }

    function map($column_name=""){
        $temp=array();
        foreach($this->data() as $row){
            $keys = array_keys($row);
            if($column_name==""){
                $temp[]=$row[$keys[0]];
            }else{
                $temp[]=$row[$column_name];
            }
        }
        return $temp;
    }

    function merge($newarray){
        $this->data = array_merge($newarray, $this->data());
        return $this;
    }

    function toJson(){
        return json_encode($this->data());
    }
    function toArray(){
        return $this->data();
    }
    //將欄位轉key及value
    function pluck($as_value,$as_key){
        $temp = array();
        foreach($this->data as $row){
            $temp[$row[$as_key]]=$row[$as_value];
        }
        return $temp;
    }

    function setLog($v="n"){
        $this->logFlag=$v;
    }

    //用來記錄query的執行時間sql語法
    function log($text="") {
        $arr = $this->backtrace;
        $text.=sprintf("====== %s ======", date("Y-m-d H:i:s"));
        if(mb_detect_encoding($this->logFlag,"utf-8",true)!="UTF-8"){
            $this->logFlag = $this->btu($this->query_string);
        }
        $text.="\n".$this->logFlag;
        $text.= "\n=================================";
        $text.="\nprocess_id:".getmypid(); 
        $text.="\n";
        if(mb_detect_encoding($this->query_string,"utf-8",true)!="UTF-8"){
            $this->query_string = $this->btu($this->query_string);
        }
        $log_str="\n${text}執行秒數:".(microtime(true) - $this->time_start)."\n檔案:".$arr[0]['file']."\n行:".$arr[0]['line']."\n".$this->query_string."\n\n";
        error_log($log_str,"3",dirname(__FILE__)."/../../storage/logs/IDB.log");
    }
    
}
?>
