<?php

namespace EupassQ\PhpClasses;

class EupassqDatabase {

    private $_wpdb;
    private $euqTable;
    public $tablePrefix;

    public function __construct() {
        
        global $wpdb;
        $this->_wpdb = $wpdb;
        $this->tablePrefix = $wpdb->prefix;
        
        $this->euqTable =  $this->tablePrefix . 'eupqs';

        add_action('init', [$this, 'Eupassq_Create_Tables']);
    }

    public function Eupassq_return_Setting_value($key)
    {
        $table = $this->tablePrefix . 'eupass_set';
        $row = $this->_wpdb->get_row($this->_wpdb->prepare("SELECT * FROM $table WHERE euq_key = %s", $key));

        if($row != null)
        {
            return $row->euq_val;
        }else
        {
            return null;
        }
    }

    public function Eupassq_Check_Insert_Replace_Setting_value($key, $valin)
    {
        $table = $this->tablePrefix . 'eupass_set';

        $count = $this->_wpdb->get_var(
            $this->_wpdb->prepare("SELECT COUNT(*) FROM $table WHERE euq_key = %s", $key)
        );

        if ($count > 0) {
            $deleted = $this->_wpdb->delete(
                $table,
                [ 'euq_key' => $key ],
                [ '%s' ]
            );
        }

        $data = [
            'euq_key' => $key,
            'euq_val' => $valin,
        ];
        $format = ['%s', '%s'];

        $this->_wpdb->insert($table, $data, $format);

    }

    public function EupassQ_QuizId_randomizer()
    {
        $d_arr = 'abcdefghilmnopqrstuwvyz123456789';
        $cod = '';

        for ($i=0; $i < 8; $i++) { 
            
            $cod .= $d_arr[rand(0, strlen($d_arr)-1)];
        }

        return $cod;
    }


    public function Eupassq_Get_All_Questions_Of_Level($euqlvl)
    {
    
        return $this->_wpdb->get_results($this->_wpdb->prepare("SELECT * FROM $this->euqTable 
            WHERE euqlvl = %s", $euqlvl), ARRAY_A);

    }

    public function Eupassq_Find_Single_Question_PostId($postid)
    {
        return $this->_wpdb->get_row($this->_wpdb->prepare("SELECT * FROM $this->euqTable WHERE euqpostid = %d", $postid));
    }

    public function Eupassq_Get_Single_Question($quid)
    {
        return $this->_wpdb->get_row($this->_wpdb->prepare("SELECT * FROM $this->euqTable WHERE euqid = %d", $quid));
    }

    public function Eupassq_Update_Single_Question_Entry($colName, $colData, $colFormat, $qId)
    {
        $dObj = array(
            $colName => $colData,
        );

        $dObjFormat = array($colFormat);
        
        $where = array(
            'euqid' => $qId,
        );
        $whereFormat = array('%d');

        $result_update = $this->_wpdb->update($this->euqTable, $dObj, $dObjFormat, $where, $whereFormat);

        return $result_update;
    }


    public function Eupassq_Validate_TypeLevel($typ, $lvl)
    {
        $allowed_types = ['text', 'audio'];
        $allowed_levels = ['A1','A2','B1','B2','C1','C2'];

        $type  = in_array($typ, $allowed_types, true) ? $typ : null;
        $level = in_array($lvl, $allowed_levels, true) ? $lvl : null;

        return array($type, $level);
    }

    public function Europassq_Delete_Autosave($postid)
    {
        $table_name = $this->tablePrefix . 'posts';

        $this->_wpdb->query($this->_wpdb->prepare( 
            "DELETE FROM $table_name WHERE post_parent = %d 
            AND post_name LIKE '%%autosave%%'" ,
            $postid));
    }

    public function Eupassq_Insert_Update_Question_Entry($obj, $actionid = 0)
    {

        //  euqid BIGINT UNSIGNED NOT NULL,
        // euqtpe ENUM('text','audio') NOT NULL,
        // euqlvl ENUM('A1','A2','B1','B2','C1', 'C2') NOT NULL,
        // euqpostid BIGINT NOT NULL DEFAULT 0,
        // euqtitl TEXT NOT NULL, 
        // euqcontent BIGINT NOT NULL DEFAULT 0,
        // euqcnt BIGINT NOT NULL DEFAULT 0,

        $arrTypLvl = $this->Eupassq_Validate_TypeLevel($obj->euqtpe, $obj->euqlvl);

        $dObj = array(
            'euqtpe' => $arrTypLvl[0],
            'euqlvl' => $arrTypLvl[1],
            'euqpostid' => $obj->euqpostid,
            'euqtitl' => $obj->euqtitl,
            'euqcontent' => $obj->euqcontent,
            'euqcnt' => $obj->euqcnt
        );
 
        $dObjFormat = array('%s', '%s', '%d', '%s','%s', '%d');

        if($actionid == 0)
        {
            $result_insert = $this->_wpdb->insert($this->euqTable, $dObj, $dObjFormat);
            return $result_insert;

        }else
        {
            $where = array(
                'euqid' => intval($obj->euqid),
            );
            $whereFormat = array('%d');

            $result_update = $this->_wpdb->update($this->euqTable, $dObj, $where,  $dObjFormat, $whereFormat);
            return $result_update;
        }
        
    }

    public function Eupassq_Get_Tmp_Answers($code)
    {
        $eupassq_tmp_quiz = $this->_wpdb->prefix . 'eupassq_tmp';

        return $this->_wpdb->get_results($this->_wpdb->prepare("SELECT * FROM $eupassq_tmp_quiz 
            WHERE euqtid = %s", $code), ARRAY_A);

    }


    public function Eupassq_Create_Tables() {
  
        
        $charset_collate = $this->_wpdb->get_charset_collate();
        $eupassq_questions = $this->_wpdb->prefix . 'eupqs' ;
        $eupassq_tmp_quiz = $this->_wpdb->prefix . 'eupassq_tmp';
        $eupassq_sec = $this->_wpdb->prefix . 'eupass_set';

        $sql = [];

        if (!($this->_wpdb->get_var("SHOW TABLES LIKE '{$eupassq_questions}'") === $eupassq_questions)) {

            $sql_1 = "CREATE TABLE $eupassq_questions (
                euqid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                euqtpe ENUM('text','audio') NOT NULL,
                euqlvl ENUM('A1','A2','B1','B2','C1', 'C2') NOT NULL,
                euqpostid BIGINT NOT NULL DEFAULT 0,
                euqtitl TEXT NOT NULL, 
                euqcontent LONGTEXT NOT NULL,
                euqcnt BIGINT NOT NULL DEFAULT 0,
                PRIMARY KEY (euqid)
            ) $charset_collate;";

            array_push($sql, $sql_1);
        }

        if (!($this->_wpdb->get_var("SHOW TABLES LIKE '{$eupassq_tmp_quiz}'") === $eupassq_tmp_quiz)) {
  
            $sql_2 = "CREATE TABLE $eupassq_tmp_quiz (
                euqiidd BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                euqtid CHAR(8) NOT NULL ,
                eupqid BIGINT UNSIGNED NOT NULL,
                eupquid BIGINT UNSIGNED NOT NULL,
                euqanswer TEXT NOT NULL, 
                PRIMARY KEY (euqiidd)
            ) $charset_collate;";

            array_push($sql, $sql_2);
        }



        if(!($this->_wpdb->get_var("SHOW TABLES LIKE '{$eupassq_sec}'") === $eupassq_sec))
        {

            $sql_e = "CREATE TABLE $eupassq_sec (
                euq_key VARCHAR(255) NOT NULL,
                euq_val MEDIUMTEXT NOT NULL , 
                PRIMARY KEY (euq_key) 
            ) $charset_collate;";

            array_push($sql, $sql_e);
        }

        

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        foreach ($sql as $query) {
            dbDelta($query);
        }
          
    }

    public function Eupassq_Insert_Quiz_Entry($objArr)
    {


        $eupassq_tmp_quiz = $this->_wpdb->prefix . 'eupassq_tmp';
        $code = $this->EupassQ_QuizId_randomizer();

        foreach ($objArr as $euqa) {
            
            $dObj = array(
                'euqtid' => $code,
                'eupqid' => $euqa['question_id'],
                'eupquid' => $euqa['uid'],
                'euqanswer' => $euqa['answer']
            );

            $dObjFormat = array('%s', '%d', '%d', '%s');

            $result_insert = $this->_wpdb->insert($eupassq_tmp_quiz, $dObj, $dObjFormat);
            
            if($result_insert)
            {
                $s=0;
            }
        }
 
        return $code;
    }



}