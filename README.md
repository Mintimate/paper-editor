# 腾讯云技术早报编辑器

技术早报编辑器是由腾云先锋成员（TDP-若海）所编写的一款微信群技术早报消息生成器，为早报发布人员提供了便捷的早报内容生成，省去了大量的文章内容摘取时间。

## 安装说明

- 请将 `.env-example` 重命名为 `.env`，并根据需要修改配置

- Nginx 伪静态配置

```nginx
location ~* \.(db|env)$ {
    return 403;
}
if (!-e $request_filename) {
    rewrite ^/(\w+)$ /api/goodurl.php?url=$1 last;
}
```

## 维护人员

| 昵称 | Github 主页                  |
| ---- | ---------------------------- |
| 若海 | https://github.com/rehiy     |
| jwj  | https://github.com/big-dream |

## 其他

演示地址 https://e.tdp.fan
