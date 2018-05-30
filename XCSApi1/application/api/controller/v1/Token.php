<?php
/**
 * 获取accesstoken
 */
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\controller\Api;
use think\Response;
use app\api\controller\UnauthorizedException;
use app\api\controller\Send;
use app\api\controller\Oauth as Oauth2;
use app\api\model\Oauth as Oauth;
use think\Db;
use think\Cache;
use app\api\controller\Factory;
class Token extends Controller
{	
	use Send;
	//登录请求验证规则
	public static $rule_user = [
		'userName'     =>  'require',
		'password'     =>  'require',
	];
    /**
     * 构造函数
     * 初始化检测请求时间，签名等
     */
    public function __construct()
    {
        $this->request = Request::instance();
        //$this->checkTime();
        // $this->checkSign();
    }

	public function wechat()
	{    
		$this->checkAppkey(self::$rule_wechat);  //检测appkey
	}

	/**
	 * 为客户端提供access_token
	 * 
	 */
	public function login()
	{	
		//检测
		$this->checkAppkey(self::$rule_user);

		//$mobilebind = Db::name('tb_user')->field('mobilephone,id as user_id')->where('mobilephone',$this->request->param('mobilephone'))->find();  //取数据库对应手机号绑定用户
		$data = $this->request->param();
		$userName = $data['userName'];
		$password = md5($data['password']);
		$accountInfo = Db::connect("mysql://etrol:sql@120.77.86.72:3306/etrol_app#utf8")->table('user')->where(array('name'=>$userName))->find();
		if(!empty($accountInfo)){
			if($accountInfo['password'] == $password){
			}else{
				return $this->returnmsg(401,'密码错误！');
			}
		}else{
			return $this->returnmsg(401,'用户不存在！');
		}
		$bind = ['userName'=>$userName,'password'=>$password];
		if(!empty($bind)){
			try {
				// $mobilebind['app_key'] = $this->request->param('app_key');
				$bind['app_key'] = '2232xcsapiadmin1212';
				$accessTokenInfo = $this->setAccessToken($bind);
				return $this->returnmsg(200,'success',array('access_token'=>$accessTokenInfo['access_token'],'expires_time'=>$accessTokenInfo['expires_time']));
			} catch (\Exception $e) {
				$this->sendError(500, '服务器异常!!', 500);
			}
		}else{
			return $this->returnmsg(401,'用户为空');
		}

	}

	/**
	 * 检测时间+_300秒内请求会异常
	 */
	public function checkTime()
	{
		$time = $this->request->param('timestamp');
		if($time > time()+300  || $time < time()-300){
			return $this->returnmsg(401,'请求时间超时');
		}
	}

	/**
	 * 检测appkey的有效性
	 * @param 验证规则数组
	 */
	public function checkAppkey($rule)
	{
		$result = $this->validate($this->request->param(),$rule);
		if(true !== $result){
			return $this->returnmsg(405,$result);
		}
        //====调用模型验证app_key是否正确，这里注释，请开发者自行建表======
		// $result = Oauth::get(function($query){
		// 	$query->where('app_key', $this->request->param('app_key'));
		// 	$query->where('expires_in','>' ,time());
		// });
		if(empty($result)){
			return $this->returnmsg(401,'App_key does not exist or has expired. Please contact management');
		}
	}

	/**
	 * 检查签名
	 */
	public function checkSign()
	{	
		$baseAuth = Factory::getInstance(\app\api\controller\Oauth::class);
		$app_secret = Oauth::get(['app_key' => $this->request->param('app_key')]);
		$sign = $baseAuth->makesign($this->request->param(),$app_secret['app_secret']);     //生成签名
    	if($sign !== $this->request->param('signature')){
    		return self::returnmsg(401,'签名错误',[],[]);
    	}
	}

	/**
     * 设置AccessToken
     * @param $clientInfo
     * @return int
     */
    protected function setAccessToken($clientInfo)
    {
        //生成令牌
        $accessToken = self::buildAccessToken();
        $accessTokenInfo = [
            'access_token' => $accessToken,//访问令牌
            'expires_time' => time() + Oauth2::$expires,      //过期时间时间戳
            'client' => $clientInfo,//用户信息
        ];
        self::saveAccessToken($accessToken, $accessTokenInfo);
        return $accessTokenInfo;
    }

    /**
     * 生成AccessToken
     * @return string
     */
    protected static function buildAccessToken($lenght = 32)
    {
        //生成AccessToken
        $str_pol = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
		return substr(str_shuffle($str_pol), 0, $lenght);

    }

    /**
     * 存储
     * @param $accessToken
     * @param $accessTokenInfo
     */
    protected static function saveAccessToken($accessToken, $accessTokenInfo)
    {
        //存储accessToken
        Cache::set(Oauth2::$accessTokenPrefix . $accessToken, $accessTokenInfo, Oauth2::$expires);
    }
}