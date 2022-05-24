<?php
require_once("functions.php");
$result = mysqli_query(conn_db(),"SELECT id, parent_id,  data  FROM {$table_name}");
$tablica = [];
while ($row = mysqli_fetch_assoc($result)){
    $tablica[] = $row;

}
$indexed_arr = array();
foreach($tablica as $item)
{
    $item['children'] = array();
    $indexed_arr[$item['id']] = $item;
}
$tree = array();
foreach($indexed_arr as $id => $v)
{
    $item = &$indexed_arr[$id];
    if($item['parent_id'] == 0)
    {
        $tree[$id] = &$item;
    }
    elseif(isset($indexed_arr[$item['parent_id']]))
    {
        $indexed_arr[$item['parent_id']]['children'][$id] = &$item;
    }
    else
    {
        $tree['_orphans_'][$id] = &$item;
    }
}
echo '<pre>';
print_r($tree);
echo '</pre>';
close_conn_db(conn_db());
?>