<?php
session_start();

// ====================== INTERFAZ ======================
interface CuentaInterface {
    public function depositar(float $cantidad): void;
    public function retirar(float $cantidad): bool;
    public function consultarSaldo(): float;
}

// ====================== CLASE BASE ======================
class Cuenta implements CuentaInterface {
    protected int $numeroCuenta;
    protected string $nombreCliente;
    protected float $saldo;

    public function __construct(int $numeroCuenta, string $nombreCliente, float $saldoInicial = 0.0) {
        $this->numeroCuenta = $numeroCuenta;
        $this->nombreCliente = $nombreCliente;
        $this->saldo = $saldoInicial;
    }

    public function depositar(float $cantidad): void {
        if ($cantidad > 0) {
            $this->saldo += $cantidad;
        }
    }

    public function retirar(float $cantidad): bool {
        if ($cantidad > 0 && $cantidad <= $this->saldo) {
            $this->saldo -= $cantidad;
            return true;
        }
        return false;
    }

    public function consultarSaldo(): float {
        return $this->saldo;
    }

    public function getNumeroCuenta(): int {
        return $this->numeroCuenta;
    }

    public function getNombreCliente(): string {
        return $this->nombreCliente;
    }
}

// ====================== CUENTA DE AHORROS (HERENCIA) ======================
class CuentaAhorros extends Cuenta {
    private float $porcentajeInteresMensual;

    public function __construct(int $numeroCuenta, string $nombreCliente, float $saldoInicial, float $porcentajeInteresMensual) {
        parent::__construct($numeroCuenta, $nombreCliente, $saldoInicial);
        $this->porcentajeInteresMensual = $porcentajeInteresMensual;
    }

    public function getPorcentajeInteres(): float {
        return $this->porcentajeInteresMensual;
    }

    public function depositarIntereses(): void {
        $interes = $this->saldo * ($this->porcentajeInteresMensual / 100);
        if ($interes > 0) {
            $this->depositar($interes);
        }
    }
}

// ====================== CUENTA CORRIENTE (HERENCIA) ======================
class CuentaCorriente extends Cuenta {
    private const SOBREGIRO_MAXIMO = 300000.0;
    private const TASA_COBRO_4X1000 = 0.004; // 4 por mil

    public function __construct(int $numeroCuenta, string $nombreCliente, float $saldoInicial = 0.0) {
        parent::__construct($numeroCuenta, $nombreCliente, $saldoInicial);
    }

    public function depositar(float $cantidad): void {
        if ($cantidad > 0) {
            $cobro = $cantidad * self::TASA_COBRO_4X1000;
            $this->saldo += ($cantidad - $cobro);
        }
    }

    public function retirar(float $cantidad): bool {
        if ($cantidad > 0) {
            $cobro = $cantidad * self::TASA_COBRO_4X1000;
            $totalDebitar = $cantidad + $cobro;
            $nuevoSaldo = $this->saldo - $totalDebitar;

            if ($nuevoSaldo >= -self::SOBREGIRO_MAXIMO) {
                $this->saldo = $nuevoSaldo;
                return true;
            }
        }
        return false;
    }
}

// ====================== LÓGICA DE LA APLICACIÓN ======================
if (isset($_GET['reset'])) {
    unset($_SESSION['cuenta']);
    header("Location: index.php");
    exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear cuenta
    if (isset($_POST['crear'])) {
        $tipo = $_POST['tipo_cuenta'] ?? '';
        $numero = (int)($_POST['numero_cuenta'] ?? 0);
        $nombre = trim($_POST['nombre_cliente'] ?? '');
        $saldoInicial = (float)($_POST['saldo_inicial'] ?? 0);

        if ($numero > 0 && $nombre !== '') {
            if ($tipo === 'ahorros') {
                $porcentaje = (float)($_POST['porcentaje_interes'] ?? 0);
                $_SESSION['cuenta'] = new CuentaAhorros($numero, $nombre, $saldoInicial, $porcentaje);
                $mensaje = "✅ Cuenta de Ahorros creada exitosamente.";
            } else {
                $_SESSION['cuenta'] = new CuentaCorriente($numero, $nombre, $saldoInicial);
                $mensaje = "✅ Cuenta Corriente creada exitosamente.";
            }
        } else {
            $mensaje = "❌ Datos inválidos para crear la cuenta.";
        }
    }
    // Operaciones (solo si ya existe cuenta)
    elseif (isset($_SESSION['cuenta'])) {
        $cuenta = $_SESSION['cuenta'];

        if (isset($_POST['depositar'])) {
            $cant = (float)($_POST['cantidad_deposito'] ?? 0);
            if ($cant > 0) $cuenta->depositar($cant);
            $mensaje = "💰 Depósito realizado.";
        }

        if (isset($_POST['retirar'])) {
            $cant = (float)($_POST['cantidad_retiro'] ?? 0);
            if ($cant > 0 && $cuenta->retirar($cant)) {
                $mensaje = "🏧 Retiro realizado exitosamente.";
            } else {
                $mensaje = "❌ No se pudo retirar (saldo insuficiente o límite de sobregiro alcanzado).";
            }
        }

        if (isset($_POST['intereses']) && $cuenta instanceof CuentaAhorros) {
            $cuenta->depositarIntereses();
            $mensaje = "📅 Intereses mensuales depositados.";
        }

        $_SESSION['cuenta'] = $cuenta; // guardamos los cambios
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Banco HBC - Sistema de Cuentas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        form { margin: 15px 0; }
        input, select { margin: 5px 0; padding: 8px; }
    </style>
</head>
<body>
    <h1>🏦 Banco HBC - Ejercicio Herencia PHP</h1>

    <?php if ($mensaje): ?>
        <p><strong><?= $mensaje ?></strong></p>
    <?php endif; ?>

    <?php if (!isset($_SESSION['cuenta'])): ?>
        <!-- ====================== FORMULARIO CREAR CUENTA ====================== -->
        <h2>1. Crear Nueva Cuenta</h2>
        <form method="post">
            <label>Tipo de cuenta:</label><br>
            <select name="tipo_cuenta" required>
                <option value="ahorros">Cuenta de Ahorros</option>
                <option value="corriente">Cuenta Corriente</option>
            </select><br><br>

            <label>Número de cuenta:</label><br>
            <input type="number" name="numero_cuenta" required><br><br>

            <label>Nombre del cliente:</label><br>
            <input type="text" name="nombre_cliente" required><br><br>

            <label>Saldo inicial ($):</label><br>
            <input type="number" step="0.01" name="saldo_inicial" value="0"><br><br>

            <label>Porcentaje de interés mensual (%): <small>(solo ahorros)</small></label><br>
            <input type="number" step="0.01" name="porcentaje_interes" value="1.5"><br><br>

            <input type="submit" name="crear" value="Crear Cuenta">
        </form>

    <?php else: ?>
        <!-- ====================== DATOS DE LA CUENTA ====================== -->
        <?php 
        $cuenta = $_SESSION['cuenta']; 
        $tipo = $cuenta instanceof CuentaAhorros ? 'Ahorros' : 'Corriente';
        ?>
        <h2>📋 Cuenta Actual</h2>
        <p><strong>Tipo:</strong> <?= $tipo ?></p>
        <p><strong>Número de cuenta:</strong> <?= $cuenta->getNumeroCuenta() ?></p>
        <p><strong>Nombre del cliente:</strong> <?= $cuenta->getNombreCliente() ?></p>
        <p><strong>Saldo actual:</strong> $<?= number_format($cuenta->consultarSaldo(), 2) ?></p>

        <?php if ($cuenta instanceof CuentaAhorros): ?>
            <p><strong>Interés mensual:</strong> <?= $cuenta->getPorcentajeInteres() ?> %</p>
        <?php endif; ?>

        <!-- ====================== OPERACIONES ====================== -->
        <h2>Operaciones</h2>

        <form method="post">
            <h3>💰 Depositar</h3>
            <input type="number" step="0.01" name="cantidad_deposito" placeholder="Cantidad" required>
            <input type="submit" name="depositar" value="Depositar">
        </form>

        <form method="post">
            <h3>🏧 Retirar</h3>
            <input type="number" step="0.01" name="cantidad_retiro" placeholder="Cantidad" required>
            <input type="submit" name="retirar" value="Retirar">
        </form>

        <?php if ($cuenta instanceof CuentaAhorros): ?>
            <form method="post">
                <h3>📅 Depositar intereses (primer día del mes)</h3>
                <input type="submit" name="intereses" value="Depositar Intereses Mensuales">
            </form>
        <?php endif; ?>

        <p><a href="?reset=1">🔄 Crear otra cuenta (reiniciar)</a></p>
    <?php endif; ?>
</body>
</html>