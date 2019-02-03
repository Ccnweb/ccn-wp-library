<?php

/**
 * README
 * 
 * Here we define useful functions to validate strings and maybe other things
 * 
 */

class Ccn_Validator {

    public static $bannedEmailDomains = array();

    function __construct() {
        // source: https://gist.github.com/michenriksen/8710649
        $string = file_get_contents(CCN_LIBRARY_PLUGIN_DIR . "resources/trash-email-domains.json");
        if ($string !== false) self::$bannedEmailDomains = json_decode($string, true);
    }

    public static function isValidField($str, $f) {
        /**
         * Tells if this is a valid string $str of type $type
         */
        $type = $f['type'];
        if ($type == 'email') {
            return self::isValidEmail($str);
        } else if ($type == 'postal_code') {
            $res = preg_match ( "/^[0-9]{5,5}$/" , $str );
            if ($res) return array('valid' => true);
            return array('valid' => false, 'reason' => 'INVALID_POSTALCODE', 'descr' => 'The postal code is invalid');
        } else if ($type == 'post_status') {
            $res = self::isValidPostStatus($str);
            if ($res) return array('valid' => true);
            return array('valid' => false, 'reason' => 'INVALID_POSTSTATUS', 'descr' => 'The postal status is invalid');
        } else {
            return array('valid' => true);
        }
    }
 
    public static function isValidEmail($str) {
        // we verify that the email address is properly formed
        $res = (function_exists('filter_var')) ? filter_var($str, FILTER_VALIDATE_EMAIL) : preg_match ( " /^[^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4}$/ " , $str );
        if ($res === false) return array("valid" => false, 'reason' => 'INVALID_EMAIL', 'descr' => 'The email address is invalid');
        
        // we verify that the email address domain is valid
        $domain = explode("@",$str)[1];
        if (in_array($domain, self::$bannedEmailDomains)) return array('valid' => false, 'reason' => 'INVALID_DOMAIN', 'descr' => 'The email address domain "'.$domain.'" is banned');

        return array('valid' => true);
    }

    public static function isValidPostStatus($str) {
        /**
         * Tells if this is a valid wordpress post status
         * @source https://codex.wordpress.org/Post_Status
         * 
         * @TODO find a cleaner way to do this with a built-in wordpress function
         */
        return in_array($str, array('publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit'));
    }
}

?>