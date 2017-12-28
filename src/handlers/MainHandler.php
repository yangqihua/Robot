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
    public static $baseUrl = 'http://192.168.1.105/quan/public/index.php/api/robot/';

    public static function messageHandler(Collection $message, Friends $friends, Groups $groups, Myself $myself)
    {
        $type = $message['type'];
        switch ($type) {
            // 加好友请求
            case 'request_friend':
                vbot('console')->log('收到好友<' . $message['from']['NickName'] . '>申请，申请内容：' . $message['info']['Content']);
                $friends->approve($message);
                $result = Http::get(self::$baseUrl . 'add', ['content' => $message['info']['Content'],
                    'user_id' => $message['from']['UserName'], 'user_name' => $message['from']['NickName'],
                    'province' => $message['from']['Province'], 'city' => $message['from']['City'], 'sex' => $message['from']['Sex']]);
                vbot('console')->log('同意添加好友后请求服务器返回<' . $result . '>');
                break;
            // 同意添加好友后发送 第一次 文案
            case 'new_friend':
                vbot('console')->log('成功添加<' . $message['from']['NickName'] . '>为好友');
                Text::send($message['from']['UserName'], self::getConfig('first'));
                break;
            // 处理 文案
            case 'text':
                $selfName = $myself['NickName'];
                self::handlerText($message, $myself);
                break;
            default:
                vbot('console')->log('<' . $message['from']['NickName'] . '>发送消息：' . $message['content']);

        }
    }

    // 处理 文案
    public static function handlerText($message, $selfName)
    {
        $fromType = $message['fromType'];
        $content = trim($message['isAt'] ? $message['pure'] : $message['content']);
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
                $result = self::request($content, $username, $selfName, '1');
                vbot('console')->log('【群聊@】请求后台返回结果：<' . \GuzzleHttp\json_encode($result, JSON_UNESCAPED_UNICODE) . '>');
            } else {
                $result = self::request($content, $username, $selfName, '0');
                vbot('console')->log('【私聊】请求后台返回结果：<' . \GuzzleHttp\json_encode($result, JSON_UNESCAPED_UNICODE) . '>');
            }

            // 只有当服务器返回200 的时候才回复消息
            if ($result['code'] == 200) {
                Text::send($message['from']['UserName'], $result['data']);
            }
        }
    }

    public static function request($content, $username, $selfName, $isGroup = '0')
    {
        $url = self::$baseUrl . 'dispatchs';
//        $url = 'http://192.168.1.105/quan/public/index.php/api/robot/dispatchs';
        $result = Http::get($url,
            [
                'content' => $content,
                'user_id' => $username,
                'is_group' => $isGroup,
                'self_name' => $selfName,
            ]
        );
        self::log("服务器服务原始数据" . $result);
        if (stristr($result, "<!DOCTYPE html")) {
            return ['code' => 500, 'data' => ''];
        }
        if ($result) {
            $result = \GuzzleHttp\json_decode($result, true);
            if (!$result) {
                return ['code' => 500, 'data' => ''];
            }
            return $result;
        } else {
            return ['code' => 500, 'data' => ''];
        }

    }

    public static function log($message)
    {
//        vbot('console')->log($message);
    }

    public static function getConfig($key)
    {
        $config = [
            'first' => '一一一一添 加 成 功一一一一
送您2元现金，回复 余额 可查看 

输入宝贝信息，获取领券链接
返利教学： http://t.cn/RHcBT0x

输入  签到   可以领取签到红包
输入  余额   可以查看余额等信息
输入  提现   满10元机器人可以给你发红包
输入  帮助   可以查看指令',

            'help' => '一一一一帮 助一一一一

输入宝贝信息，获取领券链接
返利教学： http://t.cn/RHcBT0x

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
