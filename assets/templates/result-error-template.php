<?php
/**
 * Template Name: Quiz Processing Error
 * Description: Landing page shown when a database or processing error occurs during quiz evaluation.
 */

get_header();
?>

<div class="container min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
  <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5 text-center" style="max-width: 700px;">

    <div class="mb-4">
      <i class="bi bi-exclamation-triangle-fill display-3 text-warning"></i>
    </div>

    <h1 class="fw-bold mb-3 text-danger">We Encountered a Problem</h1>
    
    <p class="lead text-muted mb-4">
      Unfortunately, an unexpected error occurred while processing your quiz results.  
      This may be due to a temporary database issue or a connection problem.
    </p>

    <hr class="my-4">

    <p class="text-secondary mb-4">
      Our team has been notified and will review the issue.  
      You can try refreshing this page in a few minutes or contact support for assistance.
    </p>

    <div class="d-flex justify-content-center gap-3">
      <a href="<?php echo home_url(); ?>" class="btn btn-outline-secondary rounded-pill px-4">
        Back to Home
      </a>
      <a href="mailto:it.support@europassnetwork.eu" class="btn btn-danger rounded-pill px-4">
        Contact Support
      </a>
    </div>

  </div>
</div>

<?php
get_footer();
?>
