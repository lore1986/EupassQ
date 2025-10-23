<?php 
/* Template Name: Results */

include_once(dirname(plugin_dir_path(__DIR__)) . '/php_classes/EupassQuizHandler.php');
use EupassQ\PhpClasses\EupassQuizHandler;
$handler = new EupassQuizHandler();

get_header(); 

?>

<h1>Hello</h1>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php
        $result_code = get_query_var( 'results_id' );
        $qsm_id = get_query_var('qsmres');
       
        if ( $result_code ) {

            $results = $handler->EupassQ_Calculate_Get_User_Results($result_code, $qsm_id);

            $handler->EupassQ_Delete_User_Results($result_code);

            $user_score = $results['user_score'];
            $user_percentage = $results['user_percentage'];
            $qsm_results = $results['qsm'];
            $text_results = $results['eupassQ_text'];
            $audio_results = $results['eupassQ_audio'];

            ?>
            <div class="container EupassQ-style my-5">
                <div class="row">
                    <div class="col-lg-10 mx-auto">

     

                        <div class="card p-4 shadow-sm mb-5">
                            <h2 class="text-center">Your Results Summary</h2>
                            <hr>
                            <div class="row text-center my-3">
                                <div class="col-md-6 border-end">
                                    <h3 class="h5 text-muted">Overall Score</h3>
                                    <p class="display-5 fw-bold text-primary"><?php echo esc_html( $user_score ); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h3 class="h5 text-muted">Percentage</h3>
                                    <p class="display-5 fw-bold text-primary"><?php echo esc_html( $user_percentage ); ?>%</p>
                                </div>
                            </div>
                            <?php
                                $progress_class = 'bg-success'; // Assume pass
                                if ( $user_percentage < 50 ) {
                                    $progress_class = 'bg-danger';
                                } elseif ( $user_percentage < 60 ) {
                                    $progress_class = 'bg-warning';
                                }
                            ?>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar <?php echo $progress_class; ?> progress-bar-striped" 
                                    role="progressbar" 
                                    style="width: <?php echo esc_attr( $user_percentage ); ?>%;" 
                                    aria-valuenow="<?php echo esc_attr( $user_percentage ); ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    <?php echo esc_html( $user_percentage ); ?>%
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h2 class="mb-3">Multiple Choice Questions</h2>
                            <p class="lead">
                                Your Score: <strong><?php echo esc_html( $qsm_results['partial'] ); ?> / <?php echo esc_html( $qsm_results['total'] ); ?></strong>
                            </p>
                            <div class="table-responsive shadow-sm rounded">
                                <table class="table table-striped table-hover align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th scope="col" style="width: 5%;">#</th>
                                            <th scope="col" style="width: 55%;">Question</th>
                                            <th scope="col" style="width: 30%;">Your Answer</th>
                                            <th scope="col" style="width: 10%;" class="text-center">Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php // Check if 'qanda' exists and is an array before looping ?>
                                        <?php if ( ! empty( $qsm_results['qanda'] ) && is_array( $qsm_results['qanda'] ) ) : ?>
                                            <?php foreach ( $qsm_results['qanda'] as $index => $item ) : ?>
                                                <?php $is_correct = ( isset($item['iscorrect']) && $item['iscorrect'] === 'correct' ); ?>
                                                <tr class="<?php echo $is_correct ? 'table-success-light' : 'table-danger-light'; ?>">
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    
                                                    <td><?php echo wp_kses_post( html_entity_decode( $item['question_text'] ) ); ?></td>
                                                    
                                                    <td><em><?php echo esc_html( $item['question_answer'] ); ?></em></td>
                                                    
                                                    <td class="text-center fs-4 fw-bold">
                                                        <?php if ( $is_correct ) : ?>
                                                            <span class="text-success" title="Correct">✓</span>
                                                        <?php else : ?>
                                                            <span class="text-danger" title="Incorrect">✗</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        
                        <div class="mb-5">
                            <h2 class="mb-3">Written Task</h2>
                            <p class="lead">
                                Your Score: <strong><?php echo esc_html( $text_results['partial'] ); ?></strong>
                            </p>
                            <?php // Loop through $text_results['qanda'] ?>
                            <?php if ( ! empty( $text_results['qanda'] ) && is_array( $text_results['qanda'] ) ) : ?>
                                <?php foreach ( $text_results['qanda'] as $text_item ) : ?>
                                    <div class="card shadow-sm mb-4"> 
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>Question:</strong>
                                                <span class="badge bg-secondary fs-6">Score: <?php echo esc_html( $text_item['punteggio_totale'] ); ?></span>
                                            </div>
                                            <?php echo apply_filters('the_content', stripslashes($text_item['question_text'])); ?>
                                        </div>
                                        <div class="row g-0">
                                            <div class="col-md-5 border-end">
                                                <div class="card-body">
                                                    <h5 class="card-title">Score Breakdown</h5>
                                                    <ul class="list-group list-group-flush">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Efficacia Comunicativa
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $text_item['efficacia_comunicativa'] ); ?></span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Correttezza Morfosintattica
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $text_item['correttezza_morfosintattica'] ); ?></span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Lessico
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $text_item['lessico'] ); ?></span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Ortografia
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $text_item['ortografia'] ); ?></span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <div class="card-body h-100" style="background-color: #fdfdfd;">
                                                    <h5 class="card-title">Feedback</h5>
                                                    <p class="card-text text-muted"><?php echo esc_html( $text_item['feedback'] ); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        
                        <div class="mb-5">
                            <h2 class="mb-3">Oral Task</h2>
                            <p class="lead">
                                Your Score: <strong><?php echo esc_html( $audio_results['partial'] ); ?></strong>
                            </p>
                            <?php // Loop through $audio_results['qanda'] ?>
                            <?php if ( ! empty( $audio_results['qanda'] ) && is_array( $audio_results['qanda'] ) ) : ?>
                                <?php foreach ( $audio_results['qanda'] as $audio_item ) : ?>
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-header bg-light">
                                            <strong class="mb-2 d-block">Question:</strong>
                                            <?php echo apply_filters('the_content', stripslashes($audio_item['question_text'])); ?>
                                        </div>
                                        <div class="row g-0">
                                            <div class="col-md-5 border-end">
                                                <div class="card-body">
                                                    <h5 class="card-title">Score Breakdown</h5>
                                                    <ul class="list-group list-group-flush">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Efficacia Comunicativa
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $audio_item['efficacia_comunicativa'] ); ?></span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Correttezza Morfosintattica
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $audio_item['correttezza_morfosintattica'] ); ?></span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Lessico
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $audio_item['lessico'] ); ?></span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                            Pronuncia e Intonazione
                                                            <span class="badge bg-primary rounded-pill"><?php echo esc_html( $audio_item['pronuncia_intonazione'] ); ?></span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <div class="card-body h-100" style="background-color: #fdfdfd;">
                                                    <h5 class="card-title">Feedback</h5>
                                                    <p class="card-text text-muted"><?php echo esc_html( $audio_item['feedback'] ); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div> <?php
            

        } else {
            // Handle the case where someone visits /results/ without a code
            echo '<h1 class="text-center my-5">Please provide a result code.</h1>';
        }
        ?>

        <?php /* if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <h1 class="display-4 text-center mb-4"><?php the_title(); ?></h1>
        <?php endwhile; endif; */?> 

    </main>
</div>
<?php get_footer();