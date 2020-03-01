<?php
namespace detailedDesign;
/**
 * Class DatabaseParser 数据库字段解析
 * @package detailedDesign
 * @author shixinke
 */
class DatabaseParser {
    private $db;
    private $database;

    public function __construct($host, $port, $database, $user, $password, $charset) {
        $dsn = 'mysql:dbname='.$database.';host='.$host.';port='.$port.';charset='.$charset;
        $this->database = $database;
        $this->db = new \PDO($dsn, $user, $password);
    }

    public function getDesc() {
        $result = $this->db->query("select TABLE_NAME,TABLE_COMMENT from INFORMATION_SCHEMA.Tables where table_schema = '".$this->database."'");
        $dataList = $result->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
        $tables = [];
        $map = [];
        if (is_array($dataList)) {
            foreach ($dataList as $key=>$row) {
                $tables[$key] = $row[0];
            }
        }
        foreach($tables as $table => $comment) {
            $tableData = [];
            $tableData['comment'] = $comment;
            $desc = $this->db->query("show full columns from `".$table."`");
            if ($desc) {
                $columns = $desc->fetchAll(\PDO::FETCH_ASSOC);
                $tableData['columns'] = $columns;
            }

            $ddl = $this->db->query("show create table `".$table."`");
            $ddlData = $ddl->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
            $tableData['ddl'] = $ddlData[$table][0];

            $map[$table] = $tableData;
        }
        return $map;
    }
}