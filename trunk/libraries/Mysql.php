<?php

//mysqli 操作类


/* where 字句
    [ 'a' => 1 ]        //where a = 1
    [ 'a[!]' => 1 ]     //where a != 1
    [ 'a[>]' => 1 ]     //where a > 1
    [ 'a[<]' => 1 ]     //where a < 1
    [ 'a[<>]' => [1,100] ]     //where a BETWEEN 1 AND 100
    [ 'a[><]' => [1,100] ]     //where a NOT BETWEEN 1 AND 100
    [ 'a[~]' => 'a' ]     //where a like '%a%'
    [ 'a[!~]' => 'a' ]     //where a not like '%a%'
    [ 'a[~]' => ['aa', 'bb', 'cv']     //where "a" LIKE '%aa%' OR "a" LIKE '%bb%' OR "a" LIKE '%cc%'




    字句组合
    // where  a =1 AND b =1
    [
        'AND' => [
            'a'=> 1,
            'b'=>1
        ]
    ]

    // where  a =1 OR b =1
    [
        'OR' => [
            'a'=> 1,
            'b'=>1
        ]
    ]

    // where  (a =1 OR b =1) AND ( a=1 OR b= 1) AND c = 3
    [
        'AND #' = [
            //由于两个键值相等， 所以加 # 符号作为区分
            'OR #1' => [
                'a'=> 1,
                'b'=>1
            ],
            'OR #2' => [
                'a'=> 1,
                'b'=>1
            ],
            'c' => 2
        ]
    ]

    //group by
    [
        'GROUP' => 'a',    //group by a

        'GROUP' => [ 'a','b','c' ], //group by  a，b，c


        'GROUP' => 'a',
        'HAVING' => [ 'a[>]' => 500 ]  //group by a having a > 500
    ]


    //order by
    // order by a desc, b asc
    [
        'ORDER' => [
            'a'=> 'DESC',
            'b'=> 'ASC'
        ]
    ]

    //limit
    [
        'LIMIT' => 100          // limit 100

        'LIMIT' => [20, 100],   // limit 20，100
    ]



    //mysql 函数   ,需要在字段名 前加 #符号，并且值 大写
    [
        '#a' => 'NOW()',        //a = NOW()
    ]
 */



/*  select
    $database->select_all("account", [
        "user_name",
        "email"
    ], [
        "user_id[>]" => 100
    ]);

    $database->select_row("account", [
        "user_name",
        "email"
    ], [
        "user_id[>]" => 100
    ]);
 */


/*  insert
    $database->insert("account", [
    	"user_name" => "foo",
    	"email" => "foo@bar.com",
    	"age" => 25
    ]);
 */


/*  update
    $database->update("account", [
    	"user_name" => "foo",
    	"email" => "foo@bar.com",
    	"age[+]" => 1
    ]);
 */


/*  delete
    $database->delete("account", [
        "AND" => [
            "type" => "business",
            "age[<]" => 18
        ]
    ]);
 */


class Library_Mysql {

    public $conn_id = false; //mysql 对象
    public $result_id = false;
    public $debug_mode = false;
    public $sql_loops = [];

    //连接
    public function db_connect( $db_config ,$persistent = false){
        $this->conn_id= mysqli_init();

        $this->conn_id->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        $db_config['hostname'] = ($persistent === TRUE) ? 'p:'.$db_config['hostname'] : $db_config['hostname'];

        if ( $this->conn_id->real_connect($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database'], $db_config['port'], null, $db_config['compress'])){
            return $this->conn_id;
        }

        $this->get_error( http_build_str($db_config) );
        return FALSE;

    }

    //断线重连
    public function reconnect(){
        if ($this->conn_id !== FALSE && $this->conn_id->ping() === FALSE){
            $this->conn_id = FALSE;
        }
    }


    //关闭
    public function close(){
        if( $this->conn_id ){
            $this->conn_id->close();
        }
    }


    public function __destruct(){
        $this->close();
    }

    public function select_all( $tablename, $columns = '*', $where = [],$index_key = null ){

        $this->result_id = $this->query($this->select_context($tablename, $columns, $where));

        $result_array = array();

        if (! $this->result_id )
        {
            return false;
        }


        while ($row = $this->result_id->fetch_assoc())
        {
            if( $index_key && isset( $row [$index_key]) ){
                $result_array[ $row [$index_key] ] = $row;
            }else{
                $result_array[ ] = $row;
            }

        }

        return $result_array;

    }


    public function select_row(  $tablename, $columns = '*', $where = [] ){
        $where = array_merge($where,['LIMIT' => 1]);
        if ( $result_array = $this->select_all( $tablename, $columns, $where) ){
           return $result_array[0];
        }

        return false;
    }


    public function update( $tablename, $data, $where){
        $fields = array();

        foreach ($data as $key => $value)
        {
            preg_match('/([\w]+)(\[(\+|\-|\*|\/)\])?/i', $key, $match);

            if (isset($match[ 3 ]))
            {
                if (is_numeric($value))
                {
                    $fields[] = $this->column_quote($match[ 1 ]) . ' = ' . $this->column_quote($match[ 1 ]) . ' ' . $match[ 3 ] . ' ' . $value;
                }
            }
            else
            {
                $column = $this->column_quote(preg_replace("/^(\(JSON\)\s*|#)/i", "", $key));

                switch (gettype($value))
                {
                    case 'NULL':
                        $fields[] = $column . ' = NULL';
                        break;

                    case 'array':
                        preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

                        $fields[] = $column . ' = ' . $this->quote(
                                isset($column_match[ 0 ]) ? json_encode($value) : serialize($value)
                            );
                        break;

                    case 'boolean':
                        $fields[] = $column . ' = ' . ($value ? '1' : '0');
                        break;

                    case 'integer':
                    case 'double':
                    case 'string':
                        $fields[] = $column . ' = ' . $this->fn_quote($key, $value);
                        break;
                }
            }
        }

        return $this->query('UPDATE ' . $this->table_quote( $tablename ) . ' SET ' . implode(', ', $fields) . $this->where_clause($where));

    }

    public function delete ( $table, $where){
        return $this->query('DELETE FROM ' . $this->table_quote($table) . $this->where_clause($where));
    }

    public function insert( $tablename, $data ){

        if (!is_array( $data )) {
            return 0;
        }


        $values = array();
        $columns = array();

        foreach ($data as $key => $value)
        {
            $columns[] = $this->column_quote(preg_replace("/^(\(JSON\)\s*|#)/i", "", $key));

            switch (gettype($value))
            {
                case 'NULL':
                    $values[] = 'NULL';
                    break;

                case 'array':
                    preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

                    $values[] = isset($column_match[ 0 ]) ?
                        $this->quote(json_encode($value)) :
                        $this->quote(serialize($value));
                    break;

                case 'boolean':
                    $values[] = ($value ? '1' : '0');
                    break;

                case 'integer':
                case 'double':
                case 'string':
                    $values[] = $this->fn_quote($key, $value);
                    break;
            }
        }


        if( $this->query('INSERT INTO ' . $this->table_quote( $tablename ) . ' (' . implode(', ', $columns) . ') VALUES (' . implode($values, ', ') . ')') ){
            return $this->insert_id();
        }

        return 0;
    }


    public function query( $sql ){

        if( $this->debug_mode ){
            echo $sql;
            return false;
        }


        array_push($this->sql_loops,$sql);

        if ( ! ( $result_id = $this->conn_id->query($sql) ) ){
            $this->get_error( $sql );
        }

        return $result_id;
    }

    public function insert_id(){
        return $this->conn_id->insert_id;
    }


    public function affected_rows(){
        return $this->conn_id->affected_rows;
    }

    //count
    public function count($table, $column = null, $where = null)
    {
        $result_id = $this->query($this->select_context($table,  $column, $where, 'COUNT'));

        $row = $result_id->fetch_row();

        return $result_id ? 0 + $row[0] : false;
    }

    public function max($table,  $column = null, $where = null)
    {
        $result_id = $this->query($this->select_context($table,  $column, $where, 'MAX'));

        $row = $result_id->fetch_row();

        if ($result_id) {
            $max = $row[0];

            return is_numeric($max) ? $max + 0 : $max;
        } else {
            return false;
        }
    }

    public function min($table,  $column = null, $where = null)
    {
        $result_id = $this->query($this->select_context($table,  $column, $where, 'MIN'));

        $row = $result_id->fetch_row();

        if ($result_id)
        {
            $min = $row[0];

            return is_numeric($min) ? $min + 0 : $min;
        }
        else
        {
            return false;
        }
    }

    public function avg($table, $column = null, $where = null)
    {
        $result_id = $this->query($this->select_context($table, $column, $where, 'AVG'));

        $row = $result_id->fetch_row();

        return $result_id ? 0 + $row[0] : false;
    }

    public function sum($table, $column = null, $where = null)
    {
        $result_id = $this->query($this->select_context($table, $column, $where, 'SUM'));

        $row = $result_id->fetch_row();

        return $result_id ? 0 + $row[0] : false;
    }


    public function trans_begin(){
        $this->conn_id->autocommit(FALSE);
        return $this->conn_id->begin_transaction(); // can also be BEGIN or BEGIN WORK
    }


    public function trans_commit() {
        if ($this->conn_id->commit()){
            $this->conn_id->autocommit(TRUE);
            return TRUE;
        }

        return FALSE;
    }

    public function trans_rollback(){
        if ($this->conn_id->rollback()){
            $this->conn_id->autocommit(TRUE);
            return TRUE;
        }

        return FALSE;
    }


    //解析 select sql
    protected function select_context($table, &$columns = null, $where = null, $column_fn = null){
        preg_match('/([a-zA-Z0-9_\-]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $table, $table_match);

        if (isset($table_match[ 1 ], $table_match[ 2 ])){
            $table_query = $this->table_quote($table_match[ 1 ]) . ' AS ' . $this->table_quote($table_match[ 2 ]);
        }else{
            $table_query = $this->table_quote($table);
        }

        if (isset($column_fn)) {
            if (empty($columns)){
                $columns = '*';
            }

            $column = $column_fn . '(' . $this->column_push($columns) . ')';

        }else{
            $column = $this->column_push($columns);
        }

        return 'SELECT ' . $column . ' FROM ' . $table_query . $this->where_clause($where);
    }

    //生成字段连接字符
    protected function column_push(&$columns) {
        if ($columns == '*') {
            return $columns;
        }

        if (is_string($columns)) {
            $columns = array($columns);
        }

        $stack = array();

        foreach ($columns as $key => $value) {
            if (is_array($value)) {
                $stack[] = $this->column_push($value);
            } else {
                preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

                if (isset($match[ 1 ], $match[ 2 ])) {
                    $stack[] = $this->column_quote( $match[ 1 ] ) . ' AS ' . $this->column_quote( $match[ 2 ] );

                    $columns[ $key ] = $match[ 2 ];
                } else {
                    $stack[] = $this->column_quote( $value );
                }
            }
        }

        return implode($stack, ',');
    }

    //where 数组解析
    protected function where_clause($where){
        $where_clause = '';

        if (is_array($where)){
            $where_keys = array_keys($where);
            $where_AND = preg_grep("/^AND\s*#?$/i", $where_keys);
            $where_OR = preg_grep("/^OR\s*#?$/i", $where_keys);

            $single_condition = array_diff_key($where, array_flip(
                array('AND', 'OR', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH')
            ));

            if ($single_condition != array()) {
                $condition = $this->data_implode($single_condition, '');

                if ($condition != '') {
                    $where_clause = ' WHERE ' . $condition;
                }
            }

            if (!empty($where_AND)) {
                $value = array_values($where_AND);
                $where_clause = ' WHERE ' . $this->data_implode($where[ $value[ 0 ] ], ' AND');
            }

            if (!empty($where_OR)) {
                $value = array_values($where_OR);
                $where_clause = ' WHERE ' . $this->data_implode($where[ $value[ 0 ] ], ' OR');
            }

            if (isset($where[ 'MATCH' ])) {
                $MATCH = $where[ 'MATCH' ];

                if (is_array($MATCH) && isset($MATCH[ 'columns' ], $MATCH[ 'keyword' ])) {
                    $where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ') . ' MATCH ("' . str_replace('.', '"."', implode($MATCH[ 'columns' ], '", "')) . '") AGAINST (' . $this->quote($MATCH[ 'keyword' ]) . ')';
                }
            }

            if (isset($where[ 'GROUP' ])) {
                $where_clause .= ' GROUP BY ' . $this->column_quote($where[ 'GROUP' ]);

                if (isset($where[ 'HAVING' ])) {
                    $where_clause .= ' HAVING ' . $this->data_implode($where[ 'HAVING' ], ' AND');
                }
            }

            if (isset($where[ 'ORDER' ])) {
                $ORDER = $where[ 'ORDER' ];

                if (is_array($ORDER)) {
                    $stack = array();

                    foreach ($ORDER as $column => $value) {
                        if (is_array($value)) {
                            $stack[] = 'FIELD(' . $this->column_quote($column) . ', ' . $this->array_quote($value) . ')';
                        }else if ($value === 'ASC' || $value === 'DESC') {
                            $stack[] = $this->column_quote($column) . ' ' . $value;
                        }else if (is_int($column)) {
                            $stack[] = $this->column_quote($value);
                        }
                    }

                    $where_clause .= ' ORDER BY ' . implode($stack, ',');
                }else{
                    $where_clause .= ' ORDER BY ' . $this->column_quote($ORDER);
                }
            }

            if (isset($where[ 'LIMIT' ])){
                $LIMIT = $where[ 'LIMIT' ];

                if (is_numeric($LIMIT)){
                    $where_clause .= ' LIMIT ' . $LIMIT;
                }

                if (
                    is_array($LIMIT) &&
                    is_numeric($LIMIT[ 0 ]) &&
                    is_numeric($LIMIT[ 1 ])
                ){
                    $where_clause .= ' LIMIT ' . $LIMIT[ 0 ] . ',' . $LIMIT[ 1 ];
                }
            }
        }else{
            if ($where != null){
                $where_clause .= ' ' . $where;
            }
        }

        return $where_clause;
    }


    protected function data_implode($data, $conjunctor ){
        $wheres = array();

        foreach ($data as $key => $value) {
            $type = gettype($value);

            if (
                preg_match("/^(AND|OR)(\s+#.*)?$/i", $key, $relation_match) &&
                $type == 'array'
            )
            {
                $wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
                    '(' . $this->data_implode($value, ' ' . $relation_match[ 1 ]) . ')' :
                    '(' . $this->inner_conjunct($value, ' ' . $relation_match[ 1 ], $conjunctor) . ')';
            }
            else
            {
                preg_match('/(#?)([\w\.\-]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\>\<|\!?~)\])?/i', $key, $match);
                $column = $this->column_quote($match[ 2 ]);

                if (isset($match[ 4 ]))
                {
                    $operator = $match[ 4 ];

                    if ($operator == '!')
                    {
                        switch ($type)
                        {
                            case 'NULL':
                                $wheres[] = $column . ' IS NOT NULL';
                                break;

                            case 'array':
                                $wheres[] = $column . ' NOT IN (' . $this->array_quote($value) . ')';
                                break;

                            case 'integer':
                            case 'double':
                                $wheres[] = $column . ' != ' . $value;
                                break;

                            case 'boolean':
                                $wheres[] = $column . ' != ' . ($value ? '1' : '0');
                                break;

                            case 'string':
                                $wheres[] = $column . ' != ' . $this->fn_quote($key, $value);
                                break;
                        }
                    }

                    if ($operator == '<>' || $operator == '><')
                    {
                        if ($type == 'array')
                        {
                            if ($operator == '><')
                            {
                                $column .= ' NOT';
                            }

                            if (is_numeric($value[ 0 ]) && is_numeric($value[ 1 ]))
                            {
                                $wheres[] = '(' . $column . ' BETWEEN ' . $value[ 0 ] . ' AND ' . $value[ 1 ] . ')';
                            }
                            else
                            {
                                $wheres[] = '(' . $column . ' BETWEEN ' . $this->quote($value[ 0 ]) . ' AND ' . $this->quote($value[ 1 ]) . ')';
                            }
                        }
                    }

                    if ($operator == '~' || $operator == '!~')
                    {
                        if ($type != 'array')
                        {
                            $value = array($value);
                        }

                        $like_clauses = array();

                        foreach ($value as $item)
                        {
                            $item = strval($item);

                            if (preg_match('/^(?!(%|\[|_])).+(?<!(%|\]|_))$/', $item))
                            {
                                $item = '%' . $item . '%';
                            }

                            $like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $this->fn_quote($key, $item);
                        }

                        $wheres[] = implode(' OR ', $like_clauses);
                    }

                    if (in_array($operator, array('>', '>=', '<', '<=')))
                    {
                        $condition = $column . ' ' . $operator . ' ';

                        if (is_numeric($value))
                        {
                            $condition .= $value;
                        }
                        elseif (strpos($key, '#') === 0)
                        {
                            $condition .= $this->fn_quote($key, $value);
                        }
                        else
                        {
                            $condition .= $this->quote($value);
                        }

                        $wheres[] = $condition;
                    }
                }
                else
                {
                    switch ($type)
                    {
                        case 'NULL':
                            $wheres[] = $column . ' IS NULL';
                            break;

                        case 'array':
                            $wheres[] = $column . ' IN (' . $this->array_quote($value) . ')';
                            break;

                        case 'integer':
                        case 'double':
                            $wheres[] = $column . ' = ' . $value;
                            break;

                        case 'boolean':
                            $wheres[] = $column . ' = ' . ($value ? '1' : '0');
                            break;

                        case 'string':
                            $wheres[] = $column . ' = ' . $this->fn_quote($key, $value);
                            break;
                    }
                }
            }
        }

        return implode($conjunctor . ' ', $wheres);
    }

    protected function inner_conjunct($data, $conjunctor, $outer_conjunctor)
    {
        $haystack = array();

        foreach ($data as $value)
        {
            $haystack[] = '(' . $this->data_implode($value, $conjunctor) . ')';
        }

        return implode($outer_conjunctor . ' ', $haystack);
    }

    //数组转义
    protected function array_quote($array)
    {
        $temp = array();

        foreach ($array as $value)
        {
            $temp[] = is_int($value) ? $value : $this->quote($value);
        }

        return implode($temp, ',');
    }

    protected function fn_quote($column, $string) {
        return (strpos($column, '#') === 0 && preg_match('/^[A-Z0-9\_]*\([^)]*\)$/', $string)) ?
            $string : $this->quote($string,true);
    }

    //字符串转义
    public function quote($string, $is_quote = false)
    {
        if( is_array( $string ) ){
            $mysqli = & $this;

            return array_map(function( $value )use( $mysqli ){
                return $mysqli->quote( $value );
            },$string);
        }

        if( $is_quote == true ){
            return "'".$this->conn_id->real_escape_string($string)."'";
        }else{
            return $this->conn_id->real_escape_string($string);
        }

    }

    //表名 引号
    protected function table_quote($table){
        return '`' . $table . '`';
    }

    //字段 引号
    protected function column_quote($string){
        preg_match('/(\(JSON\)\s*|^#)?([a-zA-Z0-9_]*)\.([a-zA-Z0-9_]*)/', $string, $column_match);

        if (isset($column_match[ 2 ], $column_match[ 3 ])){
            return '`' .  $column_match[ 2 ] . '`.`' . $column_match[ 3 ] . '`';
        }

        return '`' . $string . '`';
    }

    //捕获错误
    protected function get_error( $message ){
        if ($this->conn_id->error) {
            $response = load_class('Response','core');
            $response->set_error_code('B00006');

            throw new ErrorException( $this->conn_id->error .' --> '.$message,$this->conn_id->errno, E_DB_ERROR);
        }
    }

    //获取最后执行的sql语句
    public function get_last_sqls() {
        return $this->sql_loops;
    }

    //打印sql
    public function print_sql(){
        $this->debug_mode = true;
        return $this;
    }
}