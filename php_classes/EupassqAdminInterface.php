<?php

namespace EupassQ\PhpClasses;

use stdClass;

class EupassqAdminInterface
{
    private $dbGb;
    private $nc;

    public function __construct($_dbGb, $_nc) {
        $this->dbGb = $_dbGb;
        $this->nc = $_nc;

        add_action('init', [$this, 'eupassq_register_question_cpt']);
        add_action('admin_menu', [$this, 'eupassq_admin_menu']);
        add_action('save_post_eupassq', [$this, 'eupassq_creation_intercept'], 10, 2);
        add_action('add_meta_boxes_eupassq',[$this, 'eupassq_add_type_metabox'] );
        add_action( 'admin_notices', [$this,'eupassq_show_admin_notice'] );
        add_filter( 'wp_insert_post_data', [$this,'eupassq_validate_before_save'], 10, 2 );
        add_action('admin_enqueue_scripts', 'TFIP_admin_enqueue_scripts');

        add_action( 'wp_ajax_EurpasQ_render_admin_question', array($this, 'EurpasQ_render_admin_question'));
        
        add_filter('manage_eupassq_posts_columns', array($this, 'EupassQ_add_content_column'));
        add_action('manage_eupassq_posts_custom_column', array($this, 'EupassQ_fill_content_columns'), 10, 2);

    }


    /**
     * Load  css and js
     */
    function eupassq_admin_enqueue_scripts()
    {
        
        wp_enqueue_style('eupassq_admin_css', plugin_dir_url(__DIR__) . 'assets/css/eupassqadmin.css');

        // wp_enqueue_script(
        //     'EupassQAdmin',
        //     plugin_dir_url(__DIR__) . 'assets/js/admin.js',
        //     [],
        //     null,
        //     true 
        // );

        //  wp_localize_script('EupassQAdmin', 'EupQ_Ajax_Obj', array(
        //     'ajaxUrl' => admin_url('admin-ajax.php'),
        //     'nonce' => wp_create_nonce('euq_pass_nonce')
        // ));
    }

    
    function EupassQ_add_content_column($columns) {
        $new = [];

        foreach ($columns as $key => $title) {
            $new[$key] = $title; 

           
            if ($key === 'title') { // 'title' is the title column second one
                $new['euqcontent'] = __('Eupass Question Content', 'textdomain');
            }
        }

        return $new;
    }

    function EupassQ_fill_content_columns($column, $post_id) {
        if ($column === 'euqcontent') {

            $eupassQ = $this->dbGb->Eupassq_Find_Single_Question_PostId($post_id);
            echo esc_html($eupassQ->euqcontent ?: '—');
        }
    }

    function EurpasQ_render_admin_question()
    {
        if(isset($_POST['id']))
        {
            $idpost = intval($_POST['id']);

            $eupassQ = $this->dbGb->Eupassq_Find_Single_Question_PostId($idpost);
            
            wp_send_json_success($eupassQ->euqcontent);
        }
    }

    /**
     * Register custom post type for Questions
     */
    function eupassq_register_question_cpt()
    {
        $labels = array(
            'name'          => __( 'EupassQs', 'eupassq' ),
            'singular_name' => __( 'EupassQ', 'eupassq' ),
            'add_new_item'  => __( 'Add New Question', 'eupassq' ),
            'edit_item'     => __( 'Edit Question', 'eupassq' ),
        );

        $args = array(
            'labels'       => $labels,
            'public'       => false,    
            'show_ui'      => true,     
            'show_in_menu' => false,  
            'supports'     => ['editor'],
            //'register_meta_box_cb' => [$this,  'eupassq_add_type_metabox'],
        );

        register_post_type('eupassq', $args);
    }


    /**
     * Admin Notice for errors
     */
    function eupassq_show_admin_notice() {
        if ( isset($_GET['eupassq_error']) ) {
            $msg = '';

            switch ( $_GET['eupassq_error'] ) {
                case 'missing':
                    $msg = __( 'Missing type of question or level. Please fill in required fields before publishing.', 'eupassq' );
                    break;
                case 'db':
                    $msg = __( 'Database error while saving the EupassQ entry.', 'eupassq' );
                    break;
                default:
                    $msg = __( 'An unknown error occurred.', 'eupassq' );
            }
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html( $msg ); ?></p>
            </div>
            <?php
        }
    }


    /**
     * Admin menu setup
     */
    function eupassq_admin_menu()
    {
        add_menu_page(
            'EupassQ Menu',                        // Page title
            'EupassQ',                             // Menu title
            'manage_options',                      // Capability
            'eupass_admin_dash',                   // Menu slug
            [$this, 'eupassq_generate_menu'],      // Callback
            'dashicons-megaphone'                // Icon
        );

        add_submenu_page(
            'eupass_admin_dash',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'eupass_admin_dash',                   
            [$this, 'eupassq_generate_menu'],
            0
        );

        add_submenu_page(
            'eupass_admin_dash',
            'All EupassQs',
            'All EupassQs',
            'manage_options',
            'edit.php?post_type=eupassq'
        );


        add_submenu_page(
            'eupass_admin_dash',
            'Add New',
            'Add New',
            'manage_options',
            'post-new.php?post_type=eupassq'
        );
    }


    /**
     * Metabox
     */

    function eupassq_add_type_metabox() {

        add_meta_box(
            'eupassq_type_meta', 
            'Question Type & Level', 
            [$this,'eupassq_metabox_generate'], 
            'eupassq',  
            'side', 
            'high'
        );

    }

    /**
     * Dashboard page callback
     */
    function eupassq_generate_menu()
    {
        
        

        $table_name = $this->dbGb->tablePrefix . 'quiz_list';
        $is_submit = false;

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['quiz_list_text']) 
        ) {
            $this->nc::die_if_invalid( $this->nc::LIST_SUBMIT, 'eupassqnc_list');
            $is_submit = true;
        }

        if ($is_submit) {
            
            $quiz_content = trim(wp_kses_post($_POST['quiz_list_text']));
            $quiz_content = str_replace(PHP_EOL, "", $quiz_content);
            $q_arrr = explode(";", $quiz_content);
            $clean_q_arr = [];
            foreach ($q_arrr as $q) {
                if(strlen($q) > 2)
                {
                    array_push($clean_q_arr, trim($q));
                }
            }
            $cl_jq = json_encode($clean_q_arr);
            
            $this->dbGb->Eupassq_Check_Insert_Replace_Setting_value('bqlist', $cl_jq);

            echo '<div class="notice notice-success is-dismissible"><p>✅ Quiz list saved successfully!</p></div>';
        }

        $quizzes = $this->dbGb->Eupassq_return_Setting_value('bqlist');
        $list_q = '';

        if($quizzes != null)
        {
            $qList = json_decode($quizzes);
            foreach ($qList as $q) {
                $list_q .= $q . ';' . PHP_EOL;
            }
        }

        ?>
        <div class="wrap">
            <h1>EupassQ Dashboard</h1>
            <form method="post">
                <h2>List of Quiz</h2>
                <?php $this->nc->field(EupassQNonce::LIST_SUBMIT, 'eupassqnc_list'); ?>
                <textarea name="quiz_list_text" rows="10" style="width:100%; max-width:800px;"><?php echo esc_textarea($list_q); ?></textarea>
                <br><br>
                <input type="submit" class="button button-primary" value="Save List">
            </form>
        </div>
        <?php
    }



    function eupassq_metabox_generate($post)
    {
        //wp_nonce_field( 'tf_ipf_nonce_global', 'tfIpf_one_once' );
        
        $dbInstance = $this->dbGb->Eupassq_Find_Single_Question_PostId($post->ID);

        $lvl = $typ = null;

        if($dbInstance != null)
        {
            $lvl = $dbInstance->euqlvl;
            $typ = $dbInstance->euqtpe;
        }

        //get from database with post-id as a key search 
        //get level and type of question
        //euqtpe
        //euqlvl

        ?>

        <div>
            <div class="form-group eupassq-box">
                <div class="row">
                    <label class="eupassq-label">EupassQ Question Type</label>
                </div>
                <div class="row eupassq-radios">
                    <div class="row">
                        <input type="radio" id="euqtext" name="euqtpe" value="text" <?php if ( ! empty( $typ ) ) checked( $typ, 'text' ); ?>>
                        <label for="euqtext">Text EupassQ</label>
                    </div>
                    <div class="row">
                        <input type="radio" id="euqaudio" name="euqtpe" value="audio" <?php if ( ! empty( $typ ) ) checked( $typ, 'audio' ); ?>>
                        <label for="euqaudio">Audio EupassQ</label>
                    </div>
                </div>
            </div>

            <div class="form-group eupassq-box">
                <label for="euqlvl" class="eupassq-label">EupassQ Question Level</label>
                <select id="euqlvl" name="euqlvl" class="form-control eupassq-select" required>
                    <option value="0"><?php esc_html_e('Please Select Level', 'eupassq'); ?></option>
                    <option value="A1" <?php selected( $lvl, 'A1' ); ?>>A1</option>
                    <option value="A2" <?php selected( $lvl, 'A2' ); ?>>A2</option>
                    <option value="B1" <?php selected( $lvl, 'B1' ); ?>>B1</option>
                    <option value="B2" <?php selected( $lvl, 'B2' ); ?>>B2</option>
                    <option value="C1" <?php selected( $lvl, 'C1' ); ?>>C1</option>
                    <option value="C2" <?php selected( $lvl, 'C2' ); ?>>C2</option>
                </select>
            </div>
        </div>

        <?php
    }


    /**
     * Validate question data before pubishing
     */
    function eupassq_validate_before_save( $data, $postarr ) {

        if ( $data['post_type'] !== 'eupassq' ) { return $data;}
        if ( $data['post_status'] === 'auto-draft' ) {return $data;}

        $euqtype = isset($_POST['euqtpe']) ? sanitize_key($_POST['euqtpe']) : '';
        $euqlvl  = isset($_POST['euqlvl']) ? sanitize_text_field($_POST['euqlvl']) : '';

        $post_id = isset($postarr['ID']) ? intval($postarr['ID']) : 0;

        $data['post_title'] = $post_id . '-' . ( $euqtype ?: 'unknown' );
        $data['post_name']  = sanitize_title( $data['post_title'] );

        if ( in_array( $data['post_status'], ['publish','pending'], true ) ) {
            if ( empty($euqtype) || empty($euqlvl) || $euqlvl === '0' ) {
                $data['post_status'] = 'draft';

                add_filter( 'redirect_post_location', function( $location ) {
                    return add_query_arg( 'eupassq_error', 1, $location );
                });
            }
        }

        return $data;
    }


    /**
     * Intercept save for database sync
     */

    function eupassq_creation_intercept($post_id, $post) {
    
        if ( $post->post_type !== 'eupassq' ) {return;}
        if (!current_user_can('edit_post', $post_id)) {return;}
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;

        global $pagenow;

        if($pagenow != "post-new.php")
        {
            $dbInstance = $this->dbGb->Eupassq_Find_Single_Question_PostId($post_id);
            $counter = $action = 0;

            $objD = new stdClass(); 
            
            $arrTypLvl = $this->dbGb->Eupassq_Validate_TypeLevel($_POST['euqtpe'], $_POST['euqlvl']);
            $defaultPostNameTitle = $post_id . '-';
            
            if($arrTypLvl[0] == null)
            {
                $defaultPostNameTitle .= 'unknown';
            }else
            {
                $defaultPostNameTitle .= $arrTypLvl[0];
            }
            
            $post->post_title = $post->post_name = $defaultPostNameTitle;
            

            if($arrTypLvl[0] != null && $arrTypLvl[1] != null)
            {
                $objD->euqtpe = $arrTypLvl[0];
                $objD->euqlvl = $arrTypLvl[1];
                $objD->euqpostid = sanitize_key($post_id);
                $objD->euqtitl = $post_id . '-' . sanitize_key($_POST['euqtpe']);
                $objD->euqcontent = wp_kses_post($_POST['post_content']);
                $objD->euqcnt = $counter;

                if($dbInstance != null)
                {
                    $action = 1; 
                    $counter = $dbInstance->euqcnt;
                    $objD->euqid = $dbInstance->euqid;
                }

                $result = $this->dbGb->Eupassq_Insert_Update_Question_Entry($objD, $action);

                if($result === false)
                {
                    //Throw error
                    wp_redirect( add_query_arg( 'eupassq_error', 'db', admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) );
                    exit;
                }else
                {
                    $this->dbGb->Europassq_Delete_Autosave($post_id);
                }
            }else
            {
                //Throw error
                wp_redirect( add_query_arg( 'eupassq_error', 'missing', admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) );
                $this->dbGb->Europassq_Delete_Autosave($post_id);
                exit;
            }
            
        }
        
    }
    

     /**
     * Shortcode: Display Questions
     */
    function eupassq_quiz_shortcode($atts)
    {
        $questions = get_posts([
            'post_type'      => 'eupassq',
            'posts_per_page' => -1,
        ]);

        $questions = array($questions[rand(0, count($questions) - 1)]);

        if (empty($questions)) {
            return '<p>' . __( 'No questions available.', 'eupassq' ) . '</p>';
        }

        ob_start();
        echo '<div class="eupassq-quiz">';
        foreach ($questions as $q) {
            echo '<div class="eupassq-question">';
            echo '<h3>' . esc_html(get_the_title($q->ID)) . '</h3>';
            echo '<p>' . esc_html(wp_strip_all_tags($q->post_content)) . '</p>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

}
