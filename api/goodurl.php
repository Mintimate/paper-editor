<?php

require_once __DIR__ . '/class/ShortUrl.php';

$ss = new ShortUrl(__DIR__ . '/goodurl.db');
$url = filter_var($_GET['url'], FILTER_SANITIZE_URL);

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    $ss->redirectUrl($url);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 验证密码
$config = parse_ini_file(__DIR__ . '/../.env', true);
if (isset($config['password'])) {
    if (empty($_GET['password'])) {
        exit(json_encode(['code' => 403, 'msg' => '密码不能为空'], JSON_UNESCAPED_UNICODE));
    } elseif ($config['password'] != $_GET['password']) {
        exit(json_encode(['code' => 403, 'msg' => '密码错误'], JSON_UNESCAPED_UNICODE));
    }
}

// 创建短链接
$shortUrl = $config['base_url'] . $ss->createShortUrl($url, htmlspecialchars($_GET['title']));
exit(json_encode(['code' => 0, 'msg' => '创建成功', 'url' => $shortUrl], JSON_UNESCAPED_UNICODE));
