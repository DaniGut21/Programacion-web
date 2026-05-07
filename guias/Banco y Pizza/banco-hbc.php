<?php
declare(strict_types=1);

// 1. DEFINICIÓN DE CLASES E INTERFACES (Lógica de Negocio)
interface OperacionesBancarias {
    public function depositar(float $monto): void;
    public function retirar(float $monto): bool;
}

abstract class CuentaBase implements OperacionesBancarias {
    protected string $numero;
    protected string $titular;
    protected float $saldo;

    public function __construct(string $numero, string $titular, float $saldoInicial) {
        $this->numero = $numero;
        $this->titular = $titular;
        $this->saldo = $saldoInicial;
    }

    public function getSaldo(): float { return $this->saldo; }
    public function getTitular(): string { return $this->titular; }
    public function getNumero(): string { return $this->numero; }
    abstract public function getTipoNombre(): string;
    abstract public function calcularCosto(float $monto): float;
}

class CuentaAhorros extends CuentaBase {
    private const TASA = 0.015;
    public function getTipoNombre(): string { return "Ahorros Premium"; }
    public function calcularCosto(float $monto): float { return 0.0; }
    public function depositar(float $monto): void { $this->saldo += $monto; }
    public function retirar(float $monto): bool {
        if ($this->saldo >= $monto) { $this->saldo -= $monto; return true; }
        return false;
    }
    public function aplicarInteres(): float {
        $ganancia = $this->saldo * self::TASA;
        $this->saldo += $ganancia;
        return $ganancia;
    }
}

class CuentaCorriente extends CuentaBase {
    private const GMF = 0.004; // 4x1000
    public function getTipoNombre(): string { return "Corriente Empresarial"; }
    public function calcularCosto(float $monto): float { return $monto * self::GMF; }
    public function depositar(float $monto): void { $this->saldo += ($monto - $this->calcularCosto($monto)); }
    public function retirar(float $monto): bool {
        $total = $monto + $this->calcularCosto($monto);
        if (($this->saldo + 30000) >= $total) { $this->saldo -= $total; return true; }
        return false;
    }
}

session_start();

// 2. PROCESAMIENTO DE ACCIONES (SERVIDOR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $tipo = $_POST['tipo'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $numStr = $_POST['numero'] ?? '';
        $saldoStr = $_POST['saldo'] ?? '';

        if (empty($tipo)) {
            $_SESSION['error'] = "⚠️ Debe seleccionar Ahorros o Corriente.";
        } elseif (preg_match('/[0-9]/', $nombre)) {
            $_SESSION['error'] = "❌ El nombre no puede contener números.";
        } else {
            $_SESSION['account_obj'] = ($tipo === 'ahorros') 
                ? new CuentaAhorros($numStr, $nombre, (float)$saldoStr) 
                : new CuentaCorriente($numStr, $nombre, (float)$saldoStr);
            $_SESSION['mensaje'] = "✅ Cuenta activada exitosamente.";
        }
        header("Location: banco-hbc.php"); exit;
    }

    if (isset($_SESSION['account_obj'])) {
        $acc = $_SESSION['account_obj'];
        $montoStr = $_POST['monto'] ?? '0';
        $monto = (float)$montoStr;

        if ($action === 'deposit') {
            $costo = $acc->calcularCosto($monto);
            $acc->depositar($monto);
            $msg = "💰 Depósito exitoso por $" . number_format($monto, 0);
            if ($costo > 0) $msg .= " (Impuesto 4x1000: -$" . number_format($costo, 0) . ")";
            $_SESSION['mensaje'] = $msg;
        } 
        elseif ($action === 'withdraw') { 
            $costo = $acc->calcularCosto($monto);
            if($acc->retirar($monto)) {
                $msg = "💸 Retiro exitoso por $" . number_format($monto, 0);
                if ($costo > 0) $msg .= " (Gravamen GMF: $" . number_format($costo, 0) . ")";
                $_SESSION['mensaje'] = $msg;
            } else {
                $_SESSION['error'] = "❌ Fondos insuficientes para el retiro y costos de transacción.";
            }
        }
        elseif ($action === 'interest' && $acc instanceof CuentaAhorros) {
            $ganancia = $acc->aplicarInteres();
            $_SESSION['mensaje'] = "📈 Intereses aplicados: +$" . number_format($ganancia, 2) . " (Tasa 1.5%)";
        }
        header("Location: banco-hbc.php"); exit;
    }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: banco-hbc.php"); exit; }
$acc = $_SESSION['account_obj'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Banco HBC - Gestión Profesional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.6); }
        .btn-hbc { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .btn-hbc:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,31,63,0.1); }
    </style>
</head>
<body class="min-h-screen">

<?php if (!$acc): ?>
    <div class="max-w-md mx-auto pt-20 px-6">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-[#001f3f] italic">BANCO HBC</h1>
            <p class="text-slate-400 text-sm">Ingeniería de Sistemas - UNIMINUTO</p>
        </div>

        <div class="glass-card p-10 rounded-[2.5rem] shadow-2xl">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="bg-red-50 text-red-600 text-xs p-4 rounded-xl mb-4 border border-red-100 italic flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="mainForm" class="space-y-5">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="tipo" id="tipo_val">

                <div class="flex gap-3">
                    <button type="button" onclick="setTipo('ahorros', this)" class="tipo-btn flex-1 py-4 rounded-2xl border-2 border-slate-100 flex flex-col items-center gap-2 transition-all hover:bg-slate-50">
                        <i class="fa-solid fa-piggy-bank text-slate-300"></i>
                        <span class="text-[10px] font-bold uppercase">Ahorros</span>
                    </button>
                    <button type="button" onclick="setTipo('corriente', this)" class="tipo-btn flex-1 py-4 rounded-2xl border-2 border-slate-100 flex flex-col items-center gap-2 transition-all hover:bg-slate-50">
                        <i class="fa-solid fa-wallet text-slate-300"></i>
                        <span class="text-[10px] font-bold uppercase">Corriente</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <input type="text" name="nombre" id="input_nombre" placeholder="Nombre completo" oninput="this.value = this.value.replace(/[0-9]/g, '')" class="w-full bg-slate-50/80 p-4 rounded-xl outline-none ring-1 ring-slate-100 focus:ring-2 focus:ring-blue-900 transition-all" required>
                    
                    <input type="text" name="numero" id="input_cuenta" placeholder="Número de cuenta" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full bg-slate-50/80 p-4 rounded-xl outline-none ring-1 ring-slate-100 focus:ring-2 focus:ring-blue-900 transition-all" required>
                    
                    <input type="text" name="saldo" id="input_saldo" placeholder="Saldo inicial ($)" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full bg-slate-50/80 p-4 rounded-xl outline-none ring-1 ring-slate-100 focus:ring-2 focus:ring-blue-900 transition-all font-bold" required>
                </div>

                <button type="submit" class="btn-hbc w-full bg-[#001f3f] text-white py-5 rounded-2xl font-bold">ABRIR CUENTA</button>
            </form>
        </div>
    </div>

<?php else: ?>
    <nav class="p-6 flex justify-between items-center max-w-6xl mx-auto w-full">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-[#001f3f] rounded-lg flex items-center justify-center text-white font-bold shadow-lg">H</div>
            <span class="font-extrabold text-lg text-slate-800 tracking-tighter">BANCO HBC</span>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-emerald-50 px-3 py-1.5 rounded-full flex items-center gap-2">
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                <span class="text-[9px] font-bold text-emerald-600 uppercase">Seguridad Activa</span>
            </div>
            <a href="?logout=1" class="text-slate-300 hover:text-red-500 transition-colors"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-6 w-full">
        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="bg-blue-900 text-white p-5 rounded-2xl mb-6 shadow-xl flex items-center gap-4 border-l-4 border-emerald-400 animate-in fade-in slide-in-from-top-4 duration-500">
                <i class="fa-solid fa-circle-info text-emerald-400"></i>
                <p class="text-sm font-medium"><?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></p>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-50 text-red-600 p-5 rounded-2xl mb-6 border border-red-100 text-sm flex items-center gap-4">
                <i class="fa-solid fa-circle-xmark"></i>
                <p class="font-bold"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        <?php endif; ?>

        <div class="glass-card rounded-[2.5rem] p-10 mb-8 shadow-sm">
            <p class="text-slate-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-2"><?= $acc->getTipoNombre() ?></p>
            <h2 class="text-6xl font-black text-[#001f3f] tracking-tighter">$<?= number_format($acc->getSaldo(), 2, ',', '.') ?></h2>
            <div class="mt-4 flex items-center gap-3 text-slate-500">
                <i class="fa-solid fa-user-circle"></i>
                <span class="text-sm font-semibold"><?= htmlspecialchars($acc->getTitular()) ?> (N° <?= $acc->getNumero() ?>)</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="glass-card p-8 rounded-[2rem]">
                <h3 class="font-bold text-slate-800 mb-6 text-xs uppercase tracking-widest flex items-center gap-2">
                    <i class="fa-solid fa-gears text-blue-500"></i> Centro de Transacciones
                </h3>
                <form method="POST" id="opForm" class="space-y-4">
                    <input type="text" name="monto" id="input_monto" placeholder="Monto a operar..." oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full bg-slate-50 p-5 rounded-xl outline-none border border-slate-100 focus:border-blue-900 font-bold text-lg">
                    <div class="flex gap-3">
                        <button type="submit" name="action" value="deposit" class="flex-1 bg-slate-900 text-white py-4 rounded-xl text-xs font-bold btn-hbc">DEPÓSITO</button>
                        <button type="submit" name="action" value="withdraw" class="flex-1 border-2 border-slate-900 text-[#001f3f] py-4 rounded-xl text-xs font-bold btn-hbc">RETIRO</button>
                    </div>
                </form>
            </div>

            <?php if ($acc instanceof CuentaAhorros): ?>
                <div class="bg-[#001f3f] p-8 rounded-[2rem] text-white flex flex-col justify-between shadow-xl shadow-blue-900/20">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-chart-line text-emerald-400"></i>
                            <h3 class="font-bold">Interés Estándar Activo</h3>
                        </div>
                        <p class="text-blue-200 text-xs leading-relaxed">Su cuenta genera rendimientos del 1.5%. Haga clic para capitalizar sus intereses ahora.</p>
                    </div>
                    <form method="POST" class="mt-6">
                        <button type="submit" name="action" value="interest" class="w-full bg-white text-[#001f3f] py-4 rounded-xl font-bold text-xs hover:scale-[1.02] transition-all">CAPITALIZAR RENDIMIENTO</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
// VALIDACIÓN EN TIEMPO REAL Y ALERTAS
function setTipo(tipo, btn) {
    document.getElementById('tipo_val').value = tipo;
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('border-blue-900', 'bg-blue-50'));
    btn.classList.add('border-blue-900', 'bg-blue-50');
}

// Bloqueo de envío si hay errores lógicos
document.getElementById('mainForm')?.addEventListener('submit', function(e) {
    const tipo = document.getElementById('tipo_val').value;
    const nombre = document.getElementById('input_nombre').value;
    
    if(!tipo) {
        alert("⚠️ Por favor, seleccione un tipo de cuenta.");
        e.preventDefault();
    } else if(/[0-9]/.test(nombre)) {
        alert("❌ El nombre no puede contener números.");
        e.preventDefault();
    }
});

document.getElementById('opForm')?.addEventListener('submit', function(e) {
    const monto = document.getElementById('input_monto').value;
    if(isNaN(monto) || monto === "" || monto <= 0) {
        alert("❌ Ingrese un monto numérico válido mayor a cero.");
        e.preventDefault();
    }
});
</script>
</body>
</html>