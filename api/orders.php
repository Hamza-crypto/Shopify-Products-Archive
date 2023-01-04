<?php

require_once __DIR__.'/includes/init.php';
global $con;

$table = 'orders';

$query = sprintf('SELECT * FROM %s', $table);

if (isset($_GET['id'])) {
    $query .= sprintf(' WHERE id=%s', $_GET['id']);
}

if (isset($_GET['orderDesc'])) {
    $query .= sprintf(' ORDER BY %s DESC', $_GET['orderDesc']);
}

if (isset($_GET['orderAsc'])) {
    $query .= sprintf(' ORDER BY %s ASC', $_GET['orderAsc']);
}

$page = $_GET['page'] ?? 1;
$per_page = $_GET['per_page'] ?? 100;
$offset = ($page - 1) * $per_page;
$number_of_result = mysqli_fetch_array(mysqli_query($con, sprintf('SELECT COUNT(1) FROM %s', $table)))[0];
$number_of_page = ceil ($number_of_result / $per_page);

$query .= sprintf(' LIMIT %s, %s', $offset, $per_page);

$query = mysqli_query($con, $query);

$data = [];
while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

if (!isset($_GET['id'])) {
    $data = [
        'meta' => [
            'total' => $number_of_result,
            'per_page' => $per_page,
            'current_page' => $page,
            'pages' => $number_of_page
        ],
        'data' => $data
    ];
}

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);
echo json_encode($data);
exit();
