<?php
session_start();
<<<<<<< HEAD
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
=======
require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol_id = $_POST['rol_id'];

    try {
        // SQL dinámico: Solo actualizamos password si el admin escribió algo
        $sql = "UPDATE usuarios SET nombre = :nom, email = :em, rol_id = :rol";
        if (!empty($_POST['password'])) {
            $pass_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password_hash = :pass";
        }
        $sql .= " WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nom', $nombre);
        $stmt->bindParam(':em', $email);
        $stmt->bindParam(':rol', $rol_id);
        $stmt->bindParam(':id', $id);
        if (!empty($_POST['password'])) $stmt->bindParam(':pass', $pass_hash);

        $stmt->execute();
        header("Location: ../vistas/admin_dashboard.php?mensaje=editado");
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
>>>>>>> 34771b1bf1d19a94915ec6fe3529ce3f1fb09086
