<?php
/**
 * Quiz Template
 */
get_header();

$uuid     = get_query_var('uuid');
$quiz_html = $GLOBALS['eupassq_quiz_html'] ?? '';
$user_info = $GLOBALS['user_info'] ?? '';
$question_pool = $GLOBALS['question_pool'] ?? [];

?>

<div class="wrap">
    <div class="EupassQ-style">
        <div class="container mt-4">
            <div class="row text-center m-2">
                <p> <?php esc_attr_e('procedi', 'eupassq'); ?> </p>
                <!-- <h2>Text and Audio Production Test</h2>  -->
            </div>
            <form id="eupassq_quiz_form" class="EupassQ-form" >
                <input hidden value="<?php echo $user_info ?>" name="user_info" />
                <input hidden value="<?php echo $uuid ?>" name="qsm_unique_id" />
                <?php foreach ($question_pool as $index => $question) : ?>
                    <div class="eupassq-question card mb-4 shadow-sm p-3" 
                        data-index="<?php echo $index; ?>" 
                        data-euqtpe="<?php echo $question['euqtpe']; ?>" 
                        data-euid="<?php echo $question['euqid']; ?>">

                        <div class="card-body">
                            <div class="EupassQ-style question-content mb-4">
                                <?php echo apply_filters('the_content', stripslashes($question['euqcontent'])); ?>
                            </div>

                            <?php if ($question['euqtpe'] == 'text') : ?>
                                <textarea 
                                    name="eupassq_qansw[<?php echo $index; ?>]" 
                                    class="form-control EupassQ-input" 
                                    rows="3"
                                    placeholder="Type your answer here..."></textarea>

                            <?php else : ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                    <button type="button" class="btn btn-primary start-record">🎙 Start Recording</button>
                                    <button type="button" class="btn btn-danger stop-record" disabled>⏹ Stop Recording</button>
                                </div>
                                
                                <div id="error_<?php echo $question['euqid']; ?>" hidden class="recording-message text-danger small mt-2" style="min-height:1.2em;">Hello error</div>

                                <div class="mt-3 reset-btn-div">
                                    <div class="d-flex align-items-center gap-2">
                                        <audio controls class="audio-playback flex-grow-1"></audio>
                                        <span class="audio-status text-success fs-4" hidden>✔</span>
                                    </div>
                                </div>


                                <input type="hidden" 
                                    name="eupassq_qansw[<?php echo $index; ?>]" 
                                    class="audio-data"/>
                            <?php endif; ?>

                            <input type="hidden" name="eupassq_qi[<?php echo $index; ?>]" value="<?php echo $question['euqid']; ?>"/>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-center">
                    <button id="btn btn-success" 
                            onclick="SubmitMyQuiz()" 
                            type="button" 
                            class="btn btn-success EupassQ-submit">
                            Submit Answers
                    </button>
                </div>

                <div id="rq-response" class="mt-3 text-center"></div>
            </form>
        </div>
    </div>
</div>
