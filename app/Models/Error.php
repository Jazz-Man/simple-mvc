<?php
    namespace Models;

    /**
     * Class Error
     *
     * @package Core
     */
    class Error
    {
        public $errors = [];

        public $error_data = [];

        /**
         * Error constructor.
         *
         * @param string $code
         * @param string $message
         * @param string $data
         */
        public function __construct(string $code = '', string $message = '', string $data = '')
        {
            if (empty($code)) {
                return;
            }
            $this->errors[$code][] = $message;
            if ( ! empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        /**
         * @param string $code
         *
         * @return mixed|string
         */
        public function getErrorMessage($code = '')
        {
            if (empty($code)) {
                $code = $this->getErrorCode();
            }
            $messages = $this->getErrorMessages($code);
            if (empty($messages)) {
                return '';
            }

            return $messages[0];
        }

        /**
         * @return mixed|string
         */
        public function getErrorCode()
        {
            $codes = $this->getErrorCodes();
            if (empty($codes)) {
                return '';
            }

            return $codes[0];
        }

        /**
         * @return array
         */
        public function getErrorCodes()
        {
            if (empty($this->errors)) {
                return [];
            }

            return array_keys($this->errors);
        }

        /**
         * @param string $code
         *
         * @return array|mixed
         */
        public function getErrorMessages($code = '')
        {
            if (empty($code)) {
                $all_messages = [];
                foreach ($this->errors as $key => $messages) {
                    $all_messages = array_merge($all_messages, $messages);
                }

                return $all_messages;
            }
            if (isset($this->errors[$code])) {
                return $this->errors[$code];
            } else {
                return [];
            }
        }

        /**
         * @param string $code
         *
         * @return mixed
         */
        public function getErrorData($code = '')
        {
            if (empty($code)) {
                $code = $this->getErrorCode();
            }
            if (isset($this->error_data[$code])) {
                return $this->error_data[$code];
            }
        }

        /**
         * @param        $code
         * @param        $message
         * @param string $data
         */
        public function add($code, $message, $data = '')
        {
            $this->errors[$code][] = $message;
            if ( ! empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        /**
         * @param        $data
         * @param string $code
         */
        public function add_data($data, $code = '')
        {
            if (empty($code)) {
                $code = $this->getErrorCode();
            }
            $this->error_data[$code] = $data;
        }

        /**
         * @param $code
         */
        public function remove($code)
        {
            unset($this->errors[$code], $this->error_data[$code]);
        }

        public static function isErrors($error)
        {
            return ($error instanceof Error);
        }
    }
