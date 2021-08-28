<?php

class DBManager
{

    private $connection;
    private $config;
    private $pdo;

    public function __construct()
    {

        $this->config = [
            'host' => $GLOBALS["config"]["db_host"],
            'db_name' => $GLOBALS["config"]["db_name"],
            'username' => $GLOBALS["config"]["db_username"],
            'password' => $GLOBALS["config"]["db_password"]
        ];
        $this->connectPDO();
    }


    public function connectPDO()
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($GLOBALS['config']['db_pdo'], $this->config['username'], $this->config['password']);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }

    }

    public function exec($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function query($sql, $params = [])
    {

        $stmt = $this->exec($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert($sql, $params = [])
    {
        $stmt = $this->exec($sql, $params);
        return $this->pdo->lastInsertId();
    }

}

class DBCON
{

    private static $throwExceptions = false;
    private static $logExceptions = true;

    public function __construct($config)
    {

        $this->config = $config;
    }

    public function connect()
    {
        if (!$this->connection || is_resource($this->connection)) {

            $this->connection = mysqli_connect($this->config['host'] . ':' . $this->config['port'], $this->config['username'], $this->config['password']);

            if (!$this->connection) {
                throw new Exception('Could not connect to the database ( ' . $this->config['db_name'] . ')');
            }

            if (!mysqli_select_db($this->connection, $this->config['db_name'])) {
                throw new Exception('Could not select database ' . $this->config['db_name']);
            }

            if (isset($this->config['encoding'])) {
                mysqli_query($this->connection, 'SET NAMES ' . $this->config['encoding']);
            }
        }
    }

    public static function getInstance($dbHost = null, $dbName = null, $dbUsername = null, $dbPassword = null)
    {
        static $instance = null;
        if ($instance === null) {
            try {
                $instance = new DBCON($dbHost, $dbName, $dbUsername, $dbPassword);
            } catch (Exception $ex) {
                try {
                    //retry to connect
                    $instance = new DBCON($dbHost, $dbName, $dbUsername, $dbPassword);
                } catch (Exception $ex2) {
                    if (self::$logExceptions === true) {
                        AMSLogger::log('Exception: ' . $ex->getMessage() . PHP_EOL .
                                'File: ' . $ex->getFile() . PHP_EOL .
                                'Line: ' . $ex->getLine() . PHP_EOL .
                                'Trace: ' . $ex->getTraceAsString(), AMSLogger::ERROR);
                    }
                    if (self::$throwExceptions === true) {
                        throw $ex2;
                    } else {
                        return false;
                    }
                }
            }
        }

        return $instance;
    }

    private function isAssoc(array $arr)
    {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function isValidStringType($stringTypeArgument)
    {
        return (!is_null($stringTypeArgument) && (!is_array($stringTypeArgument) && ((!is_object($stringTypeArgument) && settype($stringTypeArgument, 'string') !== false) || (is_object($stringTypeArgument) && method_exists($stringTypeArgument, '__toString')))));
    }

    private function error($message, $followedByExit = false)
    {
        if (self::$throwExceptions === true) {
            throw new Exception($message);
        } else {
            echo $message;
        }
        if ($followedByExit === TRUE) {
            exit;
        }
    }

    private function bindValues($statement, $parameters, $arguments, $target)
    {
        foreach ($parameters as $paramName => $paramAttributes) {
            $argumentToPass = isset($arguments[$paramName]) ? $arguments[$paramName] : NULL;

            $argumentTypeAsString = $paramAttributes["Type"];
            $argumentTypeLength = $paramAttributes["Length"];
            $argumentIsRequired = $paramAttributes["IsRequired"];
            $argumentParameterIndex = $paramAttributes["ParameterIndex"];
            if (!isset($argumentToPass) && $argumentIsRequired === TRUE) {
                $this->error("$target: Passed invalid or empty argument to Required parameter \"$paramName\"! Check your input data!");
            }

            $argumentType = PDO::PARAM_NULL;
            switch ($argumentTypeAsString) {
                case "bigint":
                case "int":
                case "smallint":
                    $argumentToPass = filter_var(($argumentToPass === "" ? NULL : $argumentToPass), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                    if (is_null($argumentToPass)) {
                        if ($argumentIsRequired) {
                            $this->error("$target: Invalid argument \"$paramName\" supplied! Int type expected!");
                        }
                        break;
                    }

                    $argumentType = PDO::PARAM_INT;
                    break;
                case "char":
                case "nchar":
                case "varchar":
                case "nvarchar":
                case "text":
                    $isValid = $this->isValidStringType($argumentToPass);
                    if (!$isValid) {
                        $argumentToPass = NULL;
                        break;
                    }
                    $argumentToPass = (string) $argumentToPass;
                    if (isset($argumentTypeLength) && strtolower($argumentTypeLength) !== 'max') {
                        if (strlen($argumentToPass) > $argumentTypeLength) {
                            $this->error("$target: Passed n/varchar argument \"$paramName\" exceedes the maximum allowed length!");
                        }
                    }

                    $arguments[$paramName] = $argumentToPass;

                    $argumentType = PDO::PARAM_STR;
                    break;
                case "bit":
                    $argumentToPass = is_null($argumentToPass) ? null : filter_var($argumentToPass, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if (is_null($argumentToPass)) {
                        break;
                    }

                    $argumentType = PDO::PARAM_BOOL;
                    break;
                case "datetime":
                case "date":
                    if (empty($argumentToPass)) {
                        $argumentToPass = NULL;
                        break;
                    }

                    $argumentType = PDO::PARAM_STR;
                    break;
                case "xml":
                    if (empty($argumentToPass)) {
                        $argumentToPass = NULL;
                        break;
                    }

                    $argumentType = PDO::PARAM_STR;
                    break;
                case "float":
                    $argumentToPass = filter_var(($argumentToPass === "" ? NULL : $argumentToPass), FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                    if (is_null($argumentToPass)) {
                        if ($argumentIsRequired) {
                            $this->error("$target: Invalid argument \"$paramName\" supplied! Float type expected!");
                        }
                        break;
                    }

                    $argumentType = PDO::PARAM_STR;
                    break;
                case "decimal":
                    if (is_null($argumentToPass)) {
                        $argumentToPass = NULL;
                        break;
                    }

                    $argumentType = PDO::PARAM_STR;
                    break;
                default :
                    $this->error("$target: Invalid data type supplied for parameter \"$paramName\"!");
            }

            $statement->bindValue($argumentParameterIndex, $argumentToPass, $argumentType);
        }

        return $statement;
    }

    private function validateTarget($target)
    {
        if (!is_string($target)) {
            $this->error("$target: Invalid argument type supplied! Endpoint should be string!", false);
            return false;
        }

        $firstSlashPosition = stripos($target, '/');
        if ($firstSlashPosition === FALSE) {
            $this->error("$target:  Invalid endpoint string was supplied!");
            return false;
        }

        $lastSlashPosition = strrpos($target, '/');
        if ($firstSlashPosition !== $lastSlashPosition) {    // there is more than one slash in the string, It is not allowed cuz it will break the split
            $this->error("$target: Invalid endpoint string was supplied!");
            return false;
        }
        return true;
    }

    private function getParemeters($actionToCall, $target)
    {
        $procedureParams = $actionToCall->children();
        if (empty($procedureParams)) {
            $this->error("$target: Procedure does not accept any parameters. Are you sure, you are doing everything ok?");
        }

        $parameters = array();
        foreach ($procedureParams as $param) {
            $parameter = $this->getParameter($param, $target);
            $paramName = $parameter["ParameterName"];
            $parameters[$paramName] = $parameter;
        }

        return $parameters;
    }

    private function buildQuery($procedureName, &$procedureParams, $arguments)
    {
        $sql = 'EXEC ' . $procedureName;
        $selectOutputParamsSql = '; SELECT';
        $declareOutputParamsSql = '';
        $outputParamsIndex = 1;
        $paramsIndex = 1;
        foreach ($procedureParams as $index => $procedureParam) {
            $paramName = $procedureParam["ParameterName"];
            if ($procedureParam["Out"]) {
                $declareOutputParamsSql .= "DECLARE @$paramName " . $procedureParam["Type"] .
                        (($procedureParam["Type"] == "nvarchar" || $procedureParam["Type"] == "nchar" || $procedureParam["Type"] == "varchar" || $procedureParam["Type"] == "char") ? "(" . (empty($procedureParam["Length"]) ? "MAX" : $procedureParam["Length"]) . ")" : "") .
                        " = " .
                        (($procedureParam["Type"] == "xml" && !empty($arguments[$paramName])) || (($procedureParam["Type"] == "nvarchar" || $procedureParam["Type"] == "nchar") && isset($arguments[$paramName]) && $this->isValidStringType($arguments[$paramName])) ? "N" : "") . "?; ";

                $sql .= " @$paramName = @$paramName OUTPUT,";
                $selectOutputParamsSql .= " @$paramName as [$paramName],";
                $procedureParams[$index]["ParameterIndex"] = $outputParamsIndex++;

                foreach ($procedureParams as $k => $v) {
                    if ($k == $index) {
                        break;
                    }
                    if (!$v["Out"]) {
                        $procedureParams[$k]["ParameterIndex"] ++;
                    }
                }
            } else {
                if ($this->getHostOS() == "WIN") {
                    $sql .= " @$paramName = ?,";
                } else {
                    $sql .= " @$paramName = " . (($procedureParam["Type"] == "xml" && !empty($arguments[$paramName])) || (($procedureParam["Type"] == "nvarchar" || $procedureParam["Type"] == "nchar") && isset($arguments[$paramName]) && $this->isValidStringType($arguments[$paramName])) ? "N" : "") . "?,";
                }
                $procedureParams[$index]["ParameterIndex"] = $paramsIndex;
            }
            $paramsIndex++;
        }

        $sql = $declareOutputParamsSql . rtrim($sql, ',');
        if ($selectOutputParamsSql != '; SELECT') {
            $sql .= rtrim($selectOutputParamsSql, ',');
        }
        return $sql;
    }

    private function getParameter($parameterTag, $target)
    {
        $paramAttributes = $parameterTag->attributes();
        $paramName = (string) $paramAttributes->{'name'};
        $paramIsRequired = !empty($paramAttributes->{'required'}) ? $paramAttributes->{'required'} : false;
        if ($paramIsRequired !== FALSE) {
            $paramIsRequired = strtolower($paramIsRequired);
            $paramIsRequired = $paramIsRequired === "true" ? true : false;
        }

        $isOutParamAsString = (string) $paramAttributes->{'outparam'};
        if (empty($isOutParamAsString)) {
            $isOutParam = FALSE; // by default is false
        } else {
            $isOutParam = strtolower($isOutParamAsString) === "true" ? TRUE : FALSE;
        }

        $paramType = !empty($paramAttributes->{'type'}) ? strtolower((string) $paramAttributes->{'type'}) : NULL;
        if (!isset($paramType)) {
            $this->error("$target: Param: " . $paramName . " has no type! Check your xml!");
        }

        $paramTypeLength = !empty($paramAttributes->{'length'}) ? (string) $paramAttributes->{'length'} : NULL;
        $parameter = array("ParameterName" => $paramName, "Type" => $paramType, "Length" => $paramTypeLength, "IsRequired" => $paramIsRequired, "Out" => $isOutParam);
        return $parameter;
    }

    private function tableParser($statement, $statementResult)
    {
        if (!$statementResult) {
            return false;
        }
        if ($this->getHostOS() == "WIN") {
            while ($statement->columnCount() === 0 && $statement->nextRowset()) {
                // Advance rowset until we get to a rowset with data
            }
        }
        $rows = $statement->fetchAll(PDO::FETCH_OBJ);
        $parsedData = new stdClass();
        $parsedData->RowCount = count($rows);
        $parsedData->Rows = $rows;
        return $parsedData;
    }

    private function insertParser($statement, $statementResult)
    {
        $parsedData = new stdClass();
        $out = null;

        if ($statementResult) {
            if ($this->getHostOS() == "WIN") {
                while ($statement->columnCount() === 0 && $statement->nextRowset()) {
                    // Advance rowset until we get to a rowset with data
                }
            } else {
                while ($statement->fetch());
                $statement->nextRowset();
            }


            $errors = $statement->errorInfo();
            if (!empty($errors[2])) {
                $this->error($errors[2]);
            }
            $statement->bindColumn(1, $out, PDO::PARAM_INT);
            $statement->fetch(PDO::FETCH_BOUND);
            if ($out <= 0) {
                $statementResult = false;
                $out = null;
            }
        }
        $parsedData->IsSuccessful = $statementResult;
        $parsedData->RecordId = $out;
        return $parsedData;
    }

    private function statusParser($statement, $statementResult)
    {
        $parsedData = new stdClass();
        $parsedData->IsSuccessful = $statementResult;
        return $parsedData;
    }

    // Probably won't be needed. We'll use json instead
    private function xmlParser($statement, $statementResult)
    {
        if (!$statementResult) {
            return false;
        }
        $xml = $this->getStringColumn($statement);
        $xml = preg_replace('/&(?!(quot|amp|apos|lt|gt);)/', '&amp;', $xml);
        $parsedData = simplexml_load_string($xml);
        $rootName = $parsedData->getName();
        $nodeText = trim($parsedData);
        if (strlen($nodeText) && !$parsedData->count()) {
            $newData = new stdClass();
            $newData->{$rootName} = $nodeText;
            return $newData;
        }

        $result = json_decode(str_replace("{}", "null", json_encode($parsedData))); //converts SimpleXMLElement to plain object and replaces empty Elements with null
        return $result;
    }

    private function jsonParser($statement, $statementResult)
    {
        if (!$statementResult) {
            return false;
        }
        $json = $this->getStringColumn($statement);

        return json_decode($json);
    }

    private function getStringColumn($statement)
    {
        $string = "";
        $chunk = null;
        $statement->bindColumn(1, $chunk, PDO::PARAM_STR);
        while ($statement->fetch(PDO::FETCH_BOUND)) {
            $string .= $chunk;
        }
        return $string;
    }

}
