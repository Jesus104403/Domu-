<?php
session_start();
header('Content-Type: application/json');
require_once '../conexion.php';

if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode(["success" => false, "message" => "Acceso denegado."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'] ?? null;
    $nombre = $_POST['nombre'] ?? null;
    $email = $_POST['email'] ?? null;
    $nuevo_rol = $_POST['rol_id'] ?? null;

    if (!$id_usuario || !$nuevo_rol || !$nombre || !$email) {
        echo json_encode(["success" => false, "message" => "Faltan datos en el formulario."]);
        exit;
    }

    try {
        // Actualizamos nombre, email y rol
        $sql = "UPDATE usuarios SET nombre = :nom, email = :em, rol_id = :rol WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nom', $nombre);
        $stmt->bindParam(':em', $email);
        $stmt->bindParam(':rol', $nuevo_rol);
        $stmt->bindParam(':id', $id_usuario);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Usuario actualizado correctamente."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al actualizar."]);
        }
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error SQL: " . $e->getMessage()]);
    }
}
?>