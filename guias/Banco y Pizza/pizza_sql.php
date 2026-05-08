<?php
// 1. CONFIGURACIÓN Y CONEXIÓN AUTOMÁTICA
$host = 'localhost';
$db   = 'pizzeria_db';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

try {
    // Conectamos primero al servidor para asegurar que la base de datos existe
    $pdo_init = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
    $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS $db");
    
    // Conexión principal a la base de datos
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass,);

    // Crear la tabla automáticamente si no existe
    $sql_tabla = "CREATE TABLE IF NOT EXISTS pedidos_pizza (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente VARCHAR(100) NOT NULL,
        pizza VARCHAR(50) NOT NULL,
        tamano VARCHAR(20) NOT NULL,
        cantidad INT NOT NULL DEFAULT 1,
        estado VARCHAR(50) DEFAULT 'Pendiente',
        fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_tabla);

} catch (PDOException $e) {
    die("Error crítico de base de datos: ". $e->getMessage());
}

// 2. LÓGICA DE CONTROL (CRUD)
$modo = isset($_GET['modo'])? $_GET['modo'] : 'tienda';

// CREAR: Insertar pedido
if (isset($_POST['action']) && $_POST['action'] == 'nuevo_pedido') {
    $stmt = $pdo->prepare("INSERT INTO pedidos_pizza (cliente, pizza, tamano, cantidad) VALUES (?,?,?,?)");
    $stmt->execute(, $_POST['pizza'], $_POST['tamano'], $_POST['cantidad']]);
    header("Location: pizza_sql.php?modo=tienda&status=success");
    exit;
}

// ACTUALIZAR: Cambiar estado
if (isset($_POST['action']) && $_POST['action'] == 'actualizar_estado') {
    $stmt = $pdo->prepare("UPDATE pedidos_pizza SET estado =? WHERE id =?");
    $stmt->execute(, $_POST['pedido_id']]);
    header("Location: pizza_sql.php?modo=empresa&status=updated");
    exit;
}

// ELIMINAR: Borrar registro
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM pedidos_pizza WHERE id =?");
    $stmt->execute(]);
    header("Location: pizza_sql.php?modo=empresa&status=deleted");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pizzería SQL - Linux</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
       .nav { background: #333; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
       .nav a { color: white; text-decoration: none; margin-right: 15px; font-weight: bold; }
       .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #eee; }
       .btn-delete { color: #d9534f; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="?modo=tienda">🛒 Realizar Pedido</a>
        <a href="?modo=empresa">💼 Gestión Empresa</a>
    </div>

    <div class="container">
        <?php if ($modo == 'tienda'):?>
            <h1>🍕 Nueva Orden de Pizza</h1>
            <?php if(isset($_GET['status']) && $_GET['status'] == 'success') echo "<p style='color:green'><b>¡Pedido recibido!</b></p>";?>
            <form method="POST">
                <input type="hidden" name="action" value="nuevo_pedido">
                <p>Nombre:<br><input type="text" name="cliente" required style="width:100%"></p>
                <p>Pizza:<br>
                <select name="pizza" style="width:100%">
                    <option value="Margarita">Margarita</option>
                    <option value="Pepperoni">Pepperoni</option>
                    <option value="Hawaiana">Hawaiana</option>
                </select></p>
                <p>Tamaño:<br>
                <input type="radio" name="tamano" value="Pequena"> P 
                <input type="radio" name="tamano" value="Mediana" checked> M 
                <input type="radio" name="tamano" value="Grande"> G</p>
                <p>Cantidad:<br><input type="number" name="cantidad" value="1" min="1" style="width:100%"></p>
                <button type="submit" style="background:#5cb85c; color:white; padding:10px; border:none; width:100%; cursor:pointer;">Confirmar Pedido</button>
            </form>

        <?php else:?>
            <h1>📊 Panel de Administración</h1>
            <table>
                <tr><th>ID</th><th>Cliente</th><th>Pedido</th><th>Estado</th><th>Acciones</th></tr>
                <?php
                $stmt = $pdo->query("SELECT * FROM pedidos_pizza ORDER BY id DESC");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                        <td>#{$row['id']}</td>
                        <td>{$row['cliente']}</td>
                        <td>{$row['cantidad']}x {$row['pizza']} ({$row['tamano']})</td>
                        <td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='action' value='actualizar_estado'>
                                <input type='hidden' name='pedido_id' value='{$row['id']}'>
                                <select name='nuevo_estado' onchange='this.form.submit()'>
                                    <option value='Pendiente' ".($row['estado']=='Pendiente'?'selected':'').">Pendiente</option>
                                    <option value='Preparando' ".($row['estado']=='Preparando'?'selected':'').">Preparando</option>
                                    <option value='Entregado' ".($row['estado']=='Entregado'?'selected':'').">Entregado</option>
                                </select>
                            </form>
                        </td>
                        <td><a href='?modo=empresa&delete_id={$row['id']}' class='btn-delete' onclick='return confirm(\"¿Eliminar?\")'>Eliminar</a></td>
                    </tr>";
                }
               ?>
            </table>
        <?php endif;?>
    </div>
</body>
</html>