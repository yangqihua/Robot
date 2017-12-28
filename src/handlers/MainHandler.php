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
use Hanson\Vbot\Message\Text;
use Illuminate\Support\Collection;
use app\common\Http;

class MainHandler
{
    public static $baseUrl = 'http://yangqihua.com/quan/public/index.php/api/robot/';

    public static function messageHandler(Collection $message, Friends $friends, Groups $groups)
    {
        $type = $message['type'];
        switch ($type) {
            // 加好友请求
            case 'request_friend':
                vbot('console')->log('收到好友<' . $message['from']['NickName'] . '>申请，申请内容：' . $message['info']['Content']);
                $friends->approve($message);
                $result = Http::get(self::$baseUrl . 'add', ['content' => $message['content'], 'user_id' => $message['from']['UserName']]);
                vbot('console')->log('好友请求服务器返回<' . \GuzzleHttp\json_encode($result) . '>');
                break;
            // 同意添加好友后发送 第一次 文案
            case 'new_friend':
                vbot('console')->log('成功添加<' . $message['from']['NickName'] . '>为好友');
                Text::send($message['from']['UserName'], self::getConfig('first'));
                break;
            // 处理 文案
            case 'text':
                self::handlerText($message);
                break;
            default:
                vbot('console')->log('<' . $message['from']['NickName'] . '>发送消息：' . $message['content']);

        }
    }

    // 处理 文案
    public function handlerText($message)
    {
        $fromType = $message['fromType'];
        $content = $message['isAt'] ? $message['pure'] : $message['content'];
        $username = $message['from']['UserName'];
        if ($fromType == 'Friend' || ($fromType == 'Group' && $message['isAt'])) {
            vbot('console')->log('<' . $message['from']['NickName'] . '>发送消息：' . $content);
            switch ($content) {
                case '帮助':
                    Text::send($message['from']['UserName'], self::getConfig('help'));
                    break;
                // 其他文案 后台去处理
                default:
                    $result = self::request($content, $username);
                    vbot('console')->log('请求后台返回结果：<' . \GuzzleHttp\json_encode($result) . '>');
                    if ($result['code'] == 200) {
                        Text::send($message['from']['UserName'], $result['data']);
                    }
                    break;
            }
        }
    }

    public static function request($content, $username)
    {
        $url = self::$baseUrl . 'request';
        $result = Http::get($url,
            [
                'content' => $content,
                'user_id' => $username
            ]
        );
        return $result;
    }

    public static function getConfig($key)
    {
        $config = [
            'first' => '
一一一一添 加 成 功一一一一
送您2元现金，回复 余额 可查看 

输入宝贝信息，获取领券链接
返利教学： http://t.cn/RHcBT0x

输入  签到   可以领取签到红包
输入  余额   可以查看余额等信息
输入  提现   满10元机器人可以给你发红包
输入  帮助   可以查看指令',

            'help' => '
一一一一帮 助一一一一

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
