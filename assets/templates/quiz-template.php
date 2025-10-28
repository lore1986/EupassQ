<?php
/**
 * Quiz Template
 */
get_header();

$uuid          = get_query_var('uuid');
$quiz_html     = $GLOBALS['eupassq_quiz_html'] ?? '';
$user_info     = $GLOBALS['user_info'] ?? '';
$question_pool = $GLOBALS['question_pool'] ?? [];

// Separate text and audio questions
$text_questions  = array_filter($question_pool, fn($q) => $q['euqtpe'] === 'text');
$audio_questions = array_filter($question_pool, fn($q) => $q['euqtpe'] !== 'text');
?>

<div class="wrap">
    <div class="EupassQ-style">
        <div class="container mt-4">
            <!--  -->

            <form id="eupassq_quiz_form" class="EupassQ-form">
                <input type="hidden" value="<?php echo esc_attr($user_info); ?>" name="user_info" />
                <input type="hidden" value="<?php echo esc_attr($uuid); ?>" name="qsm_unique_id" />

                <!-- ============================= -->
                <!-- TEXT QUESTIONS SECTION -->
                <!-- ============================= -->
                <?php if (!empty($text_questions)) : ?>
                    <h3 class="mt-5 mb-3 custom-border-bottom pb-2">
                        📝 <?php esc_attr_e('text-question-heading', 'eupassq'); ?>
                    </h3>
                    <?php foreach ($text_questions as $index => $question) : ?>
                        <div class="eupassq-question card mb-4 shadow-sm p-3"
                            data-index="<?php echo $index; ?>"
                            data-euqtpe="<?php echo esc_attr($question['euqtpe']); ?>"
                            data-euid="<?php echo esc_attr($question['euqid']); ?>">

                            <div class="card-body">
                                <div class="EupassQ-style question-content mb-4">
                                    <?php echo apply_filters('the_content', stripslashes($question['euqcontent'])); ?>
                                </div>

                                <textarea 
                                    name="eupassq_qansw[<?php echo $index; ?>]"
                                    class="form-control EupassQ-input"
                                    rows="8"
                                    placeholder="<?php esc_attr_e('Type your answer here...', 'eupassq'); ?>"></textarea>

                                <input type="hidden" 
                                    name="eupassq_qi[<?php echo $index; ?>]" 
                                    value="<?php echo esc_attr($question['euqid']); ?>" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>


                <!-- ============================= -->
                <!-- AUDIO QUESTIONS SECTION -->
                <!-- ============================= -->
                <?php if (!empty($audio_questions)) : ?>
                    <h3 class="mt-5 mb-3 custom-border-bottom pb-2">
                        🎤 <?php esc_attr_e('audio-question-heading', 'eupassq'); ?>
                    </h3>
                    <?php foreach ($audio_questions as $index => $question) : ?>
                        <div class="eupassq-question card mb-4 shadow-sm p-3"
                            data-index="<?php echo $index; ?>"
                            data-euqtpe="<?php echo esc_attr($question['euqtpe']); ?>"
                            data-euid="<?php echo esc_attr($question['euqid']); ?>">

                            <div class="card-body">
                                <div class="EupassQ-style question-content mb-4">
                                    <?php echo apply_filters('the_content', stripslashes($question['euqcontent'])); ?>
                                </div>

                                <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                    <button type="button" class="btn btn-primary start-record">
                                        🎙 <?php esc_attr_e('start-recording', 'eupassq'); ?>
                                    </button>
                                    <button type="button" class="btn btn-danger stop-record" disabled>
                                        ⏹ <?php esc_attr_e('stop-recording', 'eupassq'); ?>
                                    </button>
                                    <span class="timer-display"></span>
                                </div>

                                <div id="error_<?php echo esc_attr($question['euqid']); ?>" 
                                    hidden class="recording-message text-danger small mt-2" 
                                    style="min-height:1.2em;">
                                </div>

                                <div class="mt-3 reset-btn-div">
                                    <div class="d-flex align-items-center gap-2">
                                        <audio controls hidden class="audio-playback flex-grow-1"></audio>
                                        <span class="audio-status text-success fs-4" hidden>✔</span>
                                    </div>
                                </div>

                                <input type="hidden" 
                                    name="eupassq_qansw[<?php echo $index; ?>]" 
                                    class="audio-data" />

                                <input type="hidden" 
                                    name="eupassq_qi[<?php echo $index; ?>]" 
                                    value="<?php echo esc_attr($question['euqid']); ?>" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>


                <!-- ============================= -->
                <!-- SUBMIT BUTTON -->
                <!-- ============================= -->
                <div class="text-center mt-5">
                    <button id="btn btn-success" 
                            onclick="SubmitMyQuiz()" 
                            type="button" 
                            class="btn btn-success EupassQ-submit">
                        <?php esc_attr_e('submit-quiz', 'eupassq'); ?>
                    </button>
                </div>

                <div id="rq-response" class="mt-3 text-center"></div>
            </form>
        </div>
    </div>
</div>

<script>

window.addEventListener('pageshow', function (event) {

    const isBackForward = event.persisted ||
    (performance.getEntriesByType('navigation')[0]?.type === 'back_forward');

    if (isBackForward) {
        hideSpinner();

        const form = document.getElementById('eupassq_quiz_form');
        if (form) {
        [...form.elements].forEach(el => el.disabled = false);
        }
    }
});

document.addEventListener('visibilitychange', function () {
  if (!document.hidden) {
    const nav = performance.getEntriesByType('navigation')[0];
    if (nav && nav.type === 'back_forward') hideSpinner();
  }
});

</script>
