<?php
/**
 * Template Name: Results Processed (Disclaimer)
 * Description: Landing page shown when results have already been processed and emailed.
 */

get_header();
?>
<div class="EupassQ-style">
    <div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
        <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5 text-center" style="max-width: 700px;">
            
            <div class="mb-4">
                <i class="bi bi-envelope-check display-3 text-success"></i>
            </div>

            <h1 class="fw-bold mb-3">
                <?php esc_html_e( 'results-processed-title', 'eupassq' ); ?>
            </h1>
            
            <p class="lead text-muted mb-4">
                <?php esc_html_e( 'results-processed-lead', 'eupassq' ); ?>
            </p>

            <hr class="my-4">

            <p class="text-secondary mb-4">
                <?php esc_html_e( 'results-processed-followup', 'eupassq' ); ?>
            </p>

            <a href="<?php echo esc_url( home_url() ); ?>" class="btn btn-primary btn-lg rounded-pill px-4">
                <?php esc_html_e( 'results-processed-button', 'eupassq' ); ?>
            </a>

        </div>
    </div>
</div>

<?php
get_footer();
?>
