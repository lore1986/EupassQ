<?php 

namespace EupassQ\PhpClasses;


class EupassQuizHandler {

    private $grader;

    public function __construct() {
        global $wpdb;
        $this->grader = new EupassQGrader(); 
    }


    
    public function EupassQ_Calculate_Get_User_Results($result_code, $qsmUid)
    {
        //validate input uuid 
        //get results from qsm
        //get analyze results with open ai
        //combine results
        //delete audio from tmp folder and database

        $test = $this->grader->EupassQ_Handle_Submissions($result_code, $qsmUid);

        return $test;
    }



}
