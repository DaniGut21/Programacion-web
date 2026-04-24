<?php

// ================================================

// BANCO HBC - Todo en un solo archivo

// ================================================

session_start();


if (!isset($_SESSION['account'])) {

$_SESSION['account'] = null;

}


function money($amount) {

return '$' . number_format($amount, 2, ',', '.');

}


function calcularInteres($balance, $rate) {

return $balance * $rate;

}


// Procesar acciones POST

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$action = $_POST['action'] ?? '';


if ($action === 'create') {

$tipo = $_POST['tipo'] ?? 'ahorros';

$numero = preg_replace('/\s+/', '', $_POST['numero'] ?? '100234');

$nombre = $_POST['nombre'] ?? 'Ana García López';

$saldo = (float) str_replace([',', '.'], ['', '.'], $_POST['saldo'] ?? 100000); // acepta 100.000 o 100.000

$interes = (float) str_replace(['%', ','], ['', '.'], $_POST['interes'] ?? 1.5) / 100;


$_SESSION['account'] = [

'type' => $tipo,

'number' => $numero,

'name' => $nombre,

'balance' => $saldo,

'rate' => ($tipo === 'ahorros')? $interes : 0,

'fee_rate' => ($tipo === 'corriente')? 0.004: 0,

'sobregiro' => ($tipo === 'corriente')? 30000: 0

];

$_SESSION['mensaje'] = "Cuenta creada exitosamente";

header("Location: banco-hbc.php");

exit;

}


if ($action === 'deposit' && $_SESSION['account']) {

$monto = (float) str_replace([',', '.'], ['', '.'], $_POST['monto'] ?? 0);

$acc = &$_SESSION['account'];

$fee = ($acc['fee_rate'] > 0) ? $monto * $acc['fee_rate'] : 0;

$acc['balance'] += ($monto - $fee);

$_SESSION['mensaje'] = "Depósito de " . money($monto). " realizado." . ($fee > 0 ? " Cobro 4×1000: " . money($fee): "");

header("Location: banco-hbc.php");

exit;

}


if ($action === 'withdraw' && $_SESSION['account']) {

$monto = (float) str_replace([',', '.'], ['', '.'], $_POST['monto'] ?? 0);

$acc = &$_SESSION['account'];

if ($acc['balance'] < $monto && $acc['type'] === 'ahorros') {

$_SESSION['error'] = "❌ Saldo insuficiente";

} else {

$fee = ($acc['fee_rate'] > 0) ? $monto * $acc['fee_rate'] : 0;

$acc['balance'] -= ($monto + $fee);

$_SESSION['mensaje'] = "Retiro de " . money($monto). " realizado." . ($fee > 0 ? " Cobro 4×1000: " . money($fee): "");

}

header("Location: banco-hbc.php");

exit;

}


if ($action === 'interest' && $_SESSION['account'] && $_SESSION['account']['type'] === 'ahorros') {

$acc = &$_SESSION['account'];

$interes = calcularInteres($acc['balance'], $acc['rate']);

$acc['balance'] += $interes;

$_SESSION['mensaje'] = "Intereses acreditados: +" . money($interes);

header("Location: banco-hbc.php");

exit;

}

}


// Cerrar sesión

if (isset($_GET['logout'])) {

session_destroy();

header("Location: banco-hbc.php");

exit;

}


$acc = $_SESSION['account'];

$isAhorros = $acc && $acc['type'] === 'ahorros';

$page = $_GET['page'] ?? 'home';

?>


<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Banco HBC</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

body { font-family: 'Inter', system-ui, sans-serif; }

.header-blue { background: linear-gradient(90deg, #001f3f, #003366); }

.btn-marine { background-color: #001f3f; }

.btn-marine:hover { background-color: #003366; }

.card { box-shadow: 0 10px 15px -3px rgb(0 31 63 / 0.15); }

</style>

</head>

<body class="bg-slate-100">


<?php if (!$acc): // Pantalla Crear Cuenta ?>

<div class="max-w-2xl mx-auto pt-12 px-4">

<div class="header-blue text-white p-10 rounded-t-3xl text-center">

<div class="flex justify-center items-center gap-4 mb-3">

<div class="w-14 h-14 bg-white text-[#001f3f] rounded-3xl flex items-center justify-center text-4xl font-bold">HBC</div>

<h1 class="text-5xl font-semibold">Banco HBC</h1>

</div>

<p class="text-2xl">Sistema de Cuentas Bancarias</p>

<p class="mt-2 opacity-90">Seguro · Confiable · Simple</p>

</div>


<div class="bg-white rounded-b-3xl card p-10">

<h2 class="text-3xl font-semibold text-center mb-2">Crear nueva cuenta</h2>

<p class="text-center text-slate-500 mb-8">Completa los datos para comenzar</p>


<form method="POST" class="space-y-8" onsubmit="if(!document.getElementById('tipo').value){ alert('Por favor, selecciona un tipo de cuenta (Ahorros o Corriente)'); return false; }">

<input type="hidden" name="action" value="create">


<div>

<label class="block text-sm font-medium text-slate-600 mb-3">Tipo de cuenta</label>

<div class="grid grid-cols-2 gap-4">

<button type="button" onclick="selectType(this, 'ahorros')"

class="type-btn flex flex-col items-center py-8 border-2 border-slate-200 rounded-3xl hover:border-[#001f3f] transition-all">

<i class="fa-solid fa-piggy-bank text-5xl mb-4 text-slate-400"></i>

<span class="font-semibold text-xl">Ahorros</span>

</button>

<button type="button" onclick="selectType(this, 'corriente')"

class="type-btn flex flex-col items-center py-8 border-2 border-slate-200 rounded-3xl hover:border-[#001f3f] transition-all">

<i class="fa-solid fa-credit-card text-5xl mb-4 text-slate-400"></i>

<span class="font-semibold text-xl">Corriente</span>

</button>

</div>

<input type="hidden" name="tipo" id="tipo" required>

</div>


<div class="grid grid-cols-2 gap-6">

<div>

<label class="block text-sm font-medium mb-1">Número de cuenta</label>

<input type="text" name="numero" value="100234" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full px-6 py-4 border rounded-2xl focus:outline-none focus:border-[#001f3f]" required>

</div>

<div>

<label class="block text-sm font-medium mb-1">Nombre del cliente</label>

<input type="text" name="nombre" value="Ana Garcia Lopez" oninput="this.value = this.value.replace(/[0-9]/g, '')" class="w-full px-6 py-4 border rounded-2xl focus:outline-none focus:border-[#001f3f]" required>

</div>

</div>


<div class="grid grid-cols-2 gap-6">

<div>

<label class="block text-sm font-medium mb-1">Saldo inicial ($)</label>

<input type="text" name="saldo" value="100000" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full px-6 py-4 border rounded-2xl focus:outline-none focus:border-[#001f3f]" required>

</div>

<div>

<label class="block text-sm font-medium mb-1">Interés mensual (%)</label>

<input type="text" name="interes" value="1.5" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')" class="w-full px-6 py-4 border rounded-2xl focus:outline-none focus:border-[#001f3f]" required>

</div>

</div>


<button type="submit" class="btn-marine w-full py-6 text-white text-2xl font-semibold rounded-3xl flex items-center justify-center gap-3">

Crear cuenta <i class="fa-solid fa-arrow-right"></i>

</button>

</form>

</div>

</div>


<?php else: // Dashboard y otras pantallas ?>

<div class="header-blue text-white px-6 py-5 flex items-center justify-between sticky top-0 z-50">

<div class="flex items-center gap-3">

<div class="w-11 h-11 bg-white text-[#001f3f] rounded-2xl flex items-center justify-center text-3xl font-bold">HBC</div>

<div>

<div class="text-2xl font-semibold">Banco HBC</div>

<div class="text-xs opacity-75">v1.0 · 2025</div>

</div>

</div>

<div class="flex gap-8 text-sm">

<a href="banco-hbc.php" class="flex items-center gap-2 hover:underline"><i class="fa-solid fa-house"></i> Mi Cuenta</a>

<a href="banco-hbc.php?page=deposit" class="flex items-center gap-2 hover:underline"><i class="fa-solid fa-arrow-up-from-bracket"></i> Depósito</a>

<a href="banco-hbc.php?page=withdraw" class="flex items-center gap-2 hover:underline"><i class="fa-solid fa-arrow-down-to-bracket"></i> Retiro</a>

<?php if ($isAhorros): ?>

<a href="banco-hbc.php?action=interest" class="flex items-center gap-2 hover:underline"><i class="fa-solid fa-hand-holding-dollar"></i> Intereses</a>

<?php endif; ?>

</div>

<a href="?logout=1" class="flex items-center gap-2 hover:text-red-300"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>

</div>


<div class="max-w-4xl mx-auto p-6">


<?php if (isset($_SESSION['mensaje'])): ?>

<div class="bg-emerald-100 border border-emerald-400 text-emerald-800 px-6 py-4 rounded-3xl mb-6 flex items-center gap-3">

<i class="fa-solid fa-check-circle text-2xl"></i>

<?= htmlspecialchars($_SESSION['mensaje']) ?>

</div>

<?php unset($_SESSION['mensaje']); ?>

<?php endif; ?>


<?php if (isset($_SESSION['error'])): ?>

<div class="bg-red-100 border border-red-400 text-red-800 px-6 py-4 rounded-3xl mb-6 flex items-center gap-3">

<i class="fa-solid fa-circle-exclamation text-2xl"></i>

<?= htmlspecialchars($_SESSION['error']) ?>

</div>

<?php unset($_SESSION['error']); ?>

<?php endif; ?>


<?php if ($page === 'home'): // Panel de control ?>

<div class="bg-white rounded-3xl card p-10">

<div class="flex justify-between items-start">

<div>

<span class="px-5 py-2 bg-emerald-100 text-emerald-700 font-semibold rounded-3xl text-sm">

<?= $acc['type'] === 'ahorros' ? 'Cuenta de Ahorros' : 'Cuenta Corriente' ?>

</span>

<h1 class="text-4xl font-bold mt-4"><?= htmlspecialchars($acc['name']) ?></h1>

<p class="text-slate-500">N° <?= $acc['number'] ?></p>

</div>

<div class="text-right">

<div class="text-6xl font-bold text-[#001f3f]"><?= money($acc['balance']) ?></div>

<p class="text-slate-500">Saldo disponible</p>

</div>

</div>


<?php if ($isAhorros): ?>

<div class="mt-12 grid grid-cols-3 gap-6">

<div class="bg-slate-50 rounded-2xl p-6">

<p class="text-sm text-slate-500">Tasa mensual</p>

<p class="text-3xl font-bold"><?= number_format($acc['rate']*100, 1) ?>%</p>

</div>

<div class="bg-emerald-50 rounded-2xl p-6 col-span-2">

<p class="text-emerald-700">Interés estimado este mes</p>

<p class="text-4xl font-bold text-emerald-600">+ <?= money(calcularInteres($acc['balance'], $acc['rate'])) ?></p>

<form method="POST" class="mt-4">

<input type="hidden" name="action" value="interest">

<button type="submit" class="bg-emerald-600 text-white px-8 py-3 rounded-2xl hover:bg-emerald-700">Acreditar intereses</button>

</form>

</div>

</div>

<?php else: ?>

<div class="mt-10 bg-amber-50 border border-amber-200 rounded-3xl p-8">

<p class="font-semibold text-amber-800">Cobro 4×1000 (0,4% por operación)</p>

<p class="text-sm text-amber-700 mt-2">Se aplica en depósitos y retiros.</p>

</div>

<?php endif; ?>


<div class="mt-12 grid grid-cols-3 gap-4">

<a href="?page=deposit" class="btn-marine text-white text-center py-8 rounded-3xl font-semibold text-xl flex flex-col items-center gap-2">

<i class="fa-solid fa-arrow-up-from-bracket text-3xl"></i> Deposito

</a>

<a href="?page=withdraw" class="btn-marine text-white text-center py-8 rounded-3xl font-semibold text-xl flex flex-col items-center gap-2">

<i class="fa-solid fa-arrow-down-to-bracket text-3xl"></i> Retirar

</a>

<a href="?logout=1" class="bg-slate-700 text-white text-center py-8 rounded-3xl font-semibold text-xl flex flex-col items-center gap-2">

<i class="fa-solid fa-plus text-3xl"></i> Nueva cuenta

</a>

</div>

</div>


<?php elseif ($page === 'deposit'): ?>

<div class="bg-white rounded-3xl card p-10 max-w-2xl mx-auto">

<h2 class="text-3xl font-semibold text-center mb-2">Depositar</h2>

<p class="text-center text-slate-500 mb-10">Ingresa el monto a depositar</p>

<form method="POST">

<input type="hidden" name="action" value="deposit">

<div class="relative mb-8">

<span class="absolute left-8 top-1/2 -translate-y-1/2 text-5xl text-slate-300">$</span>

<input type="text" name="monto" id="monto" oninput="this.value = this.value.replace(/[^0-9]/g, '')"

class="w-full pl-20 pr-8 py-7 text-5xl font-semibold border-2 border-slate-200 rounded-3xl text-center focus:border-[#001f3f] outline-none"

placeholder="0" required>

</div>


<div class="grid grid-cols-4 gap-3 mb-10">

<button type="button" onclick="setAmount(50000)" class="border py-4 rounded-2xl hover:bg-[#001f3f] hover:text-white transition">50,000</button>

<button type="button" onclick="setAmount(100000)" class="border py-4 rounded-2xl hover:bg-[#001f3f] hover:text-white transition">100,000</button>

<button type="button" onclick="setAmount(200000)" class="border py-4 rounded-2xl hover:bg-[#001f3f] hover:text-white transition">200,000</button>

<button type="button" onclick="setAmount(500000)" class="border py-4 rounded-2xl hover:bg-[#001f3f] hover:text-white transition">500,000</button>

</div>


<div class="flex gap-4">

<button type="submit" class="flex-1 btn-marine py-6 text-white text-xl font-semibold rounded-3xl">Confirmar depósito</button>

<a href="banco-hbc.php" class="flex-1 border border-slate-300 py-6 text-center text-slate-700 text-xl font-semibold rounded-3xl">Cancelar</a>

</div>

</form>

</div>


<?php elseif ($page === 'withdraw'): ?>

<div class="bg-white rounded-3xl card p-10 max-w-2xl mx-auto">

<h2 class="text-3xl font-semibold text-center mb-2">Retirar</h2>

<p class="text-center text-slate-500 mb-10">Ingresa el monto a retirar</p>

<form method="POST">

<input type="hidden" name="action" value="withdraw">

<div class="relative mb-8">

<span class="absolute left-8 top-1/2 -translate-y-1/2 text-5xl text-slate-300">$</span>

<input type="text" name="monto" id="monto_ret" oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateAfterWithdraw()"

class="w-full pl-20 pr-8 py-7 text-5xl font-semibold border-2 border-slate-200 rounded-3xl text-center focus:border-[#001f3f] outline-none"

placeholder="0" required>

</div>


<div class="bg-slate-50 rounded-3xl p-6 mb-8">

<div class="flex justify-between text-lg">

<span>Saldo disponible:</span>

<span class="font-bold"><?= money($acc['balance']) ?></span>

</div>

<div class="flex justify-between text-lg mt-4 pt-4 border-t">

<span>Saldo tras retiro:</span>

<span id="saldo_tras" class="font-bold text-emerald-600">—</span>

</div>

</div>


<div class="flex gap-4">

<button type="submit" class="flex-1 btn-marine py-6 text-white text-xl font-semibold rounded-3xl">Confirmar retiro</button>

<a href="banco-hbc.php" class="flex-1 border border-slate-300 py-6 text-center text-slate-700 text-xl font-semibold rounded-3xl">Cancelar</a>

</div>

</form>

</div>

<?php endif; ?>

</div>

<?php endif; ?>


<script>

function selectType(btn, tipo) {

document.getElementById('tipo').value = tipo;

document.querySelectorAll('.type-btn').forEach(b => {

b.classList.remove('border-[#001f3f]', 'bg-[#001f3f]', 'text-white');

});

btn.classList.add('border-[#001f3f]', 'bg-[#001f3f]', 'text-white');

}


function setAmount(val) {

document.getElementById('monto').value = val;

}


function updateAfterWithdraw() {

const monto = parseFloat(document.getElementById('monto_ret').value) || 0;

const actual = <?= $acc ? $acc['balance'] : 0 ?>;

const tras = actual - monto;

const el = document.getElementById('saldo_tras');

if (tras >= 0) {

el.textContent = '$' + tras.toLocaleString('es-ES') + '.00';

el.className = 'font-bold text-emerald-600';

} else {

el.textContent = '❌ Saldo insuficiente';

el.className = 'font-bold text-red-600';

}

}


tailwind.config = { contenido: [] };

</script>

</body>

</html>