<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

namespace Csulok0000\DevTools;

class Debug {
    
    protected static string $root = '';
    
    /**
     * 
     * @param string $root
     */
    public static function setRoot(string $root) {
        self::$root = self::shortPath($root);
    }
    
    /**
     * 
     * @param mixed $var
     * @param bool $exit
     * @param array $backTrace
     */
    public static function show(mixed $var = Enum\Debug::NO_PARAM, bool $exit = true, array $backTrace = []): void {
        if (!$backTrace) {
            $backTrace = debug_backtrace();
        }
    
        //
        // Ajax
        //
        if (filter_input(INPUT_SERVER, 'CONTENT_TYPE') == 'application/json') {
            self::printJSON($var, $backTrace);
        }

        //
        // CLI
        //
        elseif (in_array(PHP_SAPI, ['cli']) || !isset($_SERVER['HTTP_HOST']) || isset($_SERVER['term'])) {
            self::printCLI($var, $backTrace);
        }

        //
        // Web
        //
        else {
            self::printHTML($var, $backTrace);
        }
        
        // Stop running
        if ($exit) {
            exit;
        }
    }
    
    protected static function printJSON(mixed $var, array $backTrace): void {
        // Clear OB
        while (ob_get_length()) {
            ob_end_clean();
        }

        // Set JSON header
        header('Content-type: application/json');

        // Print debug info
        json_encode([
            'debug' => var_export($var, true),
            'file' => str_replace(base_path(), '', $backTrace[0]['file']),
            'line' => $backTrace[0]['line'],
            'trace' => $backTrace
        ]);
    }
    
    protected static function printCLI(mixed $var, array $backTrace): void {
        $position = self::shortPath($backTrace[0]['file']) . ' at line ' . $backTrace[0]['line'];
        echo PHP_EOL;
        echo '┌' . str_repeat('─', strlen($position) + 11) . '┐' . PHP_EOL;
        echo "│  DEBUG: " . $position . '  │' . PHP_EOL;
        echo '└' . str_repeat('─', strlen($position) + 11) . '┘' . PHP_EOL;
        echo PHP_EOL;
        echo " Dump: " . PHP_EOL;
        echo " ══════" . PHP_EOL;
        echo PHP_EOL;
        if (is_array($var) && $var) {
            print_r($var);
        } else {
            var_dump($var);
        }
        
        echo PHP_EOL;
        echo " Trace(s): " . PHP_EOL;
        echo " ══════════" . PHP_EOL;
        echo PHP_EOL;
        unset($backTrace[0]);

        foreach ($backTrace as $trace) {
            $callable = [];
            $args = [];
            if (isset($trace['class'])) {
                $callable[] = $trace['class'];
            }

            if (isset($trace['function'])) {
                $callable[] = $trace['function'];
                foreach ($trace['args'] as $arg) {
                    $args[] = gettype($arg) == 'object'? get_class($arg) : gettype($arg);
                }
            }

            if (isset($trace['file'])) {
                echo self::shortPath($trace['file']) . '@' . $trace['line'];
            }
            if ($callable) {
                echo ': ' . implode('::', $callable) . '(' . implode(',', $args) . ')';
            }
            echo PHP_EOL;
        }
    }
    
    protected static function printHTML(mixed $var, array $backTrace): void {?>
        <div style="border: solid 1px #8CF; display: inline-block;">
            <div style="background: #ADF; padding: 3px 10px;">Debug in <b><?php echo $d[0]['file'];?></b> on line <b><?php echo $d[0]['line'];?></b></div>
            <pre style="padding: 10px;"><?php
                if ($var === Enum\Debug::NO_PARAM) {
                    echo ""; 
                }
                elseif (is_array($var)) {
                    print_r($var);
                } else {
                    var_dump($var);
                }
            ?></pre>
            <div style="font-size: 0.9rem; padding: 10px;">
                <?php
                unset($backTrace[0]);

                foreach ($backTrace as $trace) {
                    ?><div><?php
                    $callable = [];
                    $args = [];
                    if (isset($trace['class'])) {
                        $callable[] = $trace['class'];
                    }

                    if (isset($trace['function'])) {
                        $callable[] = $trace['function'];
                        foreach ($trace['args'] as $arg) {
                            $args[] = gettype($arg) == 'object'? get_class($arg) : gettype($arg);
                        }
                    }

                    if (isset($trace['file'])) {
                        ?><b><?php echo $trace['file'];?></b> on line <b><?php echo $trace['line'];?></b>
                    <?php }?>
                    <?php if ($callable) {?>
                        : <b style="color: #03A;"><?php echo implode('::', $callable);?>(<?php echo implode(',', $args);?>)</b>
                    <?php }?>
                    </div>
                <?php }?>
            </div>
        </div>
        <?php
    }
    
    protected static function shortPath($path) {
        $unixPath = str_replace('\\', '/', $path);
        
        // Vendor
        if (($pos = strpos($unixPath, '/vendor/')) !== false) {
            $unixPath = '[vendor]/' . substr($unixPath, $pos + 8);
        }
        
        // Root
        if (self::$root && ($pos = strpos($unixPath, self::$root)) !== false) {
            $unixPath = '[root]/' . substr($unixPath, $pos + strlen(self::$root) + 1);
        }
        
        return $unixPath;
    }
}
