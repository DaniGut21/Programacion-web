<?php
/**
 * SISTEMA DE GESTIÓN DE PIZZERÍA CON SQL (PDO)
 * Archivo: pizza_sql.php
 */

// 1. CONFIGURACIÓN DE CONEXIÓN
$host = 'localhost';
$db   = 'pizzeria_db';
$user = 'root';
$pass = ''; // Cambiar si tienes contraseña en MySQL
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options =;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error de conexión: ". $e->getMessage());
}

// 2. LÓGICA DE NEGOCIO (CRUD)
$modo = isset($_GET['modo'])? $_GET['modo'] : 'tienda';

// CREAR (Insertar pedido desde la Tienda) [6, 1, 7]
if (isset($_POST['action']) && $_POST['action'] == 'nuevo_pedido') {
    $sql = "INSERT INTO pedidos_pizza (cliente, pizza, tamano, cantidad) VALUES (?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(, $_POST['pizza'], $_POST['tamano'], $_POST['cantidad']]);
    header("Location: pizza_sql.php?modo=tienda&status=success");
    exit;
}

// ACTUALIZAR (Cambiar estado desde la Empresa) [6, 8, 9]
if (isset($_POST['action']) && $_POST['action'] == 'actualizar_estado') {
    $sql = "UPDATE pedidos_pizza SET estado =? WHERE id =?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(, $_POST['pedido_id']]);
    header("Location: pizza_sql.php?modo=empresa&status=updated");
    exit;
}

// ELIMINAR (Borrar registro desde la Empresa) [6, 10]
if (isset($_GET['delete_id'])) {
    $sql = "DELETE FROM pedidos_pizza WHERE id =?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(]);
    header("Location: pizza_sql.php?modo=empresa&status=deleted");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pizzería SQL - Gestión de Pedidos</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fdfdfd; padding: 20px; }
       .nav { background: #333; padding: 15px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 10px; }
       .nav a { color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; }
       .btn-tienda { background: #e67e22; }
       .btn-empresa { background: #2980b9; }
       .container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
       .status-tag { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; }
       .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
       .success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

    <div class="nav">
        <a href="?modo=tienda" class="btn-tienda">🛒 Modo Vista Tienda</a>
        <a href="?modo=empresa" class="btn-empresa">💼 Modo Vista Empresa</a>
    </div>

    <div class="container">
        <?php if ($modo == 'tienda'): // VISTA TIENDA?>
            <h1>🍕 Realiza tu Pedido</h1>
            <?php if(isset($_GET['status']) && $_GET['status'] == 'success') echo "<div class='msg success'>¡Tu pizza está en camino!</div>";?>
            
            <form method="POST">
                <input type="hidden" name="action" value="nuevo_pedido">
                <p><label>Tu Nombre:</label><br>
                <input type="text" name="cliente" required style="width:100%; padding:8px;"></p>
                
                <p><label>Elige tu Pizza:</label><br>
                <select name="pizza" required style="width:100%; padding:8px;">
                    <option value="Margarita">Margarita</option>
                    <option value="Pepperoni">Pepperoni</option>
                    <option value="Hawaiana">Hawaiana</option>
                    <option value="Cuatro Quesos">Cuatro Quesos</option>
                </select></p>

                <p><label>Tamaño:</label><br>
                <input type="radio" name="tamano" value="Pequena"> Pequeña 
                <input type="radio" name="tamano" value="Mediana" checked> Mediana 
                <input type="radio" name="tamano" value="Grande"> Grande</p>

                <p><label>Cantidad:</label><br>
                <input type="number" name="cantidad" value="1" min="1" style="width:100%; padding:8px;"></p>
                
                <button type="submit" style="background:#27ae60; color:white; padding:12px 25px; border:none; border-radius:5px; cursor:pointer; width:100%; font-size:1.1em;">Confirmar Pedido</button>
            </form>

        <?php else: // VISTA EMPRESA?[4, 10, 11, 5]>
            <h1> 📊 Gestión de Pedidos (Empresa) </h1>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Detalle</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // LEER (Seleccionar todos los pedidos)
                    $stmt = $pdo->query("SELECT * FROM pedidos_pizza ORDER BY fecha_pedido DESC");
                    while ($row = $stmt->fetch()) {
                        echo "<tr>";
                        echo "<td>#{$row['id']}</td>";
                        echo "<td>{$row['cliente']}</td>";
                        echo "<td>{$row['cantidad']}x {$row['pizza']} ({$row['tamano']})</td>";
                        echo "<td>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='action' value='actualizar_estado'>
                                    <input type='hidden' name='pedido_id' value='{$row['id']}'>
                                    <select name='nuevo_estado' onchange='this.form.submit()'>
                                        <option value='Pendiente' ".($row['estado']=='Pendiente'?'selected':'').">Pendiente</option>
                                        <option value='Preparando' ".($row['estado']=='Preparando'?'selected':'').">Preparando</option>
                                        <option value='Enviado' ".($row['estado']=='Enviado'?'selected':'').">Enviado</option>
                                        <option value='Entregado' ".($row['estado']=='Entregado'?'selected':'').">Entregado</option>
                                    </select>
                                </form>
                              </td>";
                        echo "<td>
                                <a href='?modo=empresa&delete_id={$row['id']}' onclick='return confirm(\"¿Deseas eliminar este registro?\")' style='color:#c0392b; font-weight:bold; text-decoration:none;'>❌ Eliminar</a>
                              </td>";
                        echo "</tr>";
                    }
                   ?>
                </tbody>
            </table>
        <?php endif;?>
    </div>

</body>
</html>