<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode([]); exit; }

// Detail hewan + habitat
$stmt = mysqli_prepare($conn, "
    SELECT
        a.id_animal, a.animal_name, a.species, a.image_url,
        h.habitat_name, h.temperature,
        GROUP_CONCAT(DISTINCT f.foods_name SEPARATOR ', ') AS foods,
        GROUP_CONCAT(DISTINCT pp.feeding_schedule SEPARATOR ', ') AS schedule,
        MAX(CASE WHEN pp.status = 'done' THEN 1 ELSE 0 END) AS status_done
    FROM animals a
    JOIN habitats h ON a.id_habitat = h.id_habitat
    LEFT JOIN memakan m ON a.id_animal = m.id_animal
    LEFT JOIN foods f ON m.id_food = f.id_food
    LEFT JOIN pemberian_pakan pp ON a.id_animal = pp.id_animal
    WHERE a.id_animal = ?
    GROUP BY a.id_animal
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($data) {
    $data['status'] = $data['status_done'] ? 'done' : 'pending';
    echo json_encode($data);
} else {
    echo json_encode([]);
}
?>