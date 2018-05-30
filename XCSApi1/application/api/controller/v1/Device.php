<?php

namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\controller\Api;
use think\Response;
use think\Db;
use app\api\controller\UnauthorizedException;

class Device extends Api
{   
    public $restMethodList = 'get|post|put';
  
    //rest api


    /**
     * 设备信息获取
     *
     * @param  int  $device_id,$token
     * @return  
     */
    public function infos()
    {   
        $conf = "mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8";
        $request = request();
        if($request->isPost()){
            // dump(str_replace("ab","","abcaasdfads"));
            // trace($request->path());
            $device_id = $request->param('device_id');
            // dump($id);
            if(is_numeric($device_id)){
                $data = Db::connect($conf)->table('xcs_io')->where('device_id',$device_id)->field(true)->find();
                if(empty($data)){
                    return $this->returnmsg(401,'设备不存在');
                }
                $do = str_split($data['DO']);
                unset($data['id']); 
                unset($data['DI']); 
                unset($data['DO']);
                unset($data['lng']); 
                unset($data['lat']);  
                $i=1;
                foreach ($do as $key => $value) {
                    $data['DO'.$i] = $value;
                    $i++;
                }
                return $this->returnmsg(200,'success',$data);
            }else{
                $map = array();
                $data = Db::connect($conf)->table('xcs_io')->field(true)->select();
                foreach ($data as $key => $value) {
                  $do = str_split($value['DO']);
                  unset($value['id']);   
                  unset($value['DI']); 
                  unset($value['DO']);
                  unset($value['lng']); 
                  unset($value['lat']);  
                  $i=1;
                  foreach ($do as $k => $v) {
                      $value['DO'.$i] = $v;
                      $i++;
                  }
                  $map[] = $value;
                }
                return $this->returnmsg(200,'success',$map);
            }

        }
    }

   /**
     * io口信息获取
     *
     * @param  int  $id,$io_name,$token
     * @return  
     */
    public function ioInfo(){
        $conf = "mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8";
        $request = request();
        $AIlist = ['AI0','AI1','AI2','AI3','AI4','AI5','AI6','AI7','AI8','AI9','AI10','AI11','AI12','AI13','AI14','AI15'];
        $DOlist = ['DO0','DO1','DO2','DO3','DO4','DO5','DO6','DO7','DO8','DO9','DO10'];
        if($request->isPost()){
            $device_id = $request->param('device_id');
            $ai_name = $request->param('io_name');
            if(is_numeric($device_id) && null!=$ai_name){
                $io = Db::connect($conf)->table('xcs_io')->where('device_id',$device_id)->field(true)->find();
                if(empty($io)){
                    return $this->returnmsg(401,'设备不存在');
                }
                $conf = Db::connect($conf)->table('alarm_conf')->where(array('device_id'=>$device_id,'ai_name'=>$ai_name))->field('device_id,ai_name io_name,max,min,ai_up_limit spec_max,ai_down_limit spec_min')->find();
                $bool = empty($conf);
                $conf['device_id'] = $device_id;
                $conf['ai_name'] = $ai_name;
                if(in_array($ai_name, $DOlist)){ 
                    $i=1;
                    $do = str_split($io['DO']);
                    foreach ($do as $k => $v) {
                        if($ai_name!='DO'.$i){
                            $i++;
                        }else{
                            $conf['value'] = $v;
                        }
                    }   
                }elseif(in_array($ai_name, $AIlist)){
                    $conf['value'] = $io[$ai_name];
                }else{
                    return $this->returnmsg(401,'IO口不存在');
                }

                if($bool){
                    $conf['mark'] = '未设置报警初始参数';
                    return $this->returnmsg(200,'success',$conf); 
                }else{

                    return $this->returnmsg(200,'success',$conf); 
                }
            }elseif (!is_numeric($device_id)&&null==($ai_name)) {
                $map = array();
                $info= array();
                //groups
                $devs = Db::connect($conf)->table('xcs_io')->field(true)->select();
                foreach ($devs as $key => $value) {                
                    
                    foreach ($value as $k => $v) {
                        if(stristr($k,"AI")){
                            $conf = Db::connect($conf)->table('alarm_conf')->where(array('device_id'=>$value['device_id'],'ai_name'=>$k))->field('device_id,ai_name io_name,type,max,min,ai_up_limit spec_max,ai_down_limit spec_min')->find();
                            if(empty($conf)){
                                $info['device_id'] = $value['device_id'];
                                $info['io_name'] = $k;
                                $info['value'] = $value[$k];
                                $info['mark'] = '未设置报警初始参数';
                            }else{
                                $info = $conf;
                                $info['value'] = $value[$k];
                                $info['device_id'] = $value['device_id'];
                            }
                            $map[] = $info; 
                            $info = []; 
                        }elseif($k=="DO"){
                            $do = str_split($value['DO']);
                            $i=1;
                            foreach ($do as $dokey => $doValue) {
                                $info['device_id'] = $value['device_id'];
                                $info['io_name'] = 'DO'.$i;
                                $info['value'] = $doValue;   
                                $i++;
                                $map[] = $info;
                                $info = []; 
                            }                           
                        }
                        
                    }
                }
                return $this->returnmsg(200,'success',$map); 
                
            }elseif(is_numeric($device_id)&&null==($ai_name)){
                $io = db('xcs_io')->where('device_id',$device_id)->field(true)->find();
                if(empty($io)){
                    return $this->returnmsg(401,'设备不存在');
                }
               
                foreach ($io as $k => $v) {
                    if(stristr($k,"AI")){
                        $conf = Db::connect($conf)->table('alarm_conf')->where(array('device_id'=>$io['device_id'],'ai_name'=>$k))->field('device_id,ai_name io_name,type,max,min,ai_up_limit spec_max,ai_down_limit spec_min')->find();
                        if(empty($conf)){
                            $info['device_id'] = $io['device_id'];
                            $info['io_name'] = $k;
                            $info['value'] = $io[$k];
                            $info['mark'] = '未设置报警初始参数';
                        }else{
                            $info = $conf;
                            $info['value'] = $io[$k];
                            $info['device_id'] = $io['device_id'];
                        }
                        $map[] = $info;     
                         $info = [];  
                    }elseif($k=="DO"){
          
                        $do = str_split($io['DO']);
                        $i=1;
                        foreach ($do as $dokey => $doValue) {
                            $info['device_id'] = $io['device_id'];
                            $info['io_name'] = 'DO'.$i;
                            $info['value'] = $doValue;   
                            $i++;
                            $map[] = $info;     
                            $info = []; 
                        }                           
                    }
                    
                }
                return $this->returnmsg(200,'success',$map);
            }else{
                return $this->returnmsg(401,'请求信息不全'); 
            }
        }
    }


    /**
     * io口信息编辑
     *
     * @param  int  $id,$in_name,$token(type,max,mix,spe_max,spe_max)
     * @return  
     */
    public function ioEdit(){
        $conf = "mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8";
        $request = request();
        // $DOlist = ['DO0','DO1','DO2','DO3','DO4','DO5','DO6','DO7','DO8','DO9'];
        if($request->isPost()){
            $set = array();
            $device_id = $request->param('device_id');
            $ai_name = $request->param('io_name');
            $data = Db::connect($conf)->table('xcs_io')->where('device_id',$device_id)->field(true)->find();
            if(empty($data)){
                return $this->returnmsg(401,'设备不存在');
            }
            if(null==$request->param('io_name')){
                return $this->returnmsg(401,'IO设备信息缺失');
            }
            // if(!in_array($ai_name, $DOlist)){
            //     return $this->returnmsg(401,'不允许chaoz');
            // }
            $set['ai_name'] = $ai_name; 
            if(!null==$request->param('type')){
                $type = $request->param('type');
                if($data['type']!= $type){
                    $set['type'] = $type; 
                }
            }
            if(!null==$request->param('max')){
                $max = $request->param('max');
                if($data['max']!= $max){
                    $set['max'] = $max; 
                }
            }
            if(!null==$request->param('min')){
                $min = $request->param('min');
                if($data['min']!= $min){
                    $set['min'] = $min; 
                }
            }
            if(!null==$request->param('spec_min')){
                $lng = $request->param('spec_min');
                if($data['spec_min']!= $spec_min){
                    $set['spec_min'] = $spec_min; 
                }
            }
            if(!null==$request->param('spec_max')){
                $spec_max = $request->param('spec_max');
                if($data['ai_up_limit']!= $spec_max){
                    $set['spec_max'] = $spec_max; 
                }
            }
            $range = Db::connect($conf)->table('alarm_conf')->where(array('device_id'=>$data['device_id'],'ai_name'=>$set['ai_name']))->find();
            if(!empty($range)){
                Db::connect($conf)->table('alarm_conf')->where(array('device_id'=>$data['device_id'],'ai_name'=>$set['ai_name']))->update($set);
            

            }else{
                $set['device_id'] = $data['device_id']; 
                Db::connect($conf)->table('alarm_conf')->insert($set);
            }
            return $this->returnmsg(200,'success');  
        }
    }
    
       /**
     * io口批量信息编辑
     *
     * @param  int  $id,$in_name,$token(type,max,mix,spe_max,spe_max)
     * @return  
     */
    public function ioGroupsEdit(){
        $conf = "mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8";
        $request = request();
        if($request->isPost()){
            $map = array();          
            $data = $request->param();
            foreach ($data['ioGroups'] as $key => $value) {
                $ai_name = $value['io_name'];
                $map = $value;
                $map['device_id'] = $value['device_id'];
                $map['ai_name'] =  $value['io_name'];
                unset($map['io_name']);
                if(empty(Db::connect($conf)->table('alarm_conf')->where(array('device_id'=> $value['device_id'],'ai_name'=>$ai_name))->find())){
                    Db::connect($conf)->table('alarm_conf')->insert($map);
                }else{
                    Db::connect($conf)->table('alarm_conf')->where(array('device_id'=> $value['device_id'],'ai_name'=>$ai_name))->update($map);
                }
            }
            return $this->returnmsg(200,'success');  
        }
    }
    
    /**
     * io口控制
     *
     * @param  int  $id,$io_name,$token,$value
     * @return  
     */
    public function ioControll(){
        $conf = "mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8";
        $request = request();
        $DOlist = ['DO0','DO1','DO2','DO3','DO4','DO5','DO6','DO7','DO8','DO9','DO10'];
        if($request->isPost()){
            $map = array();
            $info = array();
            $device_id = $request->param('device_id');
            $data = Db::connect($conf)->table('xcs_io')->where('device_id',$device_id)->field(true)->find();
            if(empty($data)){
                return $this->returnmsg(401,'设备'.$device_id.'不存在');
            }
            $map['device_id'] = $device_id;
            $map['type'] = -1;
            $io_name = $request->param('io_name');
            $val = $request->param('set_value');   
            if(null==$val){
                return $this->returnmsg(401,'没有修改设置参数');
            }
            //只有DO可以控制
            if(in_array($io_name,$DOlist)){
                $map['io_name'] = $io_name;
                $map['value'] =  $val;              
            }else{
                return $this->returnmsg(401,'该iO不存在或不允许操作');
            }

            if(empty(Db::connect($conf)->table('xcs_io_read')->where(array('device_id'=>$device_id,'io_name'=>$io_name))->find())){
                if(Db::connect($conf)->table('xcs_io_read')->insert($map)){
                    return $this->returnmsg(200,'success');  
                }else{
                    return $this->returnmsg(401,'设备'.$device_id.":".$io_name.'修改失败');  
                }
            }else{
                for ($i=0; $i < 5 ; $i++) { 
                    if(db('xcs_io_read')->where(array('device_id'=>$device_id,'io_name'=>$io_name))->update($map)){
                        break;
                    }else {
                        usleep(200000);
                    }
                }
                if(Db::connect($conf)->table('xcs_io_read')->where(array('device_id'=>$device_id,'io_name'=>$io_name))->update($map)){
                    return $this->returnmsg(200,'success');  
                }else{
                    return $this->returnmsg(401,'设备'.$device_id.":".$io_name.'修改失败');  
                }
            }

            
        }
    }

    /**
     * io口批量控制
     *
     * @param  int  $id,$io_name,$token,$value
     * @return  
     */
    public function ioGroupsControll(){
        $conf = "mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8";
        $request = request();
        $DOlist = ['DO0','DO1','DO2','DO3','DO4','DO5','DO6','DO7','DO8','DO9','DO10'];
        if($request->isPost()){
            $map = array();
            $info = array();
            $warning = array();
            $data = $request->param();
            foreach ($data['ioGroups'] as $key => $value) {
                $device_id = $value['device_id'];
                $data = Db::connect($conf)->table('xcs_io')->where('device_id',$device_id)->field(true)->find();
                if(empty($data)){
                    $warning[] = '设备'.$device_id.'不存在';
                }
                $map['device_id'] = $device_id;
                $map['type'] = -1;
                $io_name  = $value['io_name'];
                $val = $value['set_value'];
                //只有DO可以控制
                if(in_array($io_name,$DOlist)){
                    $map['io_name'] = $io_name;
                    $map['value'] =  $val;                    
                }else{
                    $warning[] = '设备'.$device_id.":".$io_name.'不能修改或者不存在';
                    continue;  
                }
                if(!empty(Db::connect($conf)->table('xcs_io_read')->where(array('device_id'=>$device_id,'io_name'=>$io_name))->find())){
                    if(Db::connect($conf)->table('xcs_io_read')->where(array('device_id'=>$device_id,'io_name'=>$io_name))->update($map)){
                        
                    }else{
                        $warning[] = '设备'.$device_id.":".$io_name.'修改失败或者无需修改';    
                    }

                }else{
                    if(Db::connect($conf)->table('xcs_io_read')->insert($map)){
                        
                    }else{
                        $warning[] = '设备'.$device_id.":".$io_name.'添加失败';  
                    }
                }
            }
            if(count($warning)==0){
                return $this->returnmsg(200,'success');  
            }else{
                return $this->returnmsg(200,'success',$warning);  
            }
            
        }
    }

    /**
     * 报警信息
     *
     * @param  int  $id,
     * @return  
     */
    public function alarm()
    {   
        $conf = "mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8";
        $request = request();
        if($request->isPost()){
 
            $data = Db::connect($conf)->table('alarm_record')->field('device_id,io_name,value,time')->select();
            if($data){
                return $this->returnmsg(200,'success',$data);
            }else{
                return $this->returnmsg(401,'没有数据,查询失败');
            }

        }
    }
    
    
}
