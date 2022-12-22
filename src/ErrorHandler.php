<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

namespace Csulok0000\DevTools;

use Csulok0000\DevTools\ViewInterface;
use Psr\Log\LoggerInterface;

class ErrorHandler {
    
    /**
     * 
     * @var array
     */
    protected array $errorLevels = [
        E_ERROR               => 'E_ERROR',
        E_WARNING             => 'E_WARNING',
        E_PARSE               => 'E_PARSE',
        E_NOTICE              => 'E_NOTICE',
        E_CORE_ERROR          => 'E_CORE_ERROR',
        E_CORE_WARNING        => 'E_CORE_WARNING',
        E_COMPILE_ERROR       => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING     => 'E_COMPILE_WARNING',
        E_USER_ERROR          => 'E_USER_ERROR',
        E_USER_WARNING        => 'E_USER_WARNING',
        E_USER_NOTICE         => 'E_USER_NOTICE',
        E_STRICT              => 'E_STRICT',
        E_RECOVERABLE_ERROR   => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED          => 'E_DEPRECATED',
        E_USER_DEPRECATED     => 'E_USER_DEPRECATED',
        E_ALL                 => 'E_ALL'
    ];
    
    /**
     * 
     * @param \Psr\Log\LoggerAwareInterface|null $logger
     * @param ViewInterface|null $view
     * @param string $logSessionId
     * @param string $userAgent
     * @param string $requestUrl
     */
    public function __construct(
            protected ?\Psr\Log\LoggerAwareInterface $logger,
            protected ?ViewInterface $view,
            protected string $logSessionId = '',
            protected string $userAgent = '',
            protected string $requestUrl = ''
    ) {
        
        // Set userAgent
        if (!$userAgent && isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        if (!$requestUrl) {
            $this->requestUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        }
    }
    
    static private function _debugLog($logMethod, $type, $trace = null) {
        //
        // "Kapot" paraméterek
        //
        !$_GET      or call_user_func([__CLASS__, $logMethod], $type . "\tGET: " . json_encode($_GET));
        !$_POST     or call_user_func([__CLASS__, $logMethod], $type . "\tPOST: " . json_encode($_POST));
        !$_COOKIE   or call_user_func([__CLASS__, $logMethod], $type . "\tCOOKIE: " . json_encode($_COOKIE));
        !$_SESSION  or call_user_func([__CLASS__, $logMethod], $type . "\tSESSION: " . json_encode($_SESSION));
        
        //
        // Server
        //
        call_user_func([__CLASS__, $logMethod], $type . "\tSERVER: " . json_encode($_SERVER));
        
        //
        // Debug backtrace
        //
        if ($trace) {
            foreach ($trace as $btItem) {
                $btItem['file'] = isset($btItem['file']) ? $btItem['file'] : '';
                $btItem['class'] = isset($btItem['class']) ? $btItem['class'] : '';
                $btItem['line'] = isset($btItem['line']) ? $btItem['line'] : '';
                
                $args = [];
                
                if (isset($btItem['args'])) {
                    foreach ($btItem['args'] as $arg) {
                        switch (gettype($arg)) {
                            case 'boolean':             $args[] = $arg ? 'true' : 'false'; break;
                            case 'integer':             $args[] = $arg; break;
                            case 'double':              $args[] = $arg; break;
                            case 'string':              $args[] = '"' . $arg . '"'; break;
                            case 'array':               $args[] = '[array]'; break;
                            case 'object':              $args[] = '[' . get_class($arg) . ']'; break;
                            case 'resource':            $args[] = '[resource]'; break;
                            case 'resource (closed)':   $args[] = '[resource(closed)]'; break;
                            case 'NULL':                $args[] = 'null'; break;
                            default:                    $args[] = 'Unknown'; break;
                        }
                    }
                    $message = $btItem['class'] . '::' . $btItem['function'] . '(' . implode(', ', $args) . ') in ' . $btItem['file'] . ' at line ' . $btItem['line'];
                    call_user_func([__CLASS__, $logMethod], $type . "\tTRACE: " . $message);
                }
            }
        }
    }
    
    /**
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     */
    public function error($errno , $errstr, $errfile, $errline, $errcontext = array(), $exit = false) {

        $exit = $exit || in_array($errno, array(E_ERROR, E_CORE_ERROR, E_RECOVERABLE_ERROR)) || ENVIRONMENT != 'prod';
        $message = "[$errno]\t$errstr\tFile: $errfile\t Line: $errline\t[{$this->requestUrl}]\t({$this->userAgent})";
        self::errorLog(($exit ? ' X' : '') . "\t$message");
        
        if ($exit) {
            self::_debugLog('errorLog', ($exit ? ' X' : ''));
            $this->show('500 Internal Server Error', $message . (isset($this->errorLevels[$errno]) ? ' [' . $this->errorLevels[$errno] . ']' : ''));
        }
    }
    
    /**
     * 
     * @param Exception|Error $e
     */
    public function exception($e) {
        $this->show('500 Internal Server Error', $message);
    }
    
    /**
     * 
     * @return void
     */
    public function shutDown() {
        $error = error_get_last();
        if ($error) {
            $this->error($error['type'], $error['message'], $error['file'], $error['line'], array(), true);
            $this->show();
        }
    }
    
    /**
     * 
     * @param string $code
     * @param string $message
     */
    public function show($code = '500 Internal Server Error', $message = '') {
        
        // Clear buffer if not empty
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // a korábbi tömörítés miatt, rossz content-encoding-ot küld ki a php, ezért 
        ob_start('ob_gzhandler');
        
        if (!headers_sent()) {
            header('HTTP/1.1 ' . $code);
            if (class_exists('Debugger')) {
                Debugger::data('HTTP CODE: ' . $code);
            }
        }
        if (ENVIRONMENT == 'prod' || !$message) {
            $message = 'Az oldal generálása közben hiba történt. Kérjük próbálja újra később.';
        }
        
        //
        // Éles környezet számára, vagy ha nincs üzenet
        //
        if (filter_input(INPUT_POST, 'requestType') || filter_input(INPUT_POST, 'requestType') == 'json' || filter_input(INPUT_SERVER, 'CONTENT_TYPE') == 'application/json') {
            echo new Response(array(
                'success' => false,
                'message' => $message
            ), Response::TYPE_AJAX);
            
            if (class_exists('Debugger')) {
                Debugger::data(ob_get_contents());
            }
            exit;
        }
        
        //
        // Parancssor számára
        //
        elseif (in_array(PHP_SAPI, ['cli']) || !isset($_SERVER['HTTP_HOST']) || isset($_SERVER['term'])) {
            echo PHP_EOL . "---------------------------- ERROR ---------------------------------" . PHP_EOL . PHP_EOL;
            echo $message . PHP_EOL . PHP_EOL;
            if (class_exists('Debugger')) {
                Debugger::data(ob_get_contents());
            }
            exit;
        }
        
        //
        // Normál működés esetén fejlesztői környezetben
        //
        else {
            echo str_replace('{message}', $message, file_get_contents(TEMPLATE_DIR . '/500.html'));
            
            if (class_exists('Debugger')) {
                Debugger::data(ob_get_contents());
            }
            exit;
        }
    }
    
    /**
     * Register error handlers
     */
    public function register() {
        set_error_handler(array($this, 'error'));
        set_exception_handler(array($this, 'exception'));
        register_shutdown_function(array($this, 'shutDown'));
    }
    
    
    static private function _log($file, $message) {
        
        if (!self::$logID) {
            self::$logID = substr(md5(uniqid()), 5, 8);
        }
        
        $limit = 100000;
        if (strlen($message) > $limit) { // 100 000 karakter felett levágja
            $message = substr($message, 0, $limit) . '...';
        }
        
        file_put_contents(LOG_DIR . '/' . $file, date('Y-m-d H:i:s') . "\t" . self::$logID . "\t" . str_replace(["\n", "\r", "\n\r"], ["\\n", "\\r", "\\n\\r"], $message) . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * 
     * @param string $message
     */
    static public function errorLog($message) {
        self::_log('error.log', $message);
    }
    
    /**
     * 
     * @param string $message
     */
    static public function exceptionLog($message) {
        self::_log('exception.log', $message);
    }
    
    /**
     * 
     * @param string $message
     */
    static public function deadlockLog($message) {
        self::_log('deadlock.log', $message);
    }
    
    /**
     * 
     * @param string $message
     */
    static function notFoundLog($message) {
        self::_log('notfound.log', $message);
    }
    
}