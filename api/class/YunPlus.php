<?php

class YunPlus
{
    private $url;
    private $html;

    private $document;
    private $xpath;

    public function getArticle($id, $full = false)
    {
        $this->xpathInit('https://cloud.tencent.com/developer/article/' . $id);
        $subject = $this->xpathQuery('//h1[@class="article-title J-articleTitle"]');
        $content = $this->xpathQuery('//div[@class="rno-markdown J-articleContent"]');
        return [
            'url' => $this->url,
            'subject' => trim(strip_tags($subject)),
            'summary' => mb_substr(trim(strip_tags($content)), 0, 100),
            'content' => $full ? $content : '',
        ];
    }

    private function xpathInit($url)
    {
        $this->url = $url;
        $this->html = $this->httpRequest($url);

        $this->document = new DOMDocument();
        $this->document->loadHTML($this->html, LIBXML_NOERROR);
        $this->document->normalize();

        $this->xpath = new DOMXPath($this->document);
    }

    private function xpathQuery($rule, $i = 0)
    {
        $nodeList = $this->xpath->query($rule);
        if (isset($nodeList[$i])) {
            return $this->document->saveHTML($nodeList[$i]);
        }
    }

    private function httpRequest($url)
    {
        $url = 'http://cmd.rehiy.com/curl.php?url=' . urlencode($url);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        list($res, $err) = [curl_exec($ch), curl_errno($ch), curl_close($ch)];

        return $err ? '' : $res;
    }
}
