<?php
namespace detailedDesign;
/**
 * Class DocumentCreator 详细设计文档创建助手
 * @package detailedDesign
 * @author shixinke
 */
class DocumentCreator {

    private $data = [];
    private $config = [];
    private $indexTables = [];
    private $dataMap = [];
    private $apiMap = [];
    private $classDiagramMap = [];
    private $processMap = [];

    public function __construct($data, $config)
    {
        $this->data = $data;
        $this->config = $config;
    }

    public function create() {
        foreach ($this->data as $table => $row) {
            $prefix = substr($table, 0, strpos($table, "_"));
            if (strlen($prefix) == 0) {
                $prefix = $table;
            }
            if (!isset($this->indexTables[$prefix])) {
                $this->indexTables[$prefix] = 0;
            }
            if (!isset($this->dataMap[$prefix])) {
                $this->dataMap[$prefix] = "";
            }
            if (!isset($this->apiMap[$prefix])) {
                $this->apiMap[$prefix] = "";
            }
            if (!isset($this->classDiagramMap[$prefix])) {
                $this->classDiagramMap[$prefix] = "";
            }
            if (!isset($this->processMap[$prefix])) {
                $this->processMap[$prefix] = "";
            }
            $this->createOne($table, $prefix, $row);
        }
        foreach ($this->dataMap as $prefix => $dataContent) {
            $content = "## 1.数据架构\n\n";
            $content .= "### 1.1 数据库ER模型\n\n";
            $content .= "### 1.2 数据库逻辑模型\n\n";
            $content .= "### 1.3 数据库物理模型\n\n";
            $content .= $dataContent."\n\n";
            $content .= "## 2.接口\n\n";
            $content .= $this->apiMap[$prefix];
            $content .= "## 3.开发架构\n\n";
            $content .= "### 3.1 实现类图\n\n";
            $content .= $this->classDiagramMap[$prefix];
            $content .= $this->createPackageDesign($prefix)."\n\n";
            $content .= "## 4.运行流程\n\n";
            $content .= $this->processMap[$prefix]."\n\n";
            $content .= "## 5.测试用例设计\n\n";
            $content .= "### 5.1 单元测试用例设计\n\n";
            $content .= "包下面的每个类的公共方法都要编写相应的单元测试方法\n\n";
            $content .= "### 5.2 冒烟测试用例设计\n\n";
            $content .= "web目录下每个接口都要进行冒烟测试\n\n";
            $content .= "## 6.测试用例设计\n\n";
            $content .= "每层都要进行异常处理，并通过日志的形式记录异常信息\n\n";

            file_put_contents(OUTPUT_PATH.DS.$prefix.".md", $content);
        }

    }

    private function createOne($table, $prefix, $row) {
        $this->indexTables[$prefix] ++;

        $content = "#### 1.3.".$this->indexTables[$prefix].' '.$row['comment']."\n\n";
        $content .= "(1)字段说明\n\n";
        if (!isset($row['columns']) || empty($row['columns'])) {
            echo $table." 字段列表未获取到";
        }
        $content .= $this->createTable($row['columns']);
        $content .= "\n(2)SQL语句\n\n";
        if (!isset($row['ddl'])) {
            echo $table." DDL语句不存在";
        }
        $content .= $this->createBlock($row['ddl']);
        $this->dataMap[$prefix] .= $content;
        $this->createApi($table, $prefix, $row);
        $classDiagram = "#### 3.1.".$this->indexTables[$prefix].' '.$row['comment']."\n\n";
        $classDiagram .= "此类图为简单的调用，与其他中心等类似，不在此赘述\n\n";
        $this->classDiagramMap[$prefix] .= $classDiagram;

    }

    private function createApi($tableName, $prefix, $row) {
        $content = "### 2.".$this->indexTables[$prefix].' '.$row['comment']."管理\n\n";
        $content .= "#### 2.".$this->indexTables[$prefix].".1 ".$row['comment']."列表\n\n";
        $content .= "**(1)接口描述**\n\n";
        $content .= "查询".$row['comment']."列表\n\n";
        $content .= "**(2)接口地址**\n\n";
        $nameArr = explode("_", $tableName);
        $content .= "`GET` `/".$prefix."/v1/".$nameArr[count($nameArr)-1]."/list`\n\n";
        $content .= "**(3)输入**\n\n";
        $content .= "- 请求参数格式: QueryString\n";
        $content .= "- 请求参数\n\n";
        $content .= "\n参数名称 | 参数类型| 含义|是否必填|其他说明\n---|---|---|---|---\n";
        $apiRows = "";
        $queryString = "?";
        $table = "";
        $bodyJson = [];
        $exceptField = ['create_time', 'update_time'];
        $detailRows = "";
        foreach($row['columns'] as $column) {
            $line = "";
            if (!in_array($column['Field'], $exceptField)) {
                $queryString .= $column['Field']."=&";
                $bodyJson[$column['Field']] = "";
            }
            $table .= $column['Field']." | ".$this->getParamType($column['Type'])." | ";
            $line = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\|- ".$column['Field']." | ".$this->getParamType($column['Type'])." | ";
            $pos = strpos($column['Comment'], ':');
            $comment = $column['Comment'];
            $extra = "";
            if ($pos > 0 ) {
                $comment = substr($comment, 0, $pos);
                $extra = substr($comment, $pos);
            }
            $table .= $comment." | ";
            if ($column['Null'] == 'NO') {
                $table .= " 否 | ";
            } else {
                $table .= " 是 | ";
            }
            $line .= $comment." | ";
            $line .= $extra."\n";
            $detailRows .= $line;
            $apiRows .= "&nbsp;&nbsp;&nbsp;&nbsp;\|".$line;
            $table .= $extra;
            $table .= "\n";
        }
        $content .= $table. "page | Integer | 页码|否|默认为1\n";
        $content .= "pageSize | Integer | 每页显示数|否|默认为10\n";
        $content .= "- 示例\n\n";
        $content .= "```javascript\n".$queryString."page=1&pageSize=10\n```\n\n";
        $content .= "**(4)输出**\n\n";
        $content .= "- 输出格式: application/json\n- 输出参数\n\n";
        $content .= "参数名称 | 类型 | 含义 | 其他说明 \n";
        $content .= "---|---|---|--- \n";
        $content .= "code | int | 操作返回状态码 | \n";
        $content .= "message | String | 提示信息 | \n";
        $content .= "success | boolean | 是否成功 | \n";
        $content .= "data | Object | 返回的主体信息 |\n";
        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;\|- total |Integer | 总条数 |\n";
        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;\|- pages |Integer | 总页数 |\n";
        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;\|- page |Integer | 当前页码 |\n";
        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;\|- pageSize |Integer | 每页显示数 |\n";
        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;\|- list |List | 数据列表 |\n";
        $content .= $apiRows;
        $content .= "#### 2.".$this->indexTables[$prefix].".2 添加".$row['comment']."\n\n";
        $content .= "**(1)接口描述**\n\n";
        $content .= "添加".$row['comment']."\n\n";
        $content .= "**(2)接口地址**\n\n";
        $content .= "`POST` `/".$prefix."/v1/".$nameArr[count($nameArr)-1]."/create`\n\n";
        $content .= "**(3)输入**\n\n";
        $content .= "- 请求参数格式: application/json\n";
        $content .= "- 请求参数\n\n";
        $content .= "\n参数名称 | 参数类型| 含义|是否必填|其他说明\n---|---|---|---|---\n".$table;
        $content .= "- 示例\n\n";
        $content .= "```javascript\n".json_encode($bodyJson)."\n```\n\n";
        $content .= "**(4)输出**\n\n";
        $content .= "- 输出格式: application/json\n- 输出参数\n\n";
        $content .= "参数名称 | 类型 | 含义 | 其他说明 \n";
        $content .= "---|---|---|--- \n";
        $content .= "code | int | 操作返回状态码 | \n";
        $content .= "message | String | 提示信息 | \n";
        $content .= "success | boolean | 是否成功 | \n";
        $content .= "data | Object | 返回的主体信息 |\n";
        $content .= "#### 2".$this->indexTables[$prefix].".3 查询".$row['comment']."详情\n\n";
        $content .= "**(1)接口描述**\n\n";
        $content .= "查询".$row['comment']."详情\n\n";
        $content .= "**(2)接口地址**\n\n";
        $nameArr = explode("_", $tableName);
        $content .= "`GET` `/".$prefix."/v1/".$nameArr[count($nameArr)-1]."/detail`\n\n";
        $content .= "**(3)输入**\n\n";
        $content .= "- 请求参数格式: QueryString\n";
        $content .= "- 请求参数\n\n";
        $content .= "\n参数名称 | 参数类型| 含义|是否必填|其他说明\n---|---|---|---|---\n";
        $content .= $table. "\n";
        $content .= "- 示例\n\n";
        $content .= "```javascript\n".$queryString."id=10\n```\n\n";
        $content .= "**(4)输出**\n\n";
        $content .= "- 输出格式: application/json\n- 输出参数\n\n";
        $content .= "参数名称 | 类型 | 含义 | 其他说明 \n";
        $content .= "---|---|---|--- \n";
        $content .= "code | int | 操作返回状态码 | \n";
        $content .= "message | String | 提示信息 | \n";
        $content .= "success | boolean | 是否成功 | \n";
        $content .= "data | Object | 返回的主体信息 |\n";
        $content .= $detailRows;
        $content .= "#### 2.".$this->indexTables[$prefix].".4 更新".$row['comment']."\n\n";
        $content .= "**(1)接口描述**\n\n";
        $content .= "更新".$row['comment']."\n\n";
        $content .= "**(2)接口地址**\n\n";
        $content .= "`POST` `/".$prefix."/v1/".$nameArr[count($nameArr)-1]."/update`\n\n";
        $content .= "**(3)输入**\n\n";
        $content .= "- 请求参数格式: application/json\n";
        $content .= "- 请求参数\n\n";
        $content .= "\n参数名称 | 参数类型| 含义|是否必填|其他说明\n---|---|---|---|---\n".$table;
        $content .= "- 示例\n\n";
        $content .= "```javascript\n".json_encode($bodyJson)."\n```\n\n";
        $content .= "**(4)输出**\n\n";
        $content .= "- 输出格式: application/json\n- 输出参数\n\n";
        $content .= "参数名称 | 类型 | 含义 | 其他说明 \n";
        $content .= "---|---|---|--- \n";
        $content .= "code | int | 操作返回状态码 | \n";
        $content .= "message | String | 提示信息 | \n";
        $content .= "success | boolean | 是否成功 | \n";
        $content .= "data | Object | 返回的主体信息 |\n";
        $content .= "#### 2.".$this->indexTables[$prefix].".5 删除".$row['comment']."\n\n";
        $content .= "**(1)接口描述**\n\n";
        $content .= "删除".$row['comment']."\n\n";
        $content .= "**(2)接口地址**\n\n";
        $content .= "`POST` `/".$prefix."/v1/".$nameArr[count($nameArr)-1]."/delete`\n\n";
        $content .= "**(3)输入**\n\n";
        $content .= "- 请求参数格式: application/json\n";
        $content .= "- 请求参数\n\n";
        $content .= "\n参数名称 | 参数类型| 含义|是否必填|其他说明\n---|---|---|---|---\n";
        $content .= "ids | List | ID列表 | 是 | \n";
        $content .= "- 示例\n\n";
        $content .= "```javascript\n{\"ids\":[1,2,3]}\n```\n\n";
        $content .= "**(4)输出**\n\n";
        $content .= "- 输出格式: application/json\n- 输出参数\n\n";
        $content .= "参数名称 | 类型 | 含义 | 其他说明 \n";
        $content .= "---|---|---|--- \n";
        $content .= "code | int | 操作返回状态码 | \n";
        $content .= "message | String | 提示信息 | \n";
        $content .= "success | boolean | 是否成功 | \n";
        $content .= "data | Object | 返回的主体信息 |\n";
        $content .= "#### 2.".$this->indexTables[$prefix].".6 启用/禁用".$row['comment']."\n\n";
        $content .= "**(1)接口描述**\n\n";
        $content .= "启用/禁用".$row['comment']."状态\n\n";
        $content .= "**(2)接口地址**\n\n";
        $content .= "`POST` `/".$prefix."/v1/".$nameArr[count($nameArr)-1]."/enable`\n\n";
        $content .= "**(3)输入**\n\n";
        $content .= "- 请求参数格式: application/json\n";
        $content .= "- 请求参数\n\n";
        $content .= "\n参数名称 | 参数类型| 含义|是否必填|其他说明\n---|---|---|---|---\n";
        $content .= "ids | List | ID列表 | 是 | \n";
        $content .= "status | Integer | 状态 | 是 | \n";
        $content .= "- 示例\n\n";
        $content .= "```javascript\n{\"ids\":[1,2,3], \"status\":1}\n```\n\n";
        $content .= "**(4)输出**\n\n";
        $content .= "- 输出格式: application/json\n- 输出参数\n\n";
        $content .= "参数名称 | 类型 | 含义 | 其他说明 \n";
        $content .= "---|---|---|--- \n";
        $content .= "code | int | 操作返回状态码 | \n";
        $content .= "message | String | 提示信息 | \n";
        $content .= "success | boolean | 是否成功 | \n";
        $content .= "data | Object | 返回的主体信息 |\n";
        $this->apiMap[$prefix] .= $content;
        $processContent = "### 4.".$this->indexTables[$prefix]." ".$row['comment']."管理\n\n";
        $processContent .= "#### 4.".$this->indexTables[$prefix].".1 查询".$row['comment']."列表\n\n";
        $processContent .= "按照筛选条件，以分页的形式来查询".$row['comment']."列表\n\n";
        $processContent .= "#### 4.".$this->indexTables[$prefix].".2 新增".$row['comment']."\n\n";
        $processContent .= "保存".$row['comment']."数据\n\n";
        $processContent .= "#### 4.".$this->indexTables[$prefix].".3 根据ID查询".$row['comment']."信息\n\n";
        $processContent .= "查询指定ID的".$row['comment']."数据\n\n";
        $processContent .= "#### 4.".$this->indexTables[$prefix].".4 更新".$row['comment']."信息\n\n";
        $processContent .= "更新指定ID的".$row['comment']."数据\n\n";
        $processContent .= "#### 4.".$this->indexTables[$prefix].".5 删除".$row['comment']."信息\n\n";
        $processContent .= "删除指定ID的".$row['comment']."数据\n\n";
        $processContent .= "#### 4.".$this->indexTables[$prefix].".6 启用/禁用".$row['comment']."\n\n";
        $processContent .= "启用或禁用某个".$row['comment']."数据\n\n";
        $this->processMap[$prefix] .= $processContent;
    }

    private function getParamType($type) {
        if (substr($type, 0, 6) == "bigint") {
            return "Long";
        } else if (strpos($type, "int") >= 0) {
            return "int";
        } else if (strpos($type, "date") >= 0) {
            return "date";
        }
        return "String";
    }

    /**
     * 包设计
     * @param $prefix
     * @return String
     */
    private function createPackageDesign($prefix) {
        $content = "### 3.2 包设计\n\n";
        foreach ($this->config['layers'] as $layer) {
            $content .= "- ".$this->config['namespace'].".".$prefix.".".$layer."\n";
        }
        return $content;
    }

    private function createTable($columns) {
        $content = "\n字段名称 | 数据类型| 含义|是否可为空|其他说明\n---|---|---|---|---\n";
        foreach($columns as $column) {
            $content .= $column['Field']." | ".$column['Type']." | ";
            $pos = strpos($column['Comment'], ':');
            $comment = $column['Comment'];
            $extra = "";
            if ($pos > 0 ) {
                $comment = substr($comment, 0, $pos);
                $extra = substr($comment, $pos);
            }
            $content .= $comment." | ";
            if ($column['Null'] == 'NO') {
                $content .= " 否 | ";
            } else {
                $content .= " 是 | ";
            }
            $content .= $extra;
            $content .= "\n";
        }
        return $content."\n\n";
    }

    private function createBlock($ddl) {
        return "```sql\n".$ddl."\n```\n\n";
    }
}