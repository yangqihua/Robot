<?php
/**
 * Created by PhpStorm.
 * User: yangqihua
 * Date: 2017/12/27
 * Time: 下午8:54
 */

namespace app\handlers;

use Hanson\Vbot\Contact\Friends;
use Hanson\Vbot\Contact\Groups;
use Hanson\Vbot\Contact\Myself;
use Hanson\Vbot\Message\Text;
use Illuminate\Support\Collection;
use app\common\Http;

class MainHandler
{
    public static $baseUrl = 'http://127.0.0.1/quan/public/index.php/api/robot/';
//    public static $baseUrl = 'http://192.168.1.105/quan/public/index.php/api/robot/';

    public static function messageHandler(Collection $message, Friends $friends, Groups $groups, Myself $myself)
    {
        $type = $message['type'];
        switch ($type) {
            // 加好友请求
            case 'request_friend':
                vbot('console')->log('收到好友<' . $message['from']['NickName'] . '>申请，申请内容：' . $message['info']['Content']);
                $friends->approve($message);
                break;
            // 同意添加好友后发送 第一次 文案
            case 'new_friend':

//                vbot('console')->log('成功添加<' . $message['from']['NickName'] . '>为好友');
//                Text::send($message['from']['UserName'], self::getConfig('first'));
//
//                $result = Http::get(self::$baseUrl . 'add', ['content' => '',
//                    'user_id' => $message['from']['UserName'], 'user_name' => $message['from']['NickName'],
//                    'province' => $message['from']['Province'], 'city' => $message['from']['City'], 'sex' => $message['from']['Sex']]);
//
//                vbot('console')->log('同意添加好友后请求服务器返回<' . $result . '>');


                vbot('console')->log('成功添加<' . $message['from']['NickName'] . '>为好友');
                $result = Http::get(self::$baseUrl . 'add', ['content' => '',
                    'user_id' => $message['from']['UserName'], 'user_name' => $message['from']['NickName'],
                    'province' => $message['from']['Province'], 'city' => $message['from']['City'], 'sex' => $message['from']['Sex']]);
                vbot('console')->log('同意添加好友后请求服务器返回<' . $result . '>');
                $result = self::wrapResult($result);
                // 只有当服务器返回200 的时候才回复消息
                if ($result['code'] == 200 && $result['data']) {
                    Text::send($message['from']['UserName'], $result['data']);
                }
                break;
            // 处理 文案
            case 'text':
                $selfName = $myself->nickname;
                self::handlerText($message, $selfName);
                break;
            default:
                vbot('console')->log('<' . $message['from']['NickName'] . '>发送消息：' . $message['content']);

        }
    }

    // 处理 文案
    public static function handlerText($message, $selfName)
    {
        $fromType = $message['fromType'];
        $content = $message['content'];
        $username = $message['from']['UserName'];
        $isAtInGroup = $fromType == 'Group' && $message['isAt'];
        if ($fromType == 'Friend' || $isAtInGroup) {
            if ($isAtInGroup) {
                $groupName = $message['from']['NickName'] ? $message['from']['NickName'] : '无群名称';
                vbot('console')->log('<群：' . $groupName . ',成员：' . $message['sender']['NickName'] . '>发送消息：' . $content);
            } else {
                vbot('console')->log('<' . $message['from']['NickName'] . '>发送消息：' . $content);
            }
            if ($isAtInGroup) {
                $result = self::request($content, $username, $selfName,$message['sender']['NickName'], '1');
                vbot('console')->log('【群聊@】请求后台返回结果：<' . json_encode($result, JSON_UNESCAPED_UNICODE) . '>');
            } else {
                $result = self::request($content, $username, $selfName,$message['from']['NickName'], '0');
                vbot('console')->log('【私聊】请求后台返回结果：<' . json_encode($result, JSON_UNESCAPED_UNICODE) . '>');
            }

            // 只有当服务器返回200 的时候才回复消息
            if ($result['code'] == 200 && $result['data']) {
                Text::send($message['from']['UserName'], $result['data']);
            }
        }
    }

    public static function request($content, $username, $selfName,$senderName ,$isGroup = '0')
    {
        $url = self::$baseUrl . 'dispatchs';
        $result = Http::get($url,
            [
                'content' => $content,
                'user_id' => $username,
                'is_group' => $isGroup,
                'sender_name'=>$senderName,
                'self_name' => $selfName,
            ]
        );
        return self::wrapResult($result);

    }

    public static function wrapResult($result){
        self::log("服务器返回原始结果：".$result);
        if (stristr($result, "<!DOCTYPE html")) {
            return ['code' => 500, 'data' => '服务器异常'];
        }
        if ($result) {
            $result = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE || !$result) {
                return ['code' => 500, 'data' => 'json解析出错'];
            }
            return $result;
        } else {
            return ['code' => 500, 'data' => '没有返回结果'];
        }
    }

    public static function log($message)
    {
        vbot('console')->log($message);
    }

    public static function getConfig($key)
    {
        $config = [
            'first' => '一一一一添 加 成 功一一一一
送您2元现金，回复 余额 可查看 

输入宝贝信息，获取领券链接
返利教学： www.qu-gou.com/help.html

输入  购物   高额优惠券等你来领
输入  签到   可以领取签到红包
输入  余额   可以查看余额等信息
输入  提现   满10元机器人可以给你发红包
输入  帮助   可以查看指令',
        ];

        if (array_key_exists($key, $config)) {
            return $config[$key];
        } else {
            return '未知消息';
        }
    }
}
