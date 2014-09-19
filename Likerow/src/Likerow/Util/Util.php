<?php

namespace Likerow\Util;

class Util {

    public static $roundList = array('JPY', 'XPF', 'IDR', 'KRW', 'BYR', 'VND');
    public static $decimalTree = array('BHD', 'JOD', 'KWD', 'OMR');

    public static function callServices($uri, $params, $method = 'POST', $responseAll = false, $usuario = null, $password = null) {
        $response = array('error' => 1, 'message' => 'Por procesar');

        try {
            $clientConfig = array(
                'adapter' => 'Zend\Http\Client\Adapter\Curl',
                'curloptions' => array(
                    CURLOPT_FOLLOWLOCATION => TRUE,
                    CURLOPT_SSL_VERIFYPEER => FALSE
                ),
            );

            $client = new \Zend\Http\Client($uri, $clientConfig);
            if (!empty($usuario)) {
                $client->setAuth($usuario, $password);
            }
            $client->setMethod($method);
            $client->setParameterPost($params);
            $responseHttp = $client->send();
            //var_dump($uri, $responseHttp->getBody()); exit;
            if ($responseHttp->isSuccess()) {
                $data = json_decode($responseHttp->getBody(), true);
                if ($responseAll) {
                    return $data;
                }
                if ($data['status'] > 0) {
                    $response['data'] = $data;
                } else {
                    $response['status'] = -1;
                    $response['data'] = $data;
                }
            } else {
                $response["error"] = 10;
                throw new \Exception($responseHttp->getStatusCode() . '=> ' . $responseHttp->getReasonPhrase());
            }
        } catch (\Exception $e) {
            $response["status"] = 1;
            $response['message'] = $e->getMessage() . $e->getTraceAsString();
            /* $modelMail = $this->_service->get('Mail');
              ob_start();
              var_dump($urlRest, $e->getMessage());
              var_dump($e->getTraceAsString());
              $result = ob_get_clean();
              //$modelMail->notificarError('Sincroniozacion payadmin', $result); */
        }
    }

    /**
     * 
     * @param type $amount
     * @param type $currency
     * @param type $adyen
     * @return type
     */
    public static function formatValueAdyen($amount, $currency, $adyen = false, $currencies = null) {        
        if (isset($currencies[$currency])) {
            $amount = number_format($amount, $currencies[$currency], '.', '');
            $amount = $amount * (int) str_pad(1, $currencies[$currency] + 1, 0, STR_PAD_RIGHT);            
        } else {
            $amount = number_format($amount, '2', '.', '');
            $amount = $amount * 100;
        }
        return $amount;
    }

    /**
     * 
     * @param type $amount
     * @param type $currency
     * @param type $adyen
     * @return type
     */
    public static function formatValue($amount, $currency, $adyen = false) {
        if ($currency == 'BTC') {
            return $amount;
        }
        if (!in_array($currency, self::$decimalTree)) {
            $amount = number_format($amount, '2', '.', '');
        }

        if (in_array($currency, self::$roundList)) {
            return ceil($amount);
        } elseif ($adyen) {
            if (in_array($currency, self::$decimalTree)) {
                $amount = number_format($amount, '3', '.', '');
                return $amount * 1000;
            }
            return $amount * 100;
        }
        return $amount;
    }

    static function removerJavascript($html) {
        $javascript = '/<script[^>]*?>.*?<\/script>/si';
        $html1 = preg_replace($javascript, "", $html);
        $javascript2 = '/<script[^>]*?javascript{1}[^>]*?>.*?<\/script>/si';
        $html2 = preg_replace($javascript2, "", $html1);
        return $html2;
    }

    static function makeBongoTrackingBP($partnerID, $orderNumber) {
        $n = md5($partnerID . $orderNumber . "HFRRNSSDFS" . microtime() * 3245634567);
        $st = '';
        for ($x = 0; $x < 32; $x = $x + 2) {
            $q = ord($n[$x]) + ord($n[$x + 1]) - 2 * ord('0');
            if ($x % 3) {
                $s = ($q % 10);
            } else {
                $s = ($q % 36);
            }
            if ($s < 10) {
                $st.=chr($s + ord('0'));
            } else {
                $st.=chr($s + ord('A') - 10);
            }
        }
        return "S" . $partnerID . "-" . $st;
    }

    /**
     * 
     * @param type $data
     * @param type $value
     * @param type $tag
     * @param type $selectedValue
     * @param type $whereSelect
     * @return string
     */
    static function comboOption($data, $value, $tag, $selectedValue, $whereSelect) {
        $strCbn = '';
        if (!empty($data)) {
            foreach ($data as $valCbn) {
                $strSelected = '';
                if (!empty($selectedValue)) {
                    $where = $valCbn[$value];
                    if (!empty($whereSelect)) {
                        $where = $valCbn[$whereSelect];
                    }
                    if ($where == $selectedValue) {
                        $strSelected = ' selected="selected" ';
                    }
                }
                $strCbn .= '<option value="' . $valCbn[$value] . '" ' . $strSelected . '>' . $valCbn[$tag] . '</option>';
            }
        }
        return $strCbn;
    }

    static function cache($key, $status = true) {
        
    }

    /**
     * Realiza multiinsera una tabla
     * @param type $adapter
     * @param type $table
     * @param array $data
     */
    static function multiInsert($adapter, $table, array $data) {
        if (count($data)) {
            $columns = (array) current($data);
            $columns = array_keys($columns);
            $columnsCount = count($columns);
            $platform = $adapter->platform;

            $columns = "(" . implode(',', $columns) . ")";

            $placeholder = array_fill(0, $columnsCount, '?');
            $placeholder = "(" . implode(',', $placeholder) . ")";
            $placeholder = implode(',', array_fill(0, count($data), $placeholder));

            $values = array();
            foreach ($data as $row) {
                foreach ($row as $key => $value) {
                    $values[] = $value;
                }
            }


            $table = $adapter->platform->quoteIdentifier($table);
            $q = "INSERT INTO $table $columns VALUES $placeholder";
            $adapter->query($q)->execute($values);
        }
    }

    /**
     * Valida si la peticion es por ajax.    
     * @return boolean
     */
    static function isRequestAjax() {
        $responce = false;
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            $responce = true;
        }
        return $responce;
    }

    /**
     * 
     * @param type $params
     * @return type
     */
    static function stripTags($params) {
        $filter = new \Zend\Filter\StripTags();
        if (!empty($params)) {
            foreach ($params as $indice => $value) {
                if (is_array($value)) {
                    foreach ($value as $indOne => $valueOne) {
                        if (is_array($valueOne)) {
                            foreach ($valueOne as $indTwo => $valueTwo) {
                                $params[$filter->filter($indice)][$filter->filter($indOne)][$filter->filter($indTwo)] = $filter->filter($valueTwo);
                            }
                        } else {
                            $params[$filter->filter($indice)][$filter->filter($indOne)] = $filter->filter($valueOne);
                        }
                    }
                } else {
                    $params[$filter->filter($indice)] = $filter->filter($value);
                }
            }
        }
        return $params;
    }

    static function mergeParams($paramsData) {
        $params = array();
        foreach ($paramsData as $valueOne) {
            if (!empty($valueOne)) {
                foreach ($valueOne as $indice => $value) {
                    $params[$indice] = $value;
                }
            }
        }
        return$params;
    }

    /**
     * 
     * @param type $string
     */
    static function escaper($string) {
        
    }

    /**
     * 
     * @param type $currAct
     * @param type $noCurr
     * @param type $numero
     * @param type $rate
     * @param type $coma
     * @return type
     */
    static function getValueFormat($currAct, $noCurr, $numero, $rate, $coma = '', $numberDecimal = 2) {
        if ($currAct == 'BTC') {
            return $numero * $rate;
        }
        if ($currAct == $noCurr) {
            //$numero = $numero * 100;
            return number_format($numero * $rate, $numberDecimal, '.', $coma);
        } else {
            return number_format($numero * $rate, $numberDecimal, '.', $coma);
        }
    }

    public static function numberFormatCheckout($currency, $number, $numberDecimal, $coma = '') {
        if ($currency == 'BTC') {
            return $number;
        }

        return number_format($number, $numberDecimal, '.', $coma);
    }

    public static function array_column($input = null, $columnKey = null, $indexKey = null) {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
            return null;
        }

        if (!is_int($params[1]) && !is_float($params[1]) && !is_string($params[1]) && $params[1] !== null && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2]) && !is_int($params[2]) && !is_float($params[2]) && !is_string($params[2]) && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {

            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }
        }

        return $resultArray;
    }

    /**
     * 
     * @return type
     */
    public static function getRealIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * 
     * @param type $config
     * @param type $prefix
     * @return type
     */
    public static function deletePrefixMemcache($config, $prefix = 'checkout_') {
        $keysFound = array();
        if (!empty($config['bongo_server']['cache']['adapter']['options']['servers'][0][0])) {
            $server = $config['bongo_server']['cache']['adapter']['options']['servers'][0][0];
            $memcache = new \Memcache();
            $memcache->connect($server, $port = 11211);
            $slabs = @$memcache->getextendedstats('slabs');
            foreach ($slabs as $serverSlabs) {
                foreach ($serverSlabs as $slabId => $slabMeta) {
                    try {
                        $cacheDump = @$memcache->getextendedstats('cachedump', (int) $slabId, 1000);
                    } catch (Exception $e) {
                        continue;
                    }

                    if (!is_array($cacheDump)) {
                        continue;
                    }

                    foreach ($cacheDump as $dump) {

                        if (!is_array($dump)) {
                            continue;
                        }

                        foreach ($dump as $key => $value) {
                            $position = (int) strpos($key, $prefix);
                            if ($position !== 0) {
                                $keysFound[] = $key;
                                $memcache->delete($key);
                            }
                        }
                    }
                }
            }
        }
        return $keysFound;
    }

}
