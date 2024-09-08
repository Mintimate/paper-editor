<?php

class ShortUrl
{
    private $db;
    private $dbFile;

    public function __construct($dbFile)
    {
        $this->dbFile = $dbFile;
        $this->db = new SQLite3($this->dbFile);
        $this->db->busyTimeout(5000);
        $this->initDatabase();
    }

    public function createShortUrl($longUrl, $title = '')
    {
        // 如果长链接已经存在，返回对应的短链接
        $query = $this->db->prepare('SELECT short_url FROM urls WHERE long_url=?');
        $query->bindValue(1, $longUrl, SQLITE3_TEXT);
        $result = $query->execute();
        if ($row = $result->fetchArray()) {
            return $row['short_url'];
        }
        // 如果长链接不存在，生成新的短链接
        $shortUrl = $this->generateShortUrl();
        $insertQuery = $this->db->prepare('INSERT INTO urls (short_url, long_url, title, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
        $insertQuery->bindValue(1, $shortUrl, SQLITE3_TEXT);
        $insertQuery->bindValue(2, $longUrl, SQLITE3_TEXT);
        $insertQuery->bindValue(3, $title, SQLITE3_TEXT);
        $insertQuery->execute();
        return $shortUrl;
    }

    public function redirectUrl($shortUrl)
    {
        $query = $this->db->prepare('SELECT long_url, visits, title, created_at FROM urls WHERE short_url=?');
        $query->bindValue(1, $shortUrl, SQLITE3_TEXT);
        $result = $query->execute();
        if (!$row = $result->fetchArray()) {
            echo 'URL not found.';
            exit;
        }
        // 更新访问次数
        $longUrl = $row['long_url'];
        $visits = $row['visits'] + 1;
        $updateQuery = $this->db->prepare('UPDATE urls SET visits=? WHERE short_url=?');
        $updateQuery->bindValue(1, $visits, SQLITE3_INTEGER);
        $updateQuery->bindValue(2, $shortUrl, SQLITE3_TEXT);
        $updateQuery->execute();
        // 记录访问日志
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $insertLogQuery = $this->db->prepare('INSERT INTO logs (short_url, visit_time, user_agent, ip_address) VALUES (?, CURRENT_TIMESTAMP, ?, ?)');
        $insertLogQuery->bindValue(1, $shortUrl, SQLITE3_TEXT);
        $insertLogQuery->bindValue(2, $userAgent, SQLITE3_TEXT);
        $insertLogQuery->bindValue(3, $ipAddress, SQLITE3_TEXT);
        $insertLogQuery->execute();
        // 重定向
        header('Location: ' . $longUrl);
        exit;
    }

    public function dailyStatistics($shortUrl)
    {
        $query = $this->db->prepare('SELECT DATE(visit_time) AS day, COUNT(*) AS visits FROM logs WHERE short_url=? GROUP BY day ORDER BY day');
        $query->bindValue(1, $shortUrl, SQLITE3_TEXT);
        $result = $query->execute();
        $statistics = [];
        while ($row = $result->fetchArray()) {
            $statistics[$row['day']] = $row['visits'];
        }
        return $statistics;
    }

    private function generateShortUrl()
    {
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $shortUrl = '';
        do {
            $id = mt_rand(0, pow(62, 6) - 1);
            $shortUrl = '';
            while ($id > 0) {
                $shortUrl = $alphabet[$id % 62] . $shortUrl;
                $id = floor($id / 62);
            }
        } while ($this->urlExists($shortUrl));
        return $shortUrl;
    }

    private function urlExists($url)
    {
        $query = $this->db->prepare('SELECT * FROM urls WHERE short_url=?');
        $query->bindValue(1, $url, SQLITE3_TEXT);
        $result = $query->execute();
        return $result->fetchArray() !== false;
    }

    private function initDatabase()
    {
        if (!$this->tableExists('urls')) {
            $this->db->exec('CREATE TABLE urls (id INTEGER PRIMARY KEY AUTOINCREMENT, short_url TEXT UNIQUE, long_url TEXT, title TEXT, visits INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        }

        if (!$this->tableExists('logs')) {
            $this->db->exec('CREATE TABLE logs (id INTEGER PRIMARY KEY AUTOINCREMENT, short_url TEXT, visit_time DATETIME DEFAULT CURRENT_TIMESTAMP, user_agent TEXT, ip_address TEXT)');
        }
    }

    private function tableExists($tableName)
    {
        $query = $this->db->prepare('SELECT name FROM sqlite_master WHERE type="table" AND name=?');
        $query->bindValue(1, $tableName, SQLITE3_TEXT);
        $result = $query->execute();
        return $result->fetchArray() !== false;
    }
}
