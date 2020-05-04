<?php
    session_start();
    header('Content-Type: application/json');

    
    switch ($_REQUEST['path']){
        case 'progress':
            echo json_encode($_SESSION);
        break;
    }

    // Finalement, on détruit la session.
    session_destroy();
?>