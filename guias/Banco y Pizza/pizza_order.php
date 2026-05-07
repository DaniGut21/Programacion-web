<?php
// ╔══════════════════════════════════════════════════════════════╗
//  INTERFACES
// ╚══════════════════════════════════════════════════════════════╝

interface Validatable {
    public function validate(): bool;
    public function getErrors(): array;
}

interface Renderable {
    public function render(): string;
}

// ╔══════════════════════════════════════════════════════════════╗
//  CLASE ABSTRACTA BASE — FormField
// ╚══════════════════════════════════════════════════════════════╝

abstract class FormField implements Validatable, Renderable {

    protected string $name;
    protected string $label;
    protected string $placeholder;
    protected string $value;
    protected bool   $required;
    protected array  $errors = [];

    public function __construct(
        string $name,
        string $label,
        string $placeholder = '',
        string $value       = '',
        bool   $required    = true
    ) {
        $this->name        = $name;
        $this->label       = $label;
        $this->placeholder = $placeholder;
        $this->value       = trim($value);
        $this->required    = $required;
    }

    public function getName():  string { return $this->name;  }
    public function getValue(): string { return $this->value; }
    public function getErrors(): array { return $this->errors; }
    public function hasErrors(): bool  { return !empty($this->errors); }

    protected function addError(string $msg): void {
        $this->errors[] = $msg;
    }

    protected function checkRequired(): bool {
        if ($this->required && $this->value === '') {
            $this->addError("Este campo es obligatorio.");
            return false;
        }
        return true;
    }

    protected function renderErrorBubble(): string {
        if (empty($this->errors)) return '';
        $out = '';
        foreach ($this->errors as $e) {
            $out .= '<div class="err-msg">' . htmlspecialchars($e) . '</div>';
        }
        return $out;
    }

    // Icono SVG — cada subclase puede sobreescribir
    protected function iconSvg(): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>';
    }

    public function render(): string {
        $v   = htmlspecialchars($this->value);
        $cls = $this->hasErrors() ? 'input-wrap has-error' : 'input-wrap';
        $err = $this->renderErrorBubble();
        $ico = $this->iconSvg();
        $typ = $this->inputType();
        return <<<HTML
        <div class="$cls">
            <span class="input-icon">$ico</span>
            <input type="$typ" name="{$this->name}" id="{$this->name}"
                   value="$v" placeholder="{$this->placeholder}" autocomplete="off">
            $err
        </div>
        HTML;
    }

    protected function inputType(): string { return 'text'; }
}

// ╔══════════════════════════════════════════════════════════════╗
//  CAMPO TEXTO — solo letras, sin números
// ╚══════════════════════════════════════════════════════════════╝

class TextField extends FormField {

    public function validate(): bool {
        $this->errors = [];
        if (!$this->checkRequired()) return false;

        if (preg_match('/[0-9]/', $this->value)) {
            $this->addError("❌ El campo «{$this->label}» no puede contener números. Escribe solo letras.");
            return false;
        }
        if (!preg_match('/^[\p{L}\s\'\.\-]+$/u', $this->value)) {
            $this->addError("❌ Solo se permiten letras y espacios en «{$this->label}».");
            return false;
        }
        return true;
    }
}

// ╔══════════════════════════════════════════════════════════════╗
//  CAMPO DIRECCIÓN — hereda de TextField, ícono de ubicación
// ╚══════════════════════════════════════════════════════════════╝

class AddressField extends TextField {

    protected function iconSvg(): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0
                             l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>';
    }

    // Dirección permite números (ej: "Calle 123")
    public function validate(): bool {
        $this->errors = [];
        return $this->checkRequired();
    }
}

// ╔══════════════════════════════════════════════════════════════╗
//  CAMPO TELÉFONO — solo dígitos, sin letras
// ╚══════════════════════════════════════════════════════════════╝

class PhoneField extends FormField {

    protected function iconSvg(): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493
                             a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516
                             5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0
                             01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>';
    }

    protected function inputType(): string { return 'tel'; }

    public function validate(): bool {
        $this->errors = [];
        if (!$this->checkRequired()) return false;

        if (preg_match('/[A-Za-z]/', $this->value)) {
            $this->addError("❌ El teléfono no puede contener letras. Escribe solo números.");
            return false;
        }
        $digits = preg_replace('/[\s\+\-\(\)]/', '', $this->value);
        if (!ctype_digit($digits)) {
            $this->addError("❌ El teléfono contiene caracteres no válidos.");
            return false;
        }
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $this->addError("❌ El teléfono debe tener entre 7 y 15 dígitos.");
            return false;
        }
        return true;
    }
}

// ╔══════════════════════════════════════════════════════════════╗
//  CAMPO EMAIL — obligatorio con @
// ╚══════════════════════════════════════════════════════════════╝

class EmailField extends FormField {

    protected function iconSvg(): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7
                             a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>';
    }

    protected function inputType(): string { return 'email'; }

    public function validate(): bool {
        $this->errors = [];
        if (!$this->checkRequired()) return false;

        if (strpos($this->value, '@') === false) {
            $this->addError("❌ El correo debe contener «@». Ejemplo: nombre@gmail.com");
            return false;
        }
        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            $this->addError("❌ El correo no es válido. Formato correcto: nombre@dominio.com");
            return false;
        }
        return true;
    }
}

// ╔══════════════════════════════════════════════════════════════╗
//  CLASE PIZZA
// ╚══════════════════════════════════════════════════════════════╝

class Pizza {
    private string $id;
    private string $emoji;
    private string $name;
    private int    $price;

    public function __construct(string $id, string $emoji, string $name, int $price) {
        $this->id    = $id;
        $this->emoji = $emoji;
        $this->name  = $name;
        $this->price = $price;
    }

    public function getId():    string { return $this->id;    }
    public function getEmoji(): string { return $this->emoji; }
    public function getName():  string { return $this->name;  }
    public function getPrice(): int    { return $this->price; }

    public function getFormattedPrice(): string {
        return '$ ' . number_format($this->price, 0, ',', '.');
    }
}

// ╔══════════════════════════════════════════════════════════════╗
//  CLASE PEDIDO — agrupa campos y pizzas
// ╚══════════════════════════════════════════════════════════════╝

class PizzaOrder implements Validatable {

    private TextField    $fieldName;
    private AddressField $fieldAddress;
    private PhoneField   $fieldPhone;
    private EmailField   $fieldEmail;
    private array        $selectedIds = [];
    private array        $errors      = [];
    private static array $catalog     = [];

    public function __construct(array $post) {
        $this->fieldName    = new TextField   ('name',    'Nombre',    'Tu nombre completo',  $post['name']    ?? '');
        $this->fieldAddress = new AddressField('address', 'Dirección', 'Dirección de entrega', $post['address'] ?? '');
        $this->fieldPhone   = new PhoneField  ('phone',   'Teléfono',  'Teléfono',            $post['phone']   ?? '');
        $this->fieldEmail   = new EmailField  ('email',   'Email',     'Email',               $post['email']   ?? '');
        $this->selectedIds  = (array)($post['pizzas'] ?? []);
    }

    // ── Catálogo ──────────────────────────────────────────────
    public static function getCatalog(): array {
        if (empty(self::$catalog)) {
            self::$catalog = [
                new Pizza('ham_cheese',  '🧀', 'Jamón y Queso',  12000),
                new Pizza('napolitana',  '🍅', 'Napolitana',     14000),
                new Pizza('muzzarella',  '🧀', 'Muzzarella',     11000),
                new Pizza('pepperoni',   '🍕', 'Pepperoni',      15000),
                new Pizza('bbq_chicken', '🍗', 'BBQ Chicken',    16000),
            ];
        }
        return self::$catalog;
    }

    // ── Validación ────────────────────────────────────────────
    public function validate(): bool {
        $this->errors = [];
        $ok  = $this->fieldName->validate();
        $ok  = $this->fieldAddress->validate() && $ok;
        $ok  = $this->fieldPhone->validate()   && $ok;
        $ok  = $this->fieldEmail->validate()   && $ok;
        if (empty($this->selectedIds)) {
            $this->errors[] = 'pizza_required';
            $ok = false;
        }
        return $ok;
    }

    public function getErrors(): array { return $this->errors; }

    public function hasAnyError(): bool {
        return $this->fieldName->hasErrors()
            || $this->fieldAddress->hasErrors()
            || $this->fieldPhone->hasErrors()
            || $this->fieldEmail->hasErrors()
            || !empty($this->errors);
    }

    // ── Getters ───────────────────────────────────────────────
    public function getFieldName():    TextField    { return $this->fieldName;    }
    public function getFieldAddress(): AddressField { return $this->fieldAddress; }
    public function getFieldPhone():   PhoneField   { return $this->fieldPhone;   }
    public function getFieldEmail():   EmailField   { return $this->fieldEmail;   }
    public function getSelectedIds():  array        { return $this->selectedIds;  }

    public function getSelectedPizzas(): array {
        return array_filter(self::getCatalog(), fn($p) => in_array($p->getId(), $this->selectedIds));
    }

    public function getSubtotal(): int {
        return array_sum(array_map(fn($p) => $p->getPrice(), $this->getSelectedPizzas()));
    }
    public function getIVA():   int { return (int)round($this->getSubtotal() * 0.19); }
    public function getTotal(): int { return $this->getSubtotal() + $this->getIVA(); }
    public function fmt(int $n): string { return '$ ' . number_format($n, 0, ',', '.'); }
}

// ╔══════════════════════════════════════════════════════════════╗
//  CONTROLADOR
// ╚══════════════════════════════════════════════════════════════╝

$order     = new PizzaOrder($_POST ?? []);
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$success   = $submitted && $order->validate();
$catalog   = PizzaOrder::getCatalog();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pizzas Online 🍕</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ═══ VARIABLES ═══════════════════════════════════════════════ */
:root {
  --pink:        #FF2D78;
  --pink-light:  #FF6FA8;
  --pink-bg:     #FFF0F5;
  --pink-card:   #FFF5F8;
  --pink-border: #FFD6E7;
  --pink-grad:   linear-gradient(135deg,#FF2D78 0%,#FF6FA8 100%);
  --text:        #2D1B2E;
  --muted:       #A07090;
  --white:       #FFFFFF;
  --rad:         16px;
  --shadow:      0 4px 28px rgba(255,45,120,.11);
}

/* ═══ RESET ════════════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',sans-serif;background:var(--pink-bg);color:var(--text);min-height:100vh}

/* ═══ HEADER ═══════════════════════════════════════════════════ */
.header{text-align:center;padding:48px 24px 28px}
.header-icon{
  width:72px;height:72px;
  border:2.5px solid var(--pink);
  border-radius:50%;
  display:inline-flex;align-items:center;justify-content:center;
  margin-bottom:18px;background:var(--white);
}
.header h1{font-size:clamp(2rem,5vw,2.8rem);font-weight:900;color:var(--pink);letter-spacing:-.5px}
.header p{color:var(--muted);font-size:1rem;margin-top:5px;font-weight:600}

/* ═══ LAYOUT ════════════════════════════════════════════════════ */
.layout{
  max-width:1080px;margin:0 auto;padding:0 20px 60px;
  display:grid;grid-template-columns:1fr 330px;gap:22px;align-items:start;
}
@media(max-width:820px){.layout{grid-template-columns:1fr}.sidebar{order:-1}}

/* ═══ CARD ══════════════════════════════════════════════════════ */
.card{
  background:var(--white);border-radius:22px;
  border:1.5px solid var(--pink-border);
  padding:28px 26px 30px;box-shadow:var(--shadow);
}
@media(max-width:500px){.card{padding:20px 15px 24px}}
.card+.card{margin-top:20px}

/* ═══ SECTION TITLE ════════════════════════════════════════════ */
.sec-title{
  display:flex;align-items:center;gap:10px;
  font-size:1.1rem;font-weight:800;color:var(--text);margin-bottom:20px;
}
.sec-title svg{color:var(--pink);flex-shrink:0}

/* ═══ INPUTS ════════════════════════════════════════════════════ */
.input-wrap{position:relative;margin-bottom:13px}
.input-icon{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:var(--pink-light);display:flex;pointer-events:none;z-index:1;
}
.input-wrap input{
  width:100%;padding:14px 16px 14px 44px;
  border:2px solid var(--pink-border);border-radius:var(--rad);
  font-family:'Nunito',sans-serif;font-size:.97rem;font-weight:600;
  color:var(--text);background:var(--pink-card);outline:none;
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.input-wrap input::placeholder{color:#C9A8C0;font-weight:500}
.input-wrap input:focus{
  border-color:var(--pink);background:var(--white);
  box-shadow:0 0 0 4px rgba(255,45,120,.13);
}
.input-wrap.has-error input{border-color:#ff4d4d;background:#fff5f5}
.err-msg{
  font-size:.8rem;font-weight:700;color:#cc002e;
  background:#fff0f3;border-left:3px solid #cc002e;
  padding:6px 10px;border-radius:0 8px 8px 0;margin-top:5px;
}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}
@media(max-width:480px){.grid-2{grid-template-columns:1fr}}

/* ═══ ALERT GLOBAL ══════════════════════════════════════════════ */
.alert-global{
  background:#fff0f4;border:2px solid #ff4d6a;border-radius:var(--rad);
  padding:13px 17px;margin-bottom:18px;
  font-size:.88rem;font-weight:700;color:#c0003a;
  display:flex;align-items:center;gap:10px;
}

/* ═══ PIZZA CARDS ═══════════════════════════════════════════════ */
.pizza-card{
  display:flex;align-items:center;gap:14px;
  padding:16px 18px;border:2px solid var(--pink-border);
  border-radius:var(--rad);background:var(--white);
  cursor:pointer;transition:border-color .2s,background .2s,box-shadow .2s;
  margin-bottom:11px;user-select:none;
}
.pizza-card:hover{border-color:var(--pink-light)}
.pizza-card.selected{
  border-color:var(--pink);background:var(--pink-bg);
  box-shadow:0 4px 18px rgba(255,45,120,.15);
}
.pizza-card input[type="checkbox"]{display:none}
.pizza-chk{
  width:22px;height:22px;border:2px solid var(--pink-border);border-radius:6px;
  flex-shrink:0;display:flex;align-items:center;justify-content:center;
  background:var(--white);transition:all .2s;
}
.pizza-card.selected .pizza-chk{background:var(--pink);border-color:var(--pink)}
.pizza-chk svg{display:none}
.pizza-card.selected .pizza-chk svg{display:block}
.pizza-emoji{font-size:1.8rem;flex-shrink:0}
.pizza-info{flex:1}
.pizza-name{font-weight:800;font-size:.98rem}
.pizza-price{color:var(--pink);font-weight:700;font-size:.88rem;margin-top:2px}
.pizza-right{font-weight:800;color:var(--pink);font-size:.98rem}
.no-pizza-err{
  color:#cc002e;font-size:.82rem;font-weight:700;
  background:#fff0f3;border-left:3px solid #cc002e;
  padding:7px 12px;border-radius:0 8px 8px 0;margin-bottom:14px;
}

/* ═══ CONFIRM BUTTON ════════════════════════════════════════════ */
.btn-confirm{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:17px;background:var(--pink-grad);color:var(--white);
  font-family:'Nunito',sans-serif;font-size:1.08rem;font-weight:800;
  border:none;border-radius:var(--rad);cursor:pointer;margin-top:12px;
  transition:opacity .2s,transform .15s,box-shadow .2s;
  box-shadow:0 6px 24px rgba(255,45,120,.35);letter-spacing:.3px;
}
.btn-confirm:hover{opacity:.92;transform:translateY(-2px);box-shadow:0 10px 30px rgba(255,45,120,.4)}
.btn-confirm:active{transform:translateY(0)}
.btn-confirm:disabled{opacity:.7;cursor:not-allowed;transform:none}

/* ═══ QTY CONTROLS ══════════════════════════════════════════════ */
.qty-controls{
  display:flex;align-items:center;gap:0;
  background:var(--pink-bg);border:2px solid var(--pink-border);
  border-radius:99px;overflow:hidden;flex-shrink:0;
}
.qty-btn{
  width:34px;height:34px;border:none;background:transparent;
  color:var(--pink);font-size:1.3rem;font-weight:700;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:background .15s,color .15s;line-height:1;
  font-family:'Nunito',sans-serif;padding:0;
}
.qty-btn:hover{background:var(--pink);color:var(--white)}
.qty-num{
  min-width:32px;text-align:center;font-weight:900;font-size:1rem;
  color:var(--text);
}
.pizza-card.selected .qty-controls{border-color:var(--pink)}
.pizza-card.selected .qty-num{color:var(--pink)}

/* ═══ LOADING OVERLAY ═══════════════════════════════════════════ */
#loading-overlay{
  position:fixed;inset:0;background:rgba(255,240,245,.95);
  backdrop-filter:blur(8px);z-index:9999;
  display:none;flex-direction:column;align-items:center;justify-content:center;
}
#loading-overlay.active{display:flex}
.loading-pizza{
  font-size:4rem;animation:spin-pizza 1.2s linear infinite;
  margin-bottom:24px;filter:drop-shadow(0 4px 12px rgba(255,45,120,.3));
}
@keyframes spin-pizza{
  0%{transform:rotate(0deg) scale(1)}
  50%{transform:rotate(180deg) scale(1.1)}
  100%{transform:rotate(360deg) scale(1)}
}
.loading-dots{display:flex;gap:8px;margin-bottom:18px}
.loading-dot{
  width:10px;height:10px;background:var(--pink);border-radius:50%;
  animation:dot-bounce 1.1s ease-in-out infinite;
}
.loading-dot:nth-child(2){animation-delay:.18s}
.loading-dot:nth-child(3){animation-delay:.36s}
@keyframes dot-bounce{
  0%,80%,100%{transform:scale(0.6);opacity:.5}
  40%{transform:scale(1);opacity:1}
}
.loading-label{
  font-size:1.1rem;font-weight:800;color:var(--pink);
  letter-spacing:.5px;text-align:center;
}

/* ═══ EN CAMINO SCREEN ═════════════════════════════════════════ */
#encamino-screen{
  display:none;flex-direction:column;align-items:center;justify-content:center;
  text-align:center;
}
.encamino-moto{
  font-size:5rem;margin-bottom:18px;
  animation:moto-go 1s ease-out forwards;
}
@keyframes moto-go{
  0%{transform:translateX(-60px);opacity:0}
  60%{transform:translateX(10px);opacity:1}
  100%{transform:translateX(0);opacity:1}
}
.encamino-title{
  font-size:2rem;font-weight:900;color:var(--pink);margin-bottom:8px;
  animation:fade-up .5s ease-out .3s both;
}
.encamino-sub{
  color:var(--muted);font-size:1rem;font-weight:600;
  animation:fade-up .5s ease-out .5s both;
}
@keyframes fade-up{
  from{transform:translateY(16px);opacity:0}
  to{transform:translateY(0);opacity:1}
}

/* ═══ SIDEBAR ═══════════════════════════════════════════════════ */
.sidebar .card{position:sticky;top:24px}
.summary-empty{text-align:center;padding:28px 0;color:var(--muted)}
.summary-empty p{font-size:.9rem;font-weight:600;margin-top:10px}
.s-item{
  display:flex;align-items:center;gap:11px;
  padding:10px 0;border-bottom:1px solid var(--pink-border);font-size:.92rem;
}
.s-item:last-child{border-bottom:none}
.s-emoji{font-size:1.3rem}
.s-name{flex:1;font-weight:700}
.s-price{color:var(--pink);font-weight:800}
.s-totals{margin-top:14px;padding-top:14px;border-top:2px solid var(--pink-border)}
.s-row{display:flex;justify-content:space-between;font-size:.88rem;font-weight:600;color:var(--muted);margin-bottom:5px}
.s-total{display:flex;justify-content:space-between;font-size:1.02rem;font-weight:900;color:var(--pink);margin-top:10px}

/* ═══ SUCCESS ════════════════════════════════════════════════════ */
.success-wrap{max-width:700px;margin:0 auto;padding:0 20px 60px}
.success-hdr{text-align:center;padding:40px 0 30px}
.check-circle{
  width:80px;height:80px;border:3px solid var(--pink);border-radius:50%;
  display:inline-flex;align-items:center;justify-content:center;
  margin-bottom:18px;animation:pop .55s cubic-bezier(.34,1.56,.64,1);
}
@keyframes pop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.success-hdr h2{font-size:2.1rem;font-weight:900;color:var(--pink)}
.success-hdr p{color:var(--muted);font-weight:600;margin-top:4px}
.success-divider{width:160px;height:3px;background:var(--pink-grad);border-radius:99px;margin:14px auto 0}
.mb20{margin-bottom:20px}
.client-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}
@media(max-width:500px){.client-grid{grid-template-columns:1fr}}
.c-cell{
  background:var(--pink-bg);border:1.5px solid var(--pink-border);
  border-radius:var(--rad);padding:14px 16px;
  display:flex;align-items:center;gap:12px;
}
.c-cell svg{color:var(--pink);flex-shrink:0}
.c-info label{font-size:.72rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.5px;display:block}
.c-info span{font-weight:800;font-size:.96rem}
.order-row{
  display:flex;align-items:center;gap:14px;
  padding:15px 18px;background:var(--pink-bg);
  border:1.5px solid var(--pink-border);border-radius:var(--rad);margin-bottom:10px;
}
.order-icon{
  width:44px;height:44px;background:var(--white);border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.4rem;border:1.5px solid var(--pink-border);flex-shrink:0;
}
.order-info{flex:1}
.order-info strong{font-weight:800;font-size:.96rem;display:block}
.order-info small{color:var(--muted);font-size:.8rem;font-weight:600}
.order-total{font-weight:900;color:var(--pink);font-size:.97rem}
.totals-box{
  background:var(--white);border:1.5px solid var(--pink-border);
  border-radius:var(--rad);padding:18px 20px;margin-top:12px;
}
.t-row{
  display:flex;justify-content:space-between;
  font-size:.92rem;font-weight:600;color:var(--muted);
  padding:5px 0;border-bottom:1px solid var(--pink-border);
}
.t-row:last-of-type{border-bottom:none}
.t-final{
  display:flex;justify-content:space-between;
  font-size:1.12rem;font-weight:900;color:var(--pink);
  padding-top:12px;margin-top:6px;border-top:2px solid var(--pink-border);
}
.btn-new{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:17px;background:var(--pink-grad);color:var(--white);
  font-family:'Nunito',sans-serif;font-size:1.05rem;font-weight:800;
  border:none;border-radius:var(--rad);cursor:pointer;margin-top:18px;
  box-shadow:0 6px 24px rgba(255,45,120,.3);
  transition:opacity .2s,transform .15s;text-decoration:none;
}
.btn-new:hover{opacity:.9;transform:translateY(-2px)}
.delivery-note{text-align:center;margin-top:16px;font-size:.87rem;color:var(--muted);font-weight:600}
</style>
</head>
<body>

<?php if ($success): ?>
<!-- ╔═══════════════════════════════╗
     ║    PANTALLA CONFIRMACIÓN      ║
     ╚═══════════════════════════════╝ -->
<div class="success-wrap">

  <div class="success-hdr">
    <div>
      <div class="check-circle">
        <svg width="34" height="34" fill="none" viewBox="0 0 24 24" stroke="#FF2D78" stroke-width="3">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
    </div>
    <h2>¡Pedido Confirmado!</h2>
    <p>✨ Gracias por tu orden ✨</p>
    <div class="success-divider"></div>
  </div>

  <!-- Datos del cliente -->
  <div class="card mb20">
    <div class="sec-title">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
      </svg>
      Datos del Cliente
    </div>
    <div class="client-grid">
      <div class="c-cell">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <div class="c-info"><label>Nombre</label><span><?= htmlspecialchars($order->getFieldName()->getValue()) ?></span></div>
      </div>
      <div class="c-cell">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21
                   l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502
                   l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
        <div class="c-info"><label>Teléfono</label><span><?= htmlspecialchars($order->getFieldPhone()->getValue()) ?></span></div>
      </div>
      <div class="c-cell">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <div class="c-info"><label>Dirección</label><span><?= htmlspecialchars($order->getFieldAddress()->getValue()) ?></span></div>
      </div>
      <div class="c-cell">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <div class="c-info"><label>Email</label><span><?= htmlspecialchars($order->getFieldEmail()->getValue()) ?></span></div>
      </div>
    </div>
  </div>

  <!-- Detalle del pedido -->
  <div class="card">
    <div class="sec-title">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
      </svg>
      Detalle del Pedido
    </div>

    <?php foreach ($order->getSelectedPizzas() as $p): ?>
    <div class="order-row">
      <div class="order-icon"><?= $p->getEmoji() ?></div>
      <div class="order-info">
        <strong><?= htmlspecialchars($p->getName()) ?></strong>
        <small>1 x <?= $p->getFormattedPrice() ?></small>
      </div>
      <span class="order-total"><?= $p->getFormattedPrice() ?></span>
    </div>
    <?php endforeach; ?>

    <div class="totals-box">
      <div class="t-row"><span>Subtotal:</span><span><?= $order->fmt($order->getSubtotal()) ?></span></div>
      <div class="t-row"><span>IVA (19%):</span><span><?= $order->fmt($order->getIVA()) ?></span></div>
      <div class="t-final"><span>Total a Pagar:</span><span><?= $order->fmt($order->getTotal()) ?></span></div>
    </div>

    <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn-new">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      Realizar Nuevo Pedido
    </a>

    <p class="delivery-note">⏱️ Tiempo estimado de entrega: 30-45 minutos 🚀</p>
  </div>

</div>

<?php else: ?>
<!-- ╔═══════════════════════════════╗
     ║         FORMULARIO            ║
     ╚═══════════════════════════════╝ -->

<!-- HEADER -->
<div class="header">
  <div class="header-icon">
    <svg width="34" height="34" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M32 6 L58 54 L6 54 Z" stroke="#FF2D78" stroke-width="3.5"
            stroke-linejoin="round" fill="none"/>
      <circle cx="22" cy="40" r="3.5" fill="#FF2D78"/>
      <circle cx="40" cy="34" r="3.5" fill="#FF2D78"/>
      <circle cx="30" cy="48" r="2.5" fill="#FF2D78"/>
    </svg>
  </div>
  <h1>Pizzas Online</h1>
  <p>✨ Pedidos en línea con estilo ✨</p>
</div>

<div class="layout">

  <!-- ── COLUMNA IZQUIERDA ── -->
  <div>

    <?php if ($submitted && $order->hasAnyError()): ?>
    <div class="alert-global">
      ⚠️ Revisa los campos marcados — hay errores que debes corregir antes de continuar.
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" novalidate>

      <!-- Datos del cliente -->
      <div class="card">
        <div class="sec-title">
          <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Datos del Cliente
        </div>

        <?php echo $order->getFieldName()->render(); ?>
        <?php echo $order->getFieldAddress()->render(); ?>
        <div class="grid-2">
          <?php echo $order->getFieldPhone()->render(); ?>
          <?php echo $order->getFieldEmail()->render(); ?>
        </div>
      </div>

      <!-- Selecciona tus pizzas -->
      <div class="card" style="margin-top:20px">
        <div class="sec-title">
          <svg width="22" height="22" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M32 6 L58 54 L6 54 Z" stroke="#FF2D78" stroke-width="3.5" stroke-linejoin="round" fill="none"/>
            <circle cx="22" cy="40" r="3" fill="#FF2D78"/>
            <circle cx="40" cy="34" r="3" fill="#FF2D78"/>
          </svg>
          Selecciona tus Pizzas
        </div>

        <?php if ($submitted && in_array('pizza_required', $order->getErrors())): ?>
        <div class="no-pizza-err">❌ Debes seleccionar al menos una pizza para confirmar tu pedido.</div>
        <?php endif; ?>

        <?php foreach ($catalog as $pizza):
          $sel = in_array($pizza->getId(), $order->getSelectedIds());
        ?>
        <div class="pizza-card <?= $sel ? 'selected' : '' ?>" id="wrap-<?= $pizza->getId() ?>">
          <input type="checkbox" name="pizzas[]" value="<?= $pizza->getId() ?>"
                 id="cb-<?= $pizza->getId() ?>" <?= $sel ? 'checked' : '' ?>>
          <div class="pizza-chk" onclick="toggleFromChk('<?= $pizza->getId() ?>')">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="3.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <span class="pizza-emoji"><?= $pizza->getEmoji() ?></span>
          <div class="pizza-info">
            <div class="pizza-name"><?= htmlspecialchars($pizza->getName()) ?></div>
            <div class="pizza-price"><?= $pizza->getFormattedPrice() ?></div>
          </div>
          <div class="qty-controls">
            <button type="button" class="qty-btn" onclick="changeQty('<?= $pizza->getId() ?>', -1)">−</button>
            <span class="qty-num" id="qnum-<?= $pizza->getId() ?>"><?= $sel ? 1 : 0 ?></span>
            <button type="button" class="qty-btn qty-plus" onclick="changeQty('<?= $pizza->getId() ?>', 1)">+</button>
          </div>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="btn-confirm">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184
                     1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          Confirmar Pedido
        </button>
      </div>

    </form>
  </div>

  <!-- ── SIDEBAR RESUMEN ── -->
  <div class="sidebar">
    <div class="card">
      <div class="sec-title">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Resumen en Tiempo Real
      </div>

      <div id="sidebar-empty" class="summary-empty">
        <svg width="52" height="52" viewBox="0 0 64 64" fill="none">
          <path d="M32 6 L58 54 L6 54 Z" stroke="#D4BBCC" stroke-width="3" fill="none"/>
          <circle cx="22" cy="40" r="3" fill="#D4BBCC"/>
          <circle cx="40" cy="34" r="3" fill="#D4BBCC"/>
        </svg>
        <p>Selecciona tus pizzas</p>
      </div>
      <div id="sidebar-items"></div>
      <div id="sidebar-totals" class="s-totals" style="display:none">
        <div class="s-row"><span>Subtotal</span><span id="t-sub"></span></div>
        <div class="s-row"><span>IVA 19%</span><span id="t-iva"></span></div>
        <div class="s-total"><span>Total</span><span id="t-total"></span></div>
      </div>
    </div>
  </div>

</div><!-- /layout -->
<?php endif; ?>

<!-- ── LOADING OVERLAY ── -->
<div id="loading-overlay">
  <div id="loading-state">
    <div class="loading-pizza">🍕</div>
    <div class="loading-dots">
      <div class="loading-dot"></div>
      <div class="loading-dot"></div>
      <div class="loading-dot"></div>
    </div>
    <div class="loading-label">Procesando tu pedido...</div>
  </div>
  <div id="encamino-screen">
    <div class="encamino-moto">🛵</div>
    <div class="encamino-title">¡Tu pedido va en camino!</div>
    <div class="encamino-sub">Pronto llegará a tu puerta 🍕✨</div>
  </div>
</div>

<script>
// Catálogo para JS
const CATALOG = <?= json_encode(array_map(fn($p) => [
    'id'    => $p->getId(),
    'emoji' => $p->getEmoji(),
    'name'  => $p->getName(),
    'price' => $p->getPrice(),
], $catalog)) ?>;

// Mapa de cantidades por id de pizza
const QTY = {};
CATALOG.forEach(p => { QTY[p.id] = 0; });

// ── Inicializar cantidades desde checkboxes pre-seleccionados ─────
document.querySelectorAll('input[name="pizzas[]"]:checked').forEach(cb => {
    QTY[cb.value] = 1;
});

// ── Cambiar cantidad ──────────────────────────────────────────────
function changeQty(id, delta) {
    const current = QTY[id] || 0;
    const next    = Math.max(0, current + delta);
    QTY[id] = next;

    const numEl = document.getElementById('qnum-' + id);
    if (numEl) numEl.textContent = next;

    const cb   = document.getElementById('cb-' + id);
    const card = document.getElementById('wrap-' + id);
    if (next > 0) {
        cb.checked = true;
        card.classList.add('selected');
    } else {
        cb.checked = false;
        card.classList.remove('selected');
    }
    updateSummary();
}

// ── Toggle desde el checkmark ─────────────────────────────────────
function toggleFromChk(id) {
    const cb = document.getElementById('cb-' + id);
    if (cb.checked) {
        changeQty(id, -QTY[id]); // poner en 0
    } else {
        changeQty(id, 1);         // activar con 1
    }
}

// ── Formatear precio ──────────────────────────────────────────────
function fmt(n) {
    return '$ ' + n.toLocaleString('es-CO');
}

// ── Actualizar resumen lateral ────────────────────────────────────
function updateSummary() {
    const ids = [...document.querySelectorAll('input[name="pizzas[]"]:checked')].map(c => c.value);
    const emptyEl  = document.getElementById('sidebar-empty');
    const itemsEl  = document.getElementById('sidebar-items');
    const totalsEl = document.getElementById('sidebar-totals');
    if (!itemsEl) return;

    if (!ids.length) {
        emptyEl.style.display  = '';
        itemsEl.innerHTML      = '';
        totalsEl.style.display = 'none';
        return;
    }
    emptyEl.style.display  = 'none';
    totalsEl.style.display = '';

    let sub = 0, html = '';
    ids.forEach(id => {
        const p   = CATALOG.find(c => c.id === id);
        const qty = QTY[id] || 1;
        if (!p) return;
        sub += p.price * qty;
        html += `<div class="s-item">
            <span class="s-emoji">${p.emoji}</span>
            <span class="s-name">${p.name}${qty > 1 ? ' <strong>x'+qty+'</strong>' : ''}</span>
            <span class="s-price">${fmt(p.price * qty)}</span>
        </div>`;
    });
    itemsEl.innerHTML = html;

    const iva   = Math.round(sub * 0.19);
    const total = sub + iva;
    document.getElementById('t-sub').textContent   = fmt(sub);
    document.getElementById('t-iva').textContent   = fmt(iva);
    document.getElementById('t-total').textContent = fmt(total);
}

// ── Loading overlay al confirmar ──────────────────────────────────
function showLoading(form) {
    const overlay   = document.getElementById('loading-overlay');
    const loadState = document.getElementById('loading-state');
    const enCamino  = document.getElementById('encamino-screen');

    overlay.classList.add('active');

    // Después de 2.2s → mostrar "en camino"
    setTimeout(() => {
        loadState.style.display = 'none';
        enCamino.style.display  = 'flex';

        // Después de 1.3s → enviar el form de verdad
        setTimeout(() => {
            form.submit();
        }, 1300);
    }, 2200);
}

// ── Validación live ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    const nameEl = document.getElementById('name');
    if (nameEl) {
        nameEl.addEventListener('input', () => {
            const prev = nameEl.value;
            nameEl.value = prev.replace(/[0-9]/g, '');
            if (prev !== nameEl.value)
                showHint(nameEl, '⚠️ El nombre no puede contener números.');
            else clearHint(nameEl);
        });
    }

    const phoneEl = document.getElementById('phone');
    if (phoneEl) {
        phoneEl.addEventListener('input', () => {
            const prev = phoneEl.value;
            phoneEl.value = prev.replace(/[A-Za-z]/g, '');
            if (prev !== phoneEl.value)
                showHint(phoneEl, '⚠️ El teléfono solo acepta números.');
            else clearHint(phoneEl);
        });
    }

    const emailEl = document.getElementById('email');
    if (emailEl) {
        emailEl.addEventListener('blur', () => {
            if (emailEl.value && !emailEl.value.includes('@'))
                showHint(emailEl, '⚠️ El correo debe incluir @. Ej: nombre@gmail.com');
            else clearHint(emailEl);
        });
        emailEl.addEventListener('input', () => {
            if (emailEl.value.includes('@')) clearHint(emailEl);
        });
    }

    // Interceptar submit del formulario para mostrar loading
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const hasPizza = [...document.querySelectorAll('input[name="pizzas[]"]:checked')].length > 0;
            if (!hasPizza) return; // dejar que PHP valide
            e.preventDefault();
            document.querySelector('.btn-confirm').disabled = true;
            showLoading(form);
        });
    }

    updateSummary();
});

function showHint(input, msg) {
    const wrap = input.closest('.input-wrap');
    wrap.classList.add('has-error');
    let h = wrap.querySelector('.live-hint');
    if (!h) {
        h = document.createElement('div');
        h.className = 'err-msg live-hint';
        wrap.appendChild(h);
    }
    h.textContent = msg;
}

function clearHint(input) {
    const wrap = input.closest('.input-wrap');
    wrap.classList.remove('has-error');
    const h = wrap.querySelector('.live-hint');
    if (h) h.remove();
}
</script>
</body>
</html>
