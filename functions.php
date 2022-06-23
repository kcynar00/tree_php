<?php
$table_name = 'struct';
function conn_db() {
    $db_host = "localhost";
    $db_login = "root";
    $db_password = "";
    $db_name = "tree";
    $db_conn = new mysqli ($db_host, $db_login, $db_password);
    mysqli_select_db($db_conn,"tree") or die(mysqli_error($db_conn));
    mysqli_set_charset($db_conn, "utf8");
    if ($db_conn->connect_error)
    die("Connection failed: " . $db_conn->connect_error);
else
    return $db_conn;
}
function close_conn_db($db_conn) {
    $db_conn->close();
}

function deleteNode($conn,$id) {
    $table_name = 'struct';
    global $deleted;

    $query = "SELECT * FROM {$table_name} WHERE parent_id='{$id}';";
    $result = mysqli_query($conn,$query);
    while($row = $result->fetch_assoc()) {
        deleteNode($conn,$row['id']);
    }

    $query = "DELETE FROM {$table_name} WHERE id='{$id}';";
    $result = mysqli_query($conn,$query);
    if($conn->affected_rows > 0) $deleted = 1;
}
