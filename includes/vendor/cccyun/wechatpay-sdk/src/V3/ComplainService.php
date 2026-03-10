<?php

namespace WeChatPay\V3;

/**
 * 消费者投诉服务类
 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/open/pay/chapter6_2_5.shtml
 */
class ComplainService extends BaseService
{
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * 查询投诉单列表
     * @param $begin_date 开始日期，格式为yyyy-MM-DD
     * @param $end_date 结束日期，格式为yyyy-MM-DD
     * @param $page_no 分页号，从1开始
     * @param $page_size 分页大小1-50，默认为10
     * @return mixed {"limit":10,"offset":0,"total_count":100,"data":[]}
     */
    public function batchQuery($begin_date, $end_date, $page_no = 1, $page_size = 10)
    {
        $path = '/v3/merchant-service/complaints-v2';
        $offset = $page_size * ($page_no - 1);
        $params = [
            'limit' => $page_size,
            'offset' => $offset,
            'begin_date' => $begin_date,
            'end_date' => $end_date
        ];
        return $this->execute('GET', $path, $params);
    }

    /**
     * 查询投诉单详情
     * @param $complaint_id 投诉单号
     * @return mixed
     */
    public function query($complaint_id)
    {
        $path = '/v3/merchant-service/complaints-v2/'.$complaint_id;
        return $this->execute('GET', $path);
    }

    /**
     * 查询投诉协商历史
     * @param $complaint_id 投诉单号
     * @return mixed
     */
    public function queryHistorys($complaint_id)
    {
        $path = '/v3/merchant-service/complaints-v2/'.$complaint_id.'/negotiation-historys';
        return $this->execute('GET', $path);
    }

    /**
     * 创建投诉通知回调地址
     * @param $url 通知地址
     * @return mixed
     */
    public function createNotifications($url)
    {
        $path = '/v3/merchant-service/complaint-notifications';
        $params = [
            'url' => $url
        ];
        return $this->execute('POST', $path, $params);
    }

    /**
     * 查询投诉通知回调地址
     * @return mixed {"mchid":"商户号","url":"通知地址"}
     */
    public function queryNotifications()
    {
        $path = '/v3/merchant-service/complaint-notifications';
        return $this->execute('GET', $path);
    }

    /**
     * 更新投诉通知回调地址
     * @param $url 通知地址
     * @return mixed
     */
    public function updateNotifications($url)
    {
        $path = '/v3/merchant-service/complaint-notifications';
        $params = [
            'url' => $url
        ];
        return $this->execute('PUT', $path, $params);
    }

    /**
     * 删除投诉通知回调地址
     * @return void
     */
    public function deleteNotifications()
    {
        $path = '/v3/merchant-service/complaint-notifications';
        $this->execute('DELETE', $path);
    }

    /**
     * 回复用户
     * @param $complaint_id 投诉单号
     * @param $complainted_mchid 被诉商户号
     * @param $response_content 回复内容
     * @param $response_images 回复图片列表
     * @return void
     */
    public function response($complaint_id, $complainted_mchid, $response_content, $response_images)
    {
        $path = '/v3/merchant-service/complaints-v2/'.$complaint_id.'/response';
        $params = [
            'complainted_mchid' => $complainted_mchid,
            'response_content' => $response_content,
            'response_images' => $response_images,
        ];
        $this->execute('POST', $path, $params);
    }

    /**
     * 反馈处理完成
     * @param $complaint_id 投诉单号
     * @param $complainted_mchid 被诉商户号
     * @return void
     */
    public function complete($complaint_id, $complainted_mchid)
    {
        $path = '/v3/merchant-service/complaints-v2/'.$complaint_id.'/complete';
        $params = [
            'complainted_mchid' => $complainted_mchid,
        ];
        $this->execute('POST', $path, $params);
    }

    /**
     * 更新退款审批结果
     * @param $complaint_id 投诉单号
     * @param $params 请求参数
     * @return void
     */
    public function updateRefundProgress($complaint_id, $params)
    {
        $path = '/v3/merchant-service/complaints-v2/'.$complaint_id.'/update-refund-progress';
        $this->execute('POST', $path, $params);
    }

    /**
     * 上传反馈图片
     * @param $file_path 文件路径
     * @param $file_name 文件名
     * @return string
     */
    public function uploadImage($file_path, $file_name)
    {
        $path = '/v3/merchant-service/images/upload';
        $result = $this->upload($path, $file_path, $file_name);
        return $result['media_id'];
    }

    /**
     * 下载图片
     * @param $media_id 媒体文件标识ID
     * @return string
     */
    public function getImage($media_id)
    {
        $url = self::$GATEWAY.'/v3/merchant-service/images/'.urlencode($media_id);
        $result = $this->download($url);
        return $result;
    }
}