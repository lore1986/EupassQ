<?php

namespace EupassQ\PhpClasses;

class EupassqDatabase {

    private $_wpdb;
    private $euqTable;
    private $tablePrefix;

    public function __construct() {
        
        global $wpdb;
        $this->_wpdb = $wpdb;
        $this->tablePrefix = $wpdb->prefix;
        
        $this->euqTable =  $this->tablePrefix . 'eupqs';

        add_action('init', [$this, 'Eupassq_Create_Tables']);
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

    public function Eupassq_Create_Tables() {
  
        
        $charset_collate = $this->_wpdb->get_charset_collate();

        $eupassq_questions = $this->_wpdb->prefix . 'eupqs';


        $sql = [];

        if ($this->_wpdb->get_var("SHOW TABLES LIKE '{$eupassq_questions}'") === $eupassq_questions) {
            return;
        } else {
         
            $sql[] = "CREATE TABLE $eupassq_questions (
                euqid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                euqtpe ENUM('text','audio') NOT NULL,
                euqlvl ENUM('A1','A2','B1','B2','C1', 'C2') NOT NULL,
                euqpostid BIGINT NOT NULL DEFAULT 0,
                euqtitl TEXT NOT NULL, 
                euqcontent LONGTEXT NOT NULL,
                euqcnt BIGINT NOT NULL DEFAULT 0,
                PRIMARY KEY (euqid)
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            foreach ($sql as $query) {
                dbDelta($query);
            }
        }

        
          
    }

}