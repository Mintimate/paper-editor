<?php

$url = $_GET['url'] ?? '';
$act = $_GET['act'] ?? 'redirect';

require_once __DIR__ . '/class/ShortUrl.php';
$ss = new ShortUrl(__DIR__ . '/goodurl.db');

// 重定向
if ($act == 'redirect') {
    $ss->redirectUrl($url);
    exit;
}

// 输出json
function jsonExit($data)
{
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode($data, JSON_UNESCAPED_UNICODE));
}

// 验证密码
$config = parse_ini_file(__DIR__ . '/../.env', true);
if (isset($config['password'])) {
    if (empty($_GET['password'])) {
        jsonExit(['code' => 403, 'msg' => '密码不能为空']);
    }
    if ($config['password'] != $_GET['password']) {
        jsonExit(['code' => 403, 'msg' => '密码错误']);
    }
}

// 所有短链
if ($act == 'list') {
    jsonExit($ss->getShortUrls());
}

// 统计短链
if ($act == 'daily') {
    jsonExit($ss->dailyStatistics($url));
}

// 创建短链
if ($act == 'create') {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        jsonExit(['code' => 401, 'msg' => '链接格式错误']);
    }
    if (stripos($url, $config['base_url']) === 0) {
        jsonExit(['code' => 401, 'msg' => '请勿输入短链接']);
    }
    $shortUrl = $config['base_url'] . $ss->createShortUrl($url, htmlspecialchars($_GET['title']));
    jsonExit(['code' => 0, 'msg' => '创建成功', 'url' => $shortUrl]);
}
