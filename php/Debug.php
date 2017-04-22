<?php
/**
 * @name Mj_Debug
 * @desc debug
 * 供线上case追查用
 * @author checkking
 */

class Mj_Debug
{
    const DEBUG_PREFIX = 'MJDEBUG_';
    const KEY_DEBUG_ID = 'debugID';
    const KEY_REQUEST = 'request';
    const KEY_PROCESS = 'process';
    const KEY_RESPONSE = 'response';
    const SEP_KEY_CHAR = '.';
    const KEY_EXPIRE_SECONDS = 60;

    private static $requiredFeilds = array(
        Mj_Debug::KEY_DEBUG_ID,
        Mj_Debug::KEY_REQUEST,
        Mj_Debug::KEY_PROCESS,
        Mj_Debug::KEY_RESPONSE,
    );

    private static $instance = array();

    private $key = '';
    private $value = array();
    private $cached = false;
    private $cachable = true;

    /**
     * 私有构造函数
     * @param string $deubgId
     * @param bool $cachable
     */
    private function __construct($debugId, $cachable = true) {
        $this->key .= Mj_Debug::DEBUG_PREFIX . $debugId;
        $this->value[Mj_Debug::KEY_DEBUG_ID] = $debugId;
        $this->cachable = $cachable;
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        if ($this->cachable && !$this->cached) {
            $this->writeToCache();
        }
    }

    /**
     * 获取debug实例
     * @return Mj_Debug 实例
     */
    public static function getInstance($debugId, $cachable = false) {
        if (isset(self::$instance[$debugId])) {
            return self::$instance[$debugId];
        }
        self::$instance[$debugId] = new self($debugId, $cachable);
        return self::$instance[$debugId];
    }

    /**
     * 添加debug信息
     * @param string $key 添加的位置，如 openoffer.filter.offer
     * @param mixed $info 添加的信息
     * @param bool $flag 是否覆盖
     */
    public function addDebug($key, $info, $flag = false) {
        $offset = 0;
        $keyLen = strlen($key);
        $key = trim($key, Mj_Debug::SEP_KEY_CHAR);
        $cur = $this->value;
        while ($found = strpos($key, Mj_Debug::SEP_KEY_CHAR, $offset)) {
           $tmp = substr($key, $offset, $found - $offset); 
           if (!isset($cur[$tmp])) {
               $cur[$tmp] = array();
           }
           $cur = $cur[$tmp];
           $offset = $found + 1;
        }
        $lastKey = substr($key, $offset);
        if (!isset($cur[$lastKey])) {
            $cur[$lastKey] = array();
        }
        if ($flag) {
            $cur[$lastKey] = $info;
        } else {
            $cur[$lastKey][] = $info;
        }
    }

    public function writeToCache() {
        if ($tihs->cached || !$this->cachable) {
            return;
        }
        $redis = Mj_Redis::getInstance();
        if (!isset($redis)) {
            return;
        }
        $redis->set($this->key, json_encode($this->value));
        $redis->setTimeout($this->key, Mj_Debug::KEY_EXPIRE_SECONDS);
        $this->cached = true;
    }

    /**
     * 获得bug信息
     * @return array $value
     */
    public function getDebugInfo() {
        return $this->value;
    }
}
