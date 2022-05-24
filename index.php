<?php
ini_set('error_reporting', 'E_NONE');
setcookie("id", $_POST["id"], 0);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>DRZEWO</title>
    <link href="styles/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php
require_once("functions.php");
// Dodawanie nowego węzła

if (isset($_POST["form_name"]) && $_COOKIE["id"]==$_POST["id"]) {
    $msg = "Nie przeładujesz tego samego formularza:-)";
}
else{
if ($_SERVER['REQUEST_METHOD'] == 'POST' & $_POST['form_name'] == 'new_node') {
    if (isset($_POST['data']) && isset($_POST['new_parent'])) {

        $parent = filter_var($_POST['new_parent'],FILTER_SANITIZE_NUMBER_INT);
        $data = filter_var($_POST['data'], FILTER_SANITIZE_STRING);

        $conn = conn_db();

// Wstawia do bazy danych rekord identyfikujący nowy węzeł.

        $query = "INSERT INTO {$table_name} (data,parent_id) VALUES ('{$data}', '{$parent}')";
        $result = mysqli_query($conn, $query);

        if (mysqli_affected_rows($conn) > 0) {

            $msg = "Węzeł został dodany";
        } else {
            $msg = "Wystąpił błąd podczas dodawania węzła";
        }


    } else {
        $msg = "Nie wybrano nazwy lub rodzica węzła";
    }

}
}

// Edytowanie węzłów
if (isset($_POST["form_name"]) && $_COOKIE["id"]==$_POST["id"]) {
    $msg = "Nie przeładujesz tego samego formularza:-)";
}
else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST["form_name"] == 'edit_node') {
        if (isset($_POST['data']) && isset($_POST['id_node']) !== null) {
            $id_node = filter_var($_POST['id_node'], FILTER_SANITIZE_NUMBER_INT);
            $new_data = filter_var($_POST['data'],FILTER_SANITIZE_STRING);
            $conn = conn_db();
            $query = "UPDATE  {$table_name} SET data = '${new_data}' WHERE id = '{$id_node}';";
            $result = mysqli_query($conn, $query);
            if (mysqli_affected_rows($conn) > 0) {
                $msg = "Węzeł został edytowany";
            } else {
                $msg = "Wystąpił błąd podczas edytowania węzła";
            }
        } else {
            $msg = "Nie wybrano nazwy lub rodzica węzła";
        }

    }
}
// Przenoszenie węzła (zamiana rodzica)

if (isset($_POST["form_name"]) && $_COOKIE["id"]==$_POST["id"]) {
    $msg = "Nie przeładujesz tego samego formularza:-)";
}
else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['form_name'] == 'change_parent') {

        if (isset($_POST['id_node']) && isset($_POST['new_parent']) && $_POST['id_node'] != null) {


            $id_new_parent = filter_var($_POST['new_parent'],FILTER_SANITIZE_NUMBER_INT);
            $id_node = filter_var($_POST['id_node'],FILTER_SANITIZE_NUMBER_INT);

            if ($id_node == 1) {
                $msg = "Nie można przenieść korzenia do nowego węzła";
            } else {

                $conn = conn_db();

                $query = "SELECT id, parent_id FROM {$table_name} WHERE id = '{$id_node}';";

                $result = mysqli_query($conn, $query);


                if ($result->num_rows > 0) {

                    while ($row = $result->fetch_assoc()) {

                        $id_parent_node = $row['parent_id'];
                        if ($id_node == $id_new_parent) {
                            $msg = "Wybrano ten sam węzeł";
                        } elseif ($id_parent_node == $id_new_parent) {

                            $msg = "Węzeł jest już dzieckiem tego rodzica";

                        } else {


// Sprawdza czy węzeł nie jest przenoszony do niższego poziomu w tej samej gałęzi

                            $query = "SELECT GROUP_CONCAT(lv SEPARATOR ',') AS potomkowie FROM (SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',')" .
                                " FROM {$table_name} WHERE parent_id IN (@pv)) AS lv FROM {$table_name}" .
                                " JOIN (SELECT @pv:={$id_node})tmp WHERE parent_id IN (@pv)) a;";

                            $result = mysqli_query($conn, $query);
                            if ($result->num_rows > 0) {


                                $descendants = $row['descendants'];

                                $descendants_arrow = explode(",", $descendants);

                                if (in_array($id_new_parent, $descendants_arrow)) {
                                    $msg = "Nie można przenieść węzła do niższego poziomu w tej samej gałęzi";
                                } else {

                                    // Zamienia w bazie danych wartość pola 'parent_id' wybranego węzła na ID nowego rodzica.

                                    $query = "UPDATE {$table_name} SET parent_id = '{$id_new_parent}' WHERE id = '{$id_node}';";

                                    $result = mysqli_query($conn, $query);
                                    if (mysqli_affected_rows($conn) > 0) {
                                        $msg = "Węzeł został przeniesiony";
                                    } else {
                                        $msg = "Wystąpił błąd podczas przenoszenia węzła";

                                    }

                                }


                            } else {


// Zamienia w bazie danych wartość pola 'parent_id' wybranego węzła na ID nowego rodzica.

                                $query = "UPDATE {$table_name} SET parent_id = '{$id_new_parent}' WHERE id = '{$id_node}';";

                                $result = mysqli_query($conn, $query);
                                if (mysqli_affected_rows($conn) > 0) {
                                    $msg = "Węzeł został przeniesiony";
                                } else {
                                    $msg = "Wystąpił błąd podczas przenoszenia węzła";

                                }

                            }


                            $result = mysqli_query($conn, $query);


                        }

                        break;
                    }
                } else {

                    $msg = "Wystąpił błąd podczas przenoszenia węzła";
                }

            }


        } else {
            $msg = "Nie wybrano węzła lub nowego rodzica";

        }
    }
}
// Usuwanie węzła oraz jego potomków

if (isset($_POST["form_name"]) && $_COOKIE["id"]==$_POST["id"]) {
    $msg = "Nie przeładujesz tego samego formularza:-)";
}
else
{
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['form_name'] == 'delete_node') {


    if (isset($_POST['id_node']) && $_POST['id_node'] != null) {


        $id_wezla = filter_var($_POST['id_node'],FILTER_SANITIZE_NUMBER_INT);


        $deleted = 0;
        $conn = conn_db();

        deleteNode($conn, $id_wezla);
        if ($deleted != 0) {

            $msg = "Węzeł został usunięty";
        } else {
            $msg = "Wystąpił błąd podczas usuwania węzła";

        }


    } else {
        $msg = "Nie wybrano żadnego węzła";
    }

}
}
close_conn_db(conn_db());



?>
<div class ="UI">
<form action="index.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?=uniqid()?>" />
    TYP OPERACJI <select name="form_name">
        <option value="new_node">DODAJ WĘZEŁ</option>
        <option value="edit_node">EDYTUJ WĘZEŁ</option>
        <option value="change_parent">PRZENIEŚ WĘZEŁ</option>
        <option value="delete_node">USUŃ WĘZEŁ</option>
    </select>
    ID WEZLA: <input type="number" name="id_node">
    NAZWA/NOWA NAZWA WĘZŁA: <input type="text" name="data">
    ID RODZIC/NOWEGO RODZICA: <input type="number" name="new_parent">
    <button type="submit">POTWIERDŹ</button>
</form>
</div>
<div class="alert">
    <?php
    echo $msg;
    ?>
</div>
<div class="container">
    <iframe class="responsive-iframe" src="tree.php"></iframe><br>
</div>

</body>
</html>