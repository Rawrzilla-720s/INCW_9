<?php
declare(strict_types=1);

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Form values stay short and fixed; these are only what the ticket shows.
$sizeLabels = ['small' => 'Small, 10 inch', 'medium' => 'Medium, 12 inch', 'large' => 'Large, 14 inch'];
$toppingLabels = [
    'mushroom' => 'Mushrooms',
    'olive' => 'Olives',
    'pineapple' => 'Pineapple',
    'onion' => 'Onions',
    'green-pepper' => 'Green bell pepper'
];

$errors = [];
$clean = [];
$practiceComplete = true;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    $errors[] = 'Open order.html and submit the form using POST.';
} else {
    $customer = $_POST['customer'] ?? null;
    if (!is_string($customer) || trim($customer) === ''
        || strlen(trim($customer)) > 40) {
        $errors[] = 'Enter one chef name of 1-40 bytes.';
    } else {
        $clean['customer'] = trim($customer);
    }

    $size = $_POST['size'] ?? null;
    if (!is_string($size) || !in_array($size, ['small', 'medium', 'large'], true)) {
        $errors[] = 'Choose small, medium, or large.';
    } else {
        $clean['size'] = $sizeLabels[$size];
    }

    // Only a single string can be a valid quantity; arrays and missing keys fail here.
    $quantity = $_POST['quantity'] ?? null;
    $quantity = is_string($quantity)
        ? filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]])
        : false;
    if ($quantity === false) {
        $errors[] = 'Choose a whole-number quantity from 1 to 5.';
    } else {
        $clean['quantity'] = $quantity;
    }

    // Unchecked boxes are never sent, so no toppings key just means plain cheese.
    $allowedToppings = array_keys($toppingLabels);
    $toppings = $_POST['toppings'] ?? [];
    $toppingsOk = is_array($toppings);
    if ($toppingsOk) {
        foreach ($toppings as $topping) {
            if (!is_string($topping) || !in_array($topping, $allowedToppings, true)) {
                $toppingsOk = false;
                break;
            }
        }
    }
    if (!$toppingsOk) {
        $errors[] = 'Choose toppings only from the list on the form.';
    } else {
        $clean['toppings'] = [];
        foreach (array_unique($toppings) as $topping) {
            $clean['toppings'][] = $toppingLabels[$topping];
        }
    }

    $notes = $_POST['notes'] ?? '';
    if (!is_string($notes) || strlen(trim($notes)) > 200) {
        $errors[] = 'Enter one kitchen note of 200 bytes or less.';
    } else {
        $clean['notes'] = trim($notes);
    }

    if (!$practiceComplete) {
        $errors[] = 'Learning checkpoint: finish TODOs B-D, then enable TODO E.';
    } elseif ($errors === []) {
        foreach (['customer', 'size', 'quantity', 'toppings', 'notes'] as $key) {
            if (!array_key_exists($key, $clean)) {
                $errors[] = 'Finish the PHP processing block for: ' . $key;
            }
        }
    }
    if ($errors !== []) {
        http_response_code(422);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Campus Crust | Ticket</title>
  <style>
    :root {
      --ground: #f3f3f1;
      --surface: #ffffff;
      --ink: #161616;
      --muted: #555550;
      --field: #0039a6;
      --on-field: #ffffff;
      --accent: #d33a1f;
      --accent-press: #b02c14;
      --accent-tint: #fbe7e2;
      --gutter: clamp(16px, 4vw, 48px);
    }
    * { box-sizing: border-box; }
    body { margin: 0; font: 17px/1.55 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--ground); color: var(--ink); }
    .wrap { max-width: 800px; margin: 0 auto; padding: 0 var(--gutter); }

    .band { background: var(--field); color: var(--on-field); padding: clamp(40px, 7vw, 88px) 0 clamp(32px, 5vw, 56px); }
    h1 { margin: 0; font-size: clamp(2.5rem, 7vw, 5rem); line-height: .98; letter-spacing: -.035em; overflow-wrap: anywhere; }
    .lede { margin: 20px 0 0; max-width: 34em; font-size: 1.1875rem; }

    main { padding-top: clamp(40px, 6vw, 72px); padding-bottom: 120px; }
    h2 { margin: 0 0 16px; font-size: 1.25rem; }

    .ticket { display: grid; gap: 28px; padding: clamp(24px, 4vw, 40px); background: var(--surface); }
    .count { margin: 0; font-size: clamp(2rem, 4.5vw, 3.25rem); font-weight: 700; line-height: 1.05; letter-spacing: -.02em; }
    .lines { margin: 0; display: grid; gap: 14px; }
    .lines div { display: grid; grid-template-columns: 8.5rem minmax(0, 1fr); gap: 12px; }
    .lines dt { color: var(--muted); }
    .lines dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }

    .problems { padding: clamp(24px, 4vw, 48px); background: var(--accent-tint); }
    .problems ul { margin: 0; padding-left: 1.2em; }
    .problems li { margin: 8px 0; overflow-wrap: anywhere; }

    .after { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px 32px; margin-top: 32px; }
    .back { display: inline-block; padding: 16px 28px; font-weight: 650; color: #ffffff; background: var(--accent); text-decoration: none; transition: background-color .15s, transform .1s; }
    .back:hover { background: var(--accent-press); }
    .back:active { transform: translateY(1px); }
    .back:focus-visible { outline: 3px solid var(--field); outline-offset: 2px; }

    @media (max-width: 600px) {
      .lines div { grid-template-columns: 1fr; gap: 0; }
      .back { display: block; text-align: center; }
      main { padding-bottom: 72px; }
    }
    @media (prefers-reduced-motion: reduce) {
      .back { transition: none; }
    }
  </style>
</head>
<body>
<?php if ($errors !== []): ?>
  <header class="band">
    <div class="wrap">
      <h1>Almost ready, Chef!</h1>
      <p class="lede">The kitchen could not read this ticket yet. Fix the items below and send it again.</p>
    </div>
  </header>
  <main class="wrap">
    <div class="problems" role="alert">
      <h2>What needs fixing</h2>
      <ul>
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="after">
      <a class="back" href="order.html">Back to the pizza form</a>
    </div>
  </main>
<?php else: ?>
  <header class="band">
    <div class="wrap">
      <h1>Order up, Chef <?= e($clean['customer']) ?>.</h1>
      <p class="lede">Your ticket is ready.</p>
    </div>
  </header>
  <main class="wrap">
    <section class="ticket" aria-labelledby="ticket-title">
      <div>
        <h2 id="ticket-title">Ticket</h2>
        <p class="count"><?= e((string) $clean['quantity']) ?> &times; <?= e($clean['size']) ?></p>
      </div>
      <dl class="lines">
      <?php
      $labels = [
          'customer' => 'Name',
          'size' => 'Pizza size',
          'quantity' => 'Quantity',
          'toppings' => 'Toppings',
          'notes' => 'Kitchen note'
      ];
      foreach ($labels as $key => $label):
          $value = $clean[$key];
          if ($key === 'toppings') {
              $value = $value === [] ? 'Plain cheese' : implode(', ', $value);
          } elseif ($key === 'notes' && $value === '') {
              $value = 'No special requests';
          }
      ?>
        <div><dt><?= e($label) ?></dt><dd><?= e((string) $value) ?></dd></div>
      <?php endforeach; ?>
      </dl>
    </section>
    <div class="after">
      <a class="back" href="order.html">Build another pizza</a>
    </div>
  </main>
<?php endif; ?>
</body>
</html>
