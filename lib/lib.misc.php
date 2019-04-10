<?php
namespace ccn\lib;

function one_of() {
    /**
     * returns the first non-empty argument
     */

    $arg_list = func_get_args();
    foreach ($arg_list as $arg) {
        if (!empty($arg)) return $arg;
    }
}

function eval_condition($condition) {
    /**
     * evaluates a condition, like in eval($condition)
     * but in a safer way (as the eval function can raise a fatal error that cannot be catched)
     * returns 
     * - 1 if condition is true 
     * - 0 if condition is false
     * - -1 if something is wrong with the expression
     */

    $condition = str_replace('"', "'", $condition);
    $final_cmd = "echo (( $condition ) ? 1: 0);";

    // check if php exists in shell_exec
    $php_exists = shell_exec('php -r "echo 123;"');
    if ($php_exists !== '123') {
        //log\warning('CANNOT EXECUTE shell_exec properly');
        try {
            $final_cmd = '$res = ('.$condition.') ? 1: 0;';
            eval($final_cmd);
            return $res;
        } catch (Excpetion $e) {
            log\error('INVALID_EXPRESSION_EVAL', 'In '.basename(__FILE__).' > eval_condition, for expression:"'.$final_cmd.'" error='.$e->getMessage());
            return -1;
        }
    }

    $res = shell_exec('php -r "'.$final_cmd.'" 2>&1');
    
    if ($res !== "1" && $res !== "0") {
        log\error('INVALID_EXPRESSION', 'In '.basename(__FILE__).' > eval_condition, for expression :"'.$final_cmd.'" res='.$res);
        return -1;
    } else return intval($res);
}

function eval_operation($operation, $force_type = 'string') {
    /**
     * evaluates an operation, like in eval($operation)
     * but in a safer way (as the eval function can raise a fatal error that cannot be catched)
     * 
     * returns the raw STRING output
     * or FALSE if syntax error
     * 
     * TODO : integrate a $force_type argument to force output type to integer or else
     */

    $operation = str_replace('"', '\\"', $operation);
    $final_cmd = "echo ($operation);";
    $res = shell_exec('php -r "'.$final_cmd.'"');
    if (preg_match("/^\nParse error/", $res)) {
        log\error('OPERATION_SYNTAX_ERROR', 'In '.basename(__FILE__).' > eval_operation, for expression :"'.$final_cmd.'"');
        return false;
    }
    return $res;
}

function get_callable_name($callable) {
    /**
     * Returns the name of a callable / a function
     * returns false if $callable is not callable
     */

    if (!is_callable($callable)) return false;

    if (is_string($callable)) {
        return trim($callable);
    } else if (is_array($callable)) {
        if (is_object($callable[0])) {
            return sprintf("%s::%s", get_class($callable[0]), trim($callable[1]));
        } else {
            return sprintf("%s::%s", trim($callable[0]), trim($callable[1]));
        }
    } else if ($callable instanceof Closure) {
        return 'closure';
    } else {
        return 'unknown';
    }
}

?>