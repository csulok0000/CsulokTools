<?php

/**
 * 
 * @author Tibor Csik <csulok0000@gmail.com>
 */

namespace Csulok0000\DevTools;

class Debug {
    
    public static function d(mixed $var = Enum\Debug::NO_PARAM, $exit = true) {
        $d = debug_backtrace();
    
        //
        // Ajaxos kérés esetén
        //
        if (filter_input(INPUT_SERVER, 'CONTENT_TYPE') == 'application/json') {
            while (ob_get_length()) {
                ob_end_clean();
            }
            ob_start();
            echo 'Debug: ' . $d[0]['file'] . ' at line ' . $d[0]['line'] . "\n\n";
            if (is_array($var) && $var) {
                print_r($var);
            } else {
                var_dump($var);
            }

            $res = ob_get_contents();
            ob_end_clean();
            die(new AjaxResponse(false, $res));
        }

        //
        // Parancssor számára
        //
        elseif (in_array(PHP_SAPI, ['cli']) || !isset($_SERVER['HTTP_HOST']) || isset($_SERVER['term'])) {
            echo PHP_EOL . "---------------------------- DEBUG ---------------------------------" . PHP_EOL . PHP_EOL;

            if (is_array($var) && $var) {
                print_r($var);
            } else {
                var_dump($var);
            }
            echo PHP_EOL . '[' . $d[0]['file'] . '] at line ' . $d[0]['line'] . PHP_EOL . PHP_EOL;
            if ($exit) {
                exit();
            }
        }

        //
        // Normál esetben
        //
        else {
            ob_start();
            ?>
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
                        }?></pre>
                    <div style="font-size: 0.9rem; padding: 10px;">
                    <?php
                        unset($d[0]);

                        foreach ($d as $trace) {
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

            if ($exit) {
                ob_end_flush();
                exit;
            }
    
            return ob_get_clean();
        }
    }
}
