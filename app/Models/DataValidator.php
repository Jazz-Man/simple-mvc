<?php
    namespace Models;

    use Exception;

    /**
     * Class DataValidator
     *
     * @package Models
     */
    class DataValidator extends Model
    {
        public static $basic_tags = '<br><p><a><strong><b><i><em><img><blockquote><code><dd><dl><hr><h1><h2><h3><h4><h5><h6><label><ul><li><span><sub><sup>';
        public static $en_noise_words
            = "about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,
                                     being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,
                                     got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,its,it's,like,
                                     make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,
                                     our,out,over,said,same,see,should,since,some,still,such,take,than,that,
                                     the,their,them,then,there,these,they,this,those,through,to,too,under,up,
                                     very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,
                                     b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_";
        protected static $instance;
        protected static $fields = [];
        protected static $validation_methods = [];
        protected static $filter_methods = [];
        protected $validation_rules = [];
        protected $filter_rules = [];
        protected $errors = [];
        protected $fieldCharsToRemove = ['_', '-'];

        /**
         * @param $field
         * @param $readable_name
         */
        public static function set_field_name($field, $readable_name)
        {
            self::$fields[$field] = $readable_name;
        }

        /**
         * @param array $array
         */
        public static function set_field_names(array $array)
        {
            foreach ($array as $field => $readable_name) {
                self::$fields[$field] = $readable_name;
            }
        }

        /**
         * @param array $data
         * @param array $validators
         *
         * @return array|bool|null|string
         */
        public static function is_valid(array $data, array $validators)
        {
            $gump = self::get_instance();
            $gump->validation_rules($validators);
            if ($gump->run($data) === false) {
                return $gump->get_readable_errors(false);
            } else {
                return true;
            }
        }

        /**
         * @return \Models\DataValidator
         */
        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * @param array $rules
         *
         * @return array
         */
        public function validation_rules(array $rules = [])
        {
            if (empty($rules)) {
                return $this->validation_rules;
            }
            $this->validation_rules = $rules;
        }

        /**
         * @param array $data
         * @param bool  $check_fields
         *
         * @return array|bool
         */
        public function run(array $data, $check_fields = false)
        {
            $data = $this->filter($data, $this->filter_rules());
            $validated = $this->validate(
                $data, $this->validation_rules()
            );
            if ($check_fields === true) {
                $this->check_fields($data);
            }
            if ($validated !== true) {
                return false;
            }

            return $data;
        }

        /**
         * @param array $input
         * @param array $filterset
         *
         * @return array
         * @throws \Exception
         */
        public function filter(array $input, array $filterset)
        {
            foreach ($filterset as $field => $filters) {
                if ( ! array_key_exists($field, $input)) {
                    continue;
                }
                $filters = explode('|', $filters);
                foreach ($filters as $filter) {
                    $params = null;
                    if (strpos($filter, ',') !== false) {
                        $filter = explode(',', $filter);
                        $params = array_slice($filter, 1, count($filter) - 1);
                        $filter = $filter[0];
                    }
                    if (is_callable([$this, 'filter_' . $filter])) {
                        $method        = 'filter_' . $filter;
                        $input[$field] = $this->$method($input[$field], $params);
                    } elseif (function_exists($filter)) {
                        $input[$field] = $filter($input[$field]);
                    } elseif (isset(self::$filter_methods[$filter])) {
                        $input[$field] = call_user_func(self::$filter_methods[$filter], $input[$field], $params);
                    } else {
                        throw new Exception("Filter method '$filter' does not exist.");
                    }
                }
            }

            return $input;
        }

        /**
         * @param array $rules
         *
         * @return array
         */
        public function filter_rules(array $rules = [])
        {
            if (empty($rules)) {
                return $this->filter_rules;
            }
            $this->filter_rules = $rules;
        }

        /**
         * @param array $input
         * @param array $ruleset
         *
         * @return array|bool
         * @throws \Exception
         */
        public function validate(array $input, array $ruleset)
        {
            $this->errors = [];
            foreach ($ruleset as $field => $rules) {
                $rules = explode('|', $rules);
                if (in_array('required', $rules) || (isset($input[$field]) && ! is_array($input[$field]))) {
                    foreach ($rules as $rule) {
                        $method = null;
                        $param  = null;
                        if (strpos($rule, ',') !== false) {
                            $rule   = explode(',', $rule);
                            $method = 'validate_' . $rule[0];
                            $param  = $rule[1];
                            $rule   = $rule[0];
                        } else {
                            $method = 'validate_' . $rule;
                        }
                        if (is_callable([$this, $method])) {
                            $result = $this->$method(
                                $field, $input, $param
                            );
                            if (is_array($result)) {
                                $this->errors[] = $result;
                            }
                        } elseif (isset(self::$validation_methods[$rule])) {
                            $result = call_user_func(self::$validation_methods[$rule], $field, $input, $param);
                            if ($result === false) {
                                $this->errors[] = [
                                    'field' => $field,
                                    'value' => $input,
                                    'rule'  => self::$validation_methods[$rule],
                                    'param' => $param,
                                ];
                            }
                        } else {
                            throw new Exception("Validator method '$method' does not exist.");
                        }
                    }
                }
            }

            return (count($this->errors) > 0) ? $this->errors : true;
        }

        /**
         * @param array $data
         */
        private function check_fields(array $data)
        {
            $ruleset  = $this->validation_rules();
            $mismatch = array_diff_key($data, $ruleset);
            $fields   = array_keys($mismatch);
            foreach ($fields as $field) {
                $this->errors[] = [
                    'field' => $field,
                    'value' => $data[$field],
                    'rule'  => 'mismatch',
                    'param' => null,
                ];
            }
        }

        /**
         * @param bool   $convert_to_string
         * @param string $field_class
         * @param string $error_class
         *
         * @return array|null|string
         */
        public function get_readable_errors(
            $convert_to_string = false, $field_class = 'gump-field', $error_class = 'gump-error-message'
        ) {
            if (empty($this->errors)) {
                return $convert_to_string ? null : [];
            }
            $resp = [];
            foreach ($this->errors as $e) {
                $field = ucwords(str_replace($this->fieldCharsToRemove, chr(32), $e['field']));
                $param = $e['param'];
                if (array_key_exists($e['field'], self::$fields)) {
                    $field = self::$fields[$e['field']];
                }
                switch ($e['rule']) {
                    case 'mismatch':
                        $resp[] = "There is no validation rule for <span class=\"$field_class\">$field</span>";
                        break;
                    case 'validate_required':
                        $resp[] = "The <span class=\"$field_class\">$field</span> field is required";
                        break;
                    case 'validate_valid_email':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field is required to be a valid email address";
                        break;
                    case 'validate_max_len':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to be $param or shorter in length";
                        break;
                    case 'validate_min_len':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to be $param or longer in length";
                        break;
                    case 'validate_exact_len':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to be exactly $param characters in length";
                        break;
                    case 'validate_alpha':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field may only contain alpha characters(a-z)";
                        break;
                    case 'validate_alpha_numeric':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field may only contain alpha-numeric characters";
                        break;
                    case 'validate_alpha_dash':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field may only contain alpha characters &amp; dashes";
                        break;
                    case 'validate_numeric':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field may only contain numeric characters";
                        break;
                    case 'validate_integer':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field may only contain a numeric value";
                        break;
                    case 'validate_boolean':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field may only contain a true or false value";
                        break;
                    case 'validate_float':
                        $resp[] = "The <span class=\"$field_class\">$field</span> field may only contain a float value";
                        break;
                    case 'validate_valid_url':
                        $resp[] = "The <span class=\"$field_class\">$field</span> field is required to be a valid URL";
                        break;
                    case 'validate_url_exists':
                        $resp[] = "The <span class=\"$field_class\">$field</span> URL does not exist";
                        break;
                    case 'validate_valid_ip':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to contain a valid IP address";
                        break;
                    case 'validate_valid_cc':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to contain a valid credit card number";
                        break;
                    case 'validate_valid_name':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to contain a valid human name";
                        break;
                    case 'validate_contains':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to contain one of these values: "
                            . implode(', ', $param);
                        break;
                    case 'validate_contains_list':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to contain a value from its drop down list";
                        break;
                    case 'validate_doesnt_contain_list':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field contains a value that is not accepted";
                        break;
                    case 'validate_street_address':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to be a valid street address";
                        break;
                    case 'validate_date':
                        $resp[] = "The <span class=\"$field_class\">$field</span> field needs to be a valid date";
                        break;
                    case 'validate_min_numeric':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to be a numeric value, equal to, or higher than $param";
                        break;
                    case 'validate_max_numeric':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to be a numeric value, equal to, or lower than $param";
                        break;
                    case 'validate_starts':
                        $resp[] = "The <span class=\"$field_class\">$field</span> field needs to start with $param";
                        break;
                    case 'validate_extension':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field can have the following extensions $param";
                        break;
                    case 'validate_required_file':
                        $resp[] = "The <span class=\"$field_class\">$field</span> field is required";
                        break;
                    case 'validate_equalsfield':
                        $resp[] = "The <span class=\"$field_class\">$field</span> field does not equal $param field";
                        break;
                    case 'validate_min_age':
                        $resp[]
                            = "The <span class=\"$field_class\">$field</span> field needs to have an age greater than or equal to $param";
                        break;
                    default:
                        $resp[] = "The <span class=\"$field_class\">$field</span> field is invalid";
                }
            }
            if ( ! $convert_to_string) {
                return $resp;
            } else {
                $buffer = '';
                foreach ((array)$resp as $s) {
                    $buffer .= "<span class=\"$error_class\">$s</span>";
                }

                return $buffer;
            }
        }

        /**
         * @param array $data
         * @param array $filters
         *
         * @return array
         */
        public static function filter_input(array $data, array $filters)
        {
            $gump = self::get_instance();

            return $gump->filter($data, $filters);
        }

        /**
         * @return array|null|string
         */
        public function __toString()
        {
            return $this->get_readable_errors(true);
        }

        /**
         * @param array $data
         *
         * @return array
         */
        public static function xss_clean(array $data)
        {
            foreach ($data as $k => $v) {
                $data[$k] = filter_var($v, FILTER_SANITIZE_STRING);
            }

            return $data;
        }

        /**
         * @param $rule
         * @param $callback
         *
         * @return bool
         * @throws \Exception
         */
        public static function add_validator($rule, $callback)
        {
            $method = 'validate_' . $rule;
            if (method_exists(__CLASS__, $method) || isset(self::$validation_methods[$rule])) {
                throw new Exception("Validator rule '$rule' already exists.");
            }
            self::$validation_methods[$rule] = $callback;

            return true;
        }

        /**
         * @param $rule
         * @param $callback
         *
         * @return bool
         * @throws \Exception
         */
        public static function add_filter($rule, $callback)
        {
            $method = 'filter_' . $rule;
            if (method_exists(__CLASS__, $method) || isset(self::$filter_methods[$rule])) {
                throw new Exception("Filter rule '$rule' already exists.");
            }
            self::$filter_methods[$rule] = $callback;

            return true;
        }

        /**
         * @param       $key
         * @param array $array
         * @param null  $default
         *
         * @return mixed|null
         */
        public static function field($key, array $array, $default = null)
        {
            if ( ! is_array($array)) {
                return null;
            }
            if (isset($array[$key])) {
                return $array[$key];
            } else {
                return $default;
            }
        }

        /**
         * @param array $input
         * @param array $fields
         * @param bool  $utf8_encode
         *
         * @return array
         */
        public function sanitize(array $input, array $fields = [], $utf8_encode = true)
        {
            $magic_quotes = (bool)get_magic_quotes_gpc();
            if (empty($fields)) {
                $fields = array_keys($input);
            }
            $return = [];
            foreach ($fields as $field) {
                if ( ! isset($input[$field])) {
                    continue;
                } else {
                    $value = $input[$field];
                    if (is_array($value)) {
                        $value = $this->sanitize($value);
                    }
                    if (is_string($value)) {
                        if ($magic_quotes === true) {
                            $value = stripslashes($value);
                        }
                        if (strpos($value, "\r") !== false) {
                            $value = trim($value);
                        }
                        if (function_exists('iconv') && function_exists('mb_detect_encoding') && $utf8_encode) {
                            $current_encoding = mb_detect_encoding($value);
                            if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                                $value = iconv($current_encoding, 'UTF-8', $value);
                            }
                        }
                        $value = filter_var($value, FILTER_SANITIZE_STRING);
                    }
                    $return[$field] = $value;
                }
            }

            return $return;
        }

        /**
         * @return array
         */
        public function errors()
        {
            return $this->errors;
        }

        /**
         * @param array $input
         * @param       $rules
         * @param       $field
         *
         * @return bool
         */
        protected function shouldRunValidation(array $input, $rules, $field)
        {
            return in_array('required', $rules) || (isset($input[$field]) && trim($input[$field]) != '');
        }

        /**
         * @param null $convert_to_string
         *
         * @return array|null
         */
        public function get_errors_array($convert_to_string = null)
        {
            if (empty($this->errors)) {
                return $convert_to_string ? null : [];
            }
            $resp = [];
            foreach ($this->errors as $e) {
                $field = ucwords(str_replace(['_', '-'], chr(32), $e['field']));
                $param = $e['param'];
                if (array_key_exists($e['field'], self::$fields)) {
                    $field = self::$fields[$e['field']];
                }
                switch ($e['rule']) {
                    case 'mismatch':
                        $resp[$field] = "There is no validation rule for $field";
                        break;
                    case 'validate_required':
                        $resp[$field] = "The $field field is required";
                        break;
                    case 'validate_valid_email':
                        $resp[$field] = "The $field field is required to be a valid email address";
                        break;
                    case 'validate_max_len':
                        $resp[$field] = "The $field field needs to be $param or shorter in length";
                        break;
                    case 'validate_min_len':
                        $resp[$field] = "The $field field needs to be $param or longer in length";
                        break;
                    case 'validate_exact_len':
                        $resp[$field] = "The $field field needs to be exactly $param characters in length";
                        break;
                    case 'validate_alpha':
                        $resp[$field] = "The $field field may only contain alpha characters(a-z)";
                        break;
                    case 'validate_alpha_numeric':
                        $resp[$field] = "The $field field may only contain alpha-numeric characters";
                        break;
                    case 'validate_alpha_dash':
                        $resp[$field] = "The $field field may only contain alpha characters &amp; dashes";
                        break;
                    case 'validate_numeric':
                        $resp[$field] = "The $field field may only contain numeric characters";
                        break;
                    case 'validate_integer':
                        $resp[$field] = "The $field field may only contain a numeric value";
                        break;
                    case 'validate_boolean':
                        $resp[$field] = "The $field field may only contain a true or false value";
                        break;
                    case 'validate_float':
                        $resp[$field] = "The $field field may only contain a float value";
                        break;
                    case 'validate_valid_url':
                        $resp[$field] = "The $field field is required to be a valid URL";
                        break;
                    case 'validate_url_exists':
                        $resp[$field] = "The $field URL does not exist";
                        break;
                    case 'validate_valid_ip':
                        $resp[$field] = "The $field field needs to contain a valid IP address";
                        break;
                    case 'validate_valid_cc':
                        $resp[$field] = "The $field field needs to contain a valid credit card number";
                        break;
                    case 'validate_valid_name':
                        $resp[$field] = "The $field field needs to contain a valid human name";
                        break;
                    case 'validate_contains':
                        $resp[$field] = "The $field field needs to contain one of these values: " . implode(
                                ', ', $param
                            );
                        break;
                    case 'validate_contains_list':
                        $resp[$field] = "The $field field needs to contain a value from its drop down list";
                        break;
                    case 'validate_doesnt_contain_list':
                        $resp[$field] = "The $field field contains a value that is not accepted";
                        break;
                    case 'validate_street_address':
                        $resp[$field] = "The $field field needs to be a valid street address";
                        break;
                    case 'validate_date':
                        $resp[$field] = "The $field field needs to be a valid date";
                        break;
                    case 'validate_min_numeric':
                        $resp[$field] = "The $field field needs to be a numeric value, equal to, or higher than $param";
                        break;
                    case 'validate_max_numeric':
                        $resp[$field] = "The $field field needs to be a numeric value, equal to, or lower than $param";
                        break;
                    case 'validate_min_age':
                        $resp[$field] = "The $field field needs to have an age greater than or equal to $param";
                        break;
                    default:
                        $resp[$field] = "The $field field is invalid";
                }
            }

            return $resp;
        }

        /**
         * @param $value
         *
         * @return string
         */
        protected function filter_noise_words($value)
        {
            $value = preg_replace('/\s\s+/u', chr(32), $value);
            $value = " $value ";
            $words = explode(',', self::$en_noise_words);
            foreach ($words as $word) {
                $word = trim($word);
                $word = " $word ";
                if (stripos($value, $word) !== false) {
                    $value = str_ireplace($word, chr(32), $value);
                }
            }

            return trim($value);
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_rmpunctuation($value)
        {
            return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $value);
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_sanitize_string($value)
        {
            return filter_var($value, FILTER_SANITIZE_STRING);
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_urlencode($value)
        {
            return filter_var($value, FILTER_SANITIZE_ENCODED);
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_htmlencode($value)
        {
            return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_sanitize_email($value)
        {
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_sanitize_numbers($value)
        {
            return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_sanitize_floats($value)
        {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        /**
         * @param $value
         *
         * @return string
         */
        protected function filter_basic_tags($value)
        {
            return strip_tags($value, self::$basic_tags);
        }

        /**
         * @param $value
         *
         * @return int
         */
        protected function filter_whole_number($value)
        {
            return (int)$value;
        }

        /**
         * @param $value
         *
         * @return mixed
         */
        protected function filter_ms_word_characters($value)
        {
            $word_open_double  = '“';
            $word_close_double = '”';
            $web_safe_double   = '"';
            $value = str_replace([$word_open_double, $word_close_double], $web_safe_double, $value);
            $word_open_single  = '‘';
            $word_close_single = '’';
            $web_safe_single   = "'";
            $value = str_replace([$word_open_single, $word_close_single], $web_safe_single, $value);
            $word_em     = '–';
            $web_safe_em = '-';
            $value = str_replace($word_em, $web_safe_em, $value);
            $word_ellipsis = '…';
            $web_safe_em   = '...';
            $value = str_replace($word_ellipsis, $web_safe_em, $value);

            return $value;
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_contains($field, $input, $param = null)
        {
            if ( ! isset($input[$field])) {
                return;
            }
            $param = trim(strtolower($param));
            $value = trim(strtolower($input[$field]));
            if (preg_match_all('#\'(.+?)\'#', $param, $matches, PREG_PATTERN_ORDER)) {
                $param = $matches[1];
            } else {
                $param = explode(chr(32), $param);
            }
            if (in_array($value, $param)) {
                return;
            }

            return [
                'field' => $field,
                'value' => $value,
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_contains_list($field, $input, $param = null)
        {
            $param = trim(strtolower($param));
            $value = trim(strtolower($input[$field]));
            $param = explode(';', $param);
            if (in_array($value, $param)) {
                return;
            } else {
                return [
                    'field' => $field,
                    'value' => $value,
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_doesnt_contain_list($field, $input, $param = null)
        {
            $param = trim(strtolower($param));
            $value = trim(strtolower($input[$field]));
            $param = explode(';', $param);
            if ( ! in_array($value, $param)) {
                return;
            } else {
                return [
                    'field' => $field,
                    'value' => $value,
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_required($field, $input, $param = null)
        {
            if (isset($input[$field])
                && ($input[$field] === false || $input[$field] === 0 || $input[$field] === 0.0
                    || $input[$field] === '0'
                    || ! empty($input[$field]))
            ) {
                return;
            }

            return [
                'field' => $field,
                'value' => null,
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_email($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_max_len($field, $input, $param = null)
        {
            if ( ! isset($input[$field])) {
                return;
            }
            if (function_exists('mb_strlen')) {
                if (mb_strlen($input[$field]) <= (int)$param) {
                    return;
                }
            } else {
                if (strlen($input[$field]) <= (int)$param) {
                    return;
                }
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_min_len($field, $input, $param = null)
        {
            if ( ! isset($input[$field])) {
                return;
            }
            if (function_exists('mb_strlen')) {
                if (mb_strlen($input[$field]) >= (int)$param) {
                    return;
                }
            } else {
                if (strlen($input[$field]) >= (int)$param) {
                    return;
                }
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_exact_len($field, $input, $param = null)
        {
            if ( ! isset($input[$field])) {
                return;
            }
            if (function_exists('mb_strlen')) {
                if (mb_strlen($input[$field]) == (int)$param) {
                    return;
                }
            } else {
                if (strlen($input[$field]) == (int)$param) {
                    return;
                }
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_alpha($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! preg_match('/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $input[$field])
                !== false
            ) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_alpha_numeric($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $input[$field])
                !== false
            ) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_alpha_dash($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i', $input[$field])
                !== false
            ) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_alpha_space($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! preg_match("/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s])+$/i", $input[$field])
                !== false
            ) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_numeric($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! is_numeric($input[$field])) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_integer($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if (filter_var($input[$field], FILTER_VALIDATE_INT) === false) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_boolean($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field]) && $input[$field] !== 0) {
                return;
            }
            if ($input[$field] === true || $input[$field] === false) {
                return;
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_float($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if (filter_var($input[$field], FILTER_VALIDATE_FLOAT) === false) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_url($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! filter_var($input[$field], FILTER_VALIDATE_URL)) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_url_exists($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            $url = parse_url(strtolower($input[$field]));
            if (isset($url['host'])) {
                $url = $url['host'];
            }
            if (function_exists('checkdnsrr')) {
                if (checkdnsrr($url) === false) {
                    return [
                        'field' => $field,
                        'value' => $input[$field],
                        'rule'  => __FUNCTION__,
                        'param' => $param,
                    ];
                }
            } else {
                if (gethostbyname($url) == $url) {
                    return [
                        'field' => $field,
                        'value' => $input[$field],
                        'rule'  => __FUNCTION__,
                        'param' => $param,
                    ];
                }
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_ip($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! filter_var($input[$field], FILTER_VALIDATE_IP) !== false) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_ipv4($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! filter_var($input[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_ipv6($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! filter_var($input[$field], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_cc($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            $number = preg_replace('/\D/', '', $input[$field]);
            if (function_exists('mb_strlen')) {
                $number_length = mb_strlen($number);
            } else {
                $number_length = strlen($number);
            }
            $parity = $number_length % 2;
            $total = 0;
            for ($i = 0; $i < $number_length; ++$i) {
                $digit = $number[$i];
                if ($i % 2 == $parity) {
                    $digit *= 2;
                    if ($digit > 9) {
                        $digit -= 9;
                    }
                }
                $total += $digit;
            }
            if ($total % 10 == 0) {
                return;
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_name($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ '-])+$/i", $input[$field])
                !== false
            ) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_street_address($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            $hasLetter = preg_match('/[a-zA-Z]/', $input[$field]);
            $hasDigit  = preg_match('/\d/', $input[$field]);
            $hasSpace  = preg_match('/\s/', $input[$field]);
            $passes = $hasLetter && $hasDigit && $hasSpace;
            if ( ! $passes) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_iban($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            static $character = [
                'A' => 10,
                'C' => 12,
                'D' => 13,
                'E' => 14,
                'F' => 15,
                'G' => 16,
                'H' => 17,
                'I' => 18,
                'J' => 19,
                'K' => 20,
                'L' => 21,
                'M' => 22,
                'N' => 23,
                'O' => 24,
                'P' => 25,
                'Q' => 26,
                'R' => 27,
                'S' => 28,
                'T' => 29,
                'U' => 30,
                'V' => 31,
                'W' => 32,
                'X' => 33,
                'Y' => 34,
                'Z' => 35,
                'B' => 11,
            ];
            if ( ! preg_match("/\A[A-Z]{2}\d{2} ?[A-Z\d]{4}( ?\d{4}){1,} ?\d{1,4}\z/", $input[$field])) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
            $iban = str_replace(' ', '', $input[$field]);
            $iban = substr($iban, 4) . substr($iban, 0, 4);
            $iban = strtr($iban, $character);
            if (bcmod($iban, 97) != 1) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_date($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            $cdate1 = date('Y-m-d', strtotime($input[$field]));
            $cdate2 = date('Y-m-d H:i:s', strtotime($input[$field]));
            if ($cdate1 != $input[$field] && $cdate2 != $input[$field]) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_min_age($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            $cdate1 = new \DateTime(date('Y-m-d', strtotime($input[$field])));
            $today  = new \DateTime(date('d-m-Y'));
            $interval = $cdate1->diff($today);
            $age      = $interval->y;
            if ($age <= $param) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_max_numeric($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if (is_numeric($input[$field]) && is_numeric($param) && ($input[$field] <= $param)) {
                return;
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_min_numeric($field, $input, $param = null)
        {
            if ( ! isset($input[$field])) {
                return;
            }
            if (is_numeric($input[$field]) && is_numeric($param) && ($input[$field] >= $param)) {
                return;
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_starts($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if (strpos($input[$field], $param) !== 0) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_required_file($field, $input, $param = null)
        {
            if ($input[$field]['error'] !== 4) {
                return;
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_extension($field, $input, $param = null)
        {
            if ($input[$field]['error'] !== 4) {
                $param              = trim(strtolower($param));
                $allowed_extensions = explode(';', $param);
                $path_info = pathinfo($input[$field]['name']);
                $extension = $path_info['extension'];
                if (in_array($extension, $allowed_extensions)) {
                    return;
                }

                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_equalsfield($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ($input[$field] == $input[$param]) {
                return;
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_guidv4($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if (preg_match("/\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/", $input[$field])) {
                return;
            }

            return [
                'field' => $field,
                'value' => $input[$field],
                'rule'  => __FUNCTION__,
                'param' => $param,
            ];
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_phone_number($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            $regex = '/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i';
            if ( ! preg_match($regex, $input[$field])) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_regex($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            $regex = $param;
            if ( ! preg_match($regex, $input[$field])) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }

        /**
         * @param      $field
         * @param      $input
         * @param null $param
         *
         * @return array|void
         */
        protected function validate_valid_json_string($field, $input, $param = null)
        {
            if ( ! isset($input[$field]) || empty($input[$field])) {
                return;
            }
            if ( ! is_string($input[$field]) || ! is_object(json_decode($input[$field]))) {
                return [
                    'field' => $field,
                    'value' => $input[$field],
                    'rule'  => __FUNCTION__,
                    'param' => $param,
                ];
            }
        }
    }
