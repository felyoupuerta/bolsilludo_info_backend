<?php
function displayFlashMessages() {
    if (isset($_SESSION['flash_success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo $_SESSION['flash_success'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['flash_success']);
    }
    
    if (isset($_SESSION['flash_error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo $_SESSION['flash_error'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['flash_error']);
    }
    
    if (isset($_SESSION['flash_info'])) {
        echo '<div class="alert alert-info alert-dismissible fade show" role="alert">';
        echo $_SESSION['flash_info'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['flash_info']);
    }
}
?> 
