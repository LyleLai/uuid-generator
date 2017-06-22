<?php
class UuidGenerator
{
	const MULTIPLY_BASE = 1000000;
	private static function uniqidOfToday($serverId = 1)
	{
		$timebase = strtotime("Today");
		//$key=ftok(__FILE__,'b');	// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句
		//$sem_id=sem_get($key);		// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句
		//sem_acquire($sem_id);		// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句
		$time = microtime(true);        // 1天24小时，确保获取时间的操作在本服务器是排他的。那么得到的时间也是唯一的了。阿里云执行这个需要5us
		//sem_release($sem_id);		// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句
		$elapse = ($time - $timebase) * self::MULTIPLY_BASE;            // 只留下差额，每天86400.000000秒，10^6 倍就是微秒，阿里云执行已经保证不重复了
		$elapse_bin = decbin(pow(2,37) - 1 + $elapse);  // 38bit
		$server_id_bin = decbin(pow(2,4) - 1 + $serverId);     // 5bit // 1 to 2^4-1 ，即最大支持15台服务器
		$random = mt_rand(1, pow(2,6)-1);               // 随机数，因为排他了，其实不一定要。考虑到阿里云可能升级cpu速度，提升一个数量级之后不是us级，所以随机一下更靠谱
		$random_bin = decbin(pow(2,6)-1 + $random);     // 7bit
		$result_bin = $elapse_bin.$server_id_bin.$random_bin;   // 50bit
		return bindec($result_bin);
	}

	/* 在部署集群时需要修改不同机器的这个编号。范围1-15 */
	// 生成订单号
	public static function genOrderNo()
	{
		return date('Ymd').self::uniqidOfToday(config('server_id',1));
	}

	// 生成订单交易号。用于提交给支付平台
	public static function genOrderTradeNo($orderNo, $separator='-')
	{
		return $orderNo.$separator.date('ymd').self::uniqidOfToday(config('server_id',1));
	}

	// 生成随机数。uniqid本来就已经是微秒级别生成的了，默认又加了熵，更具唯一性
	public static function uniqid($more_entropy=true) {
		//$key=ftok(__FILE__,'b');	// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句
		//$sem_id=sem_get($key);		// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句
		//sem_acquire($sem_id);		// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句
		$s = uniqid('', $more_entropy);	//  more_entropy为false则返回的字符串长度为13。more_entropy为true，则返回的字符串长度为23，增加了d.12345678例如5707770ac994d3.72044900。
		//sem_release($sem_id);		// 因为php的windows版本没有ftok/sem_get/sem_acquire/sem_release，调试时注释掉这句

		if (!$more_entropy) {
			return base_convert(config('server_id',1).$s, 16, 36);
		}
		$hex = substr($s, 0, 13);
		$dec = $s[13] . substr($s, 15); // 去掉小数点。例如d.12345678得到d12345678
		return base_convert(config('server_id',1).$hex, 16, 36) . base_convert($dec, 10, 36);
	}

}