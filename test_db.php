<?php
require_once 'db_connect.php';

if ($conn && !$conn->connect_error) {
    echo "Connessione al DB riuscita!";
} else {
    echo "Errore di connessione: " . $conn->connect_error;
}
