<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Factura <?= htmlspecialchars($inv['number']) ?></title>
<style>
  body { margin:0; padding:0; background:#f4f4f5; font-family:'Helvetica Neue',Arial,sans-serif; font-size:14px; color:#1a1a1a; }
  .wrap { max-width:640px; margin:32px auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
  .header { background:#0a0a0a; padding:32px 40px; display:flex; align-items:center; justify-content:space-between; }
  .logo { display:flex; align-items:center; gap:10px; }
  .logo-mark { width:36px; height:36px; flex-shrink:0; }
  .logo-text { font-size:20px; font-weight:800; color:white; letter-spacing:-0.03em; }
  .logo-sub  { font-size:11px; font-weight:400; color:rgba(255,255,255,0.45); letter-spacing:0.04em; text-transform:uppercase; display:block; }
  .inv-label { text-align:right; }
  .inv-label h1 { font-size:28px; font-weight:700; color:white; margin:0; letter-spacing:-0.04em; }
  .inv-label .number { font-size:13px; color:rgba(255,255,255,0.45); margin-top:4px; }
  .status-badge { display:inline-block; margin-top:6px; padding:3px 12px; border-radius:100px; font-size:11px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase; }
  .s-paid      { background:#dcfce7; color:#166534; }
  .s-sent      { background:#dbeafe; color:#1e40af; }
  .s-draft     { background:#f4f4f5; color:#52525b; }
  .s-overdue   { background:#fee2e2; color:#991b1b; }
  .greeting { padding:32px 40px 0; }
  .greeting p { font-size:15px; line-height:1.7; color:#374151; margin:0 0 8px; }
  .parties { display:table; width:100%; border-collapse:collapse; padding:24px 40px; box-sizing:border-box; }
  .party { display:table-cell; width:50%; padding:20px; background:#f9fafb; vertical-align:top; }
  .party:first-child { border-radius:8px 0 0 8px; border-right:1px solid #e5e7eb; }
  .party:last-child  { border-radius:0 8px 8px 0; }
  .party-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af; margin-bottom:10px; }
  .party strong { font-size:15px; font-weight:700; color:#0a0a0a; display:block; margin-bottom:4px; }
  .party p { margin:0; font-size:13px; line-height:1.7; color:#6b7280; }
  .dates { padding:16px 40px; display:flex; gap:32px; border-bottom:1px solid #f3f4f6; }
  .date-item label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#9ca3af; display:block; margin-bottom:3px; }
  .date-item span  { font-size:13px; font-weight:600; color:#1a1a1a; }
  table.lines { width:100%; border-collapse:collapse; margin:0; }
  table.lines thead th { padding:10px 16px; background:#0a0a0a; color:white; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; text-align:left; }
  table.lines thead th:last-child,
  table.lines thead th:nth-child(2),
  table.lines thead th:nth-child(3) { text-align:right; }
  table.lines tbody td { padding:12px 16px; border-bottom:1px solid #f3f4f6; font-size:13px; color:#374151; vertical-align:top; }
  table.lines tbody td:nth-child(2),
  table.lines tbody td:nth-child(3),
  table.lines tbody td:last-child { text-align:right; white-space:nowrap; }
  .totals-wrap { padding:16px 40px; }
  .totals { width:260px; margin-left:auto; border-collapse:collapse; }
  .totals td { padding:5px 0; font-size:13px; }
  .totals td:last-child { text-align:right; font-weight:600; }
  .totals tr.total-row td { padding-top:12px; border-top:2px solid #0a0a0a; font-size:17px; font-weight:700; color:#0a0a0a; }
  .info-blocks { padding:0 40px 8px; display:flex; gap:24px; }
  .info-block { flex:1; background:#f9fafb; border-radius:8px; padding:16px 20px; }
  .info-block h5 { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af; margin:0 0 8px; }
  .info-block p  { font-size:12px; color:#6b7280; line-height:1.7; margin:0; white-space:pre-line; }
  .footer { padding:24px 40px; border-top:1px solid #f3f4f6; text-align:center; }
  .footer p { font-size:12px; color:#9ca3af; margin:0 0 4px; }
  .footer a { color:#0a0a0a; font-weight:600; text-decoration:none; }
</style>
</head>
<body>
<div class="wrap">

  <!-- HEADER -->
  <div class="header">
    <div class="logo">
      <svg class="logo-mark" viewBox="0 0 36 36" fill="none">
        <rect width="36" height="36" rx="8" fill="white" fill-opacity="0.08"/>
        <path d="M8 28L18 8L28 28" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M11.5 22H24.5" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <div>
        <div class="logo-text">AKRA</div>
        <span class="logo-sub">Tech Studio</span>
      </div>
    </div>
    <div class="inv-label">
      <h1><?= $labels['factura'] ?></h1>
      <div class="number"><?= htmlspecialchars($inv['number']) ?></div>
      <span class="status-badge s-<?= $inv['status'] ?>"><?= invoiceStatusLabel($inv['status'])['text'] ?></span>
    </div>
  </div>

  <!-- SALUTACIÓ -->
  <div class="greeting">
    <p><?= htmlspecialchars($labels['greeting']) ?></p>
    <p><?= htmlspecialchars($labels['body']) ?></p>
  </div>

  <!-- PARTS -->
  <div style="padding:20px 40px">
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td style="width:50%;padding:20px;background:#f9fafb;border-radius:8px 0 0 8px;border-right:1px solid #e5e7eb;vertical-align:top">
          <div class="party-label"><?= $labels['de'] ?></div>
          <strong style="font-size:15px;font-weight:700;color:#0a0a0a;display:block;margin-bottom:6px"><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></strong>
          <p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280">
            <?= htmlspecialchars($cfg['invoice_nif'] ?? '') ?><br>
            <?= htmlspecialchars($cfg['invoice_address'] ?? '') ?><br>
            <?= htmlspecialchars($cfg['email'] ?? '') ?>
          </p>
        </td>
        <td style="width:50%;padding:20px;background:#f9fafb;border-radius:0 8px 8px 0;vertical-align:top">
          <div class="party-label"><?= $labels['para'] ?></div>
          <strong style="font-size:15px;font-weight:700;color:#0a0a0a;display:block;margin-bottom:6px"><?= htmlspecialchars($client['name']) ?></strong>
          <p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280">
            <?php if (!empty($client['company'])): ?><?= htmlspecialchars($client['company']) ?><br><?php endif; ?>
            <?php if (!empty($client['nif'])): ?><?= htmlspecialchars($client['nif']) ?><br><?php endif; ?>
            <?php if (!empty($client['address'])): ?><?= htmlspecialchars($client['address']) ?><br><?php endif; ?>
            <?php if (!empty($client['city'])): ?><?= htmlspecialchars(trim($client['cp'].' '.$client['city'])) ?><br><?php endif; ?>
            <?= htmlspecialchars($client['email'] ?? '') ?>
          </p>
        </td>
      </tr>
    </table>
  </div>

  <!-- DATES -->
  <div class="dates">
    <div class="date-item">
      <label><?= $labels['fecha'] ?></label>
      <span><?= date('d/m/Y', strtotime($inv['date'])) ?></span>
    </div>
    <?php if (!empty($inv['due_date'])): ?>
    <div class="date-item">
      <label><?= $labels['venc'] ?></label>
      <span><?= date('d/m/Y', strtotime($inv['due_date'])) ?></span>
    </div>
    <?php endif; ?>
  </div>

  <!-- LÍNIES -->
  <table class="lines">
    <thead>
      <tr>
        <th><?= $labels['desc'] ?></th>
        <th><?= $labels['uds'] ?></th>
        <th><?= $labels['precio'] ?></th>
        <th><?= $labels['total'] ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($inv['lines'] as $line): ?>
      <tr>
        <td><?= nl2br(htmlspecialchars($line['desc'])) ?></td>
        <td style="text-align:right"><?= number_format($line['qty'], $line['qty']==intval($line['qty'])?0:2) ?></td>
        <td style="text-align:right"><?= number_format($line['price'],2,',','.') ?> €</td>
        <td style="text-align:right;font-weight:600"><?= number_format($line['qty']*$line['price'],2,',','.') ?> €</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- TOTALS -->
  <div class="totals-wrap">
    <table class="totals">
      <tr><td style="color:#6b7280"><?= $labels['base'] ?></td><td><?= number_format($t['subtotal'],2,',','.') ?> €</td></tr>
      <?php if ($t['tax'] > 0): ?>
      <tr><td style="color:#6b7280"><?= $labels['iva'] ?> (<?= $t['tax_pct'] ?>%)</td><td><?= number_format($t['tax'],2,',','.') ?> €</td></tr>
      <?php endif; ?>
      <?php if ($t['irpf'] > 0): ?>
      <tr><td style="color:#dc2626"><?= $labels['irpf'] ?> (–<?= $t['irpf_pct'] ?>%)</td><td style="color:#dc2626">–<?= number_format($t['irpf'],2,',','.') ?> €</td></tr>
      <?php endif; ?>
      <tr class="total-row"><td><?= $labels['total_f'] ?></td><td><?= number_format($t['total'],2,',','.') ?> €</td></tr>
    </table>
  </div>

  <!-- PAGAR ARA (opcional, si hi ha enllaç configurat) -->
  <?php if (!empty($cfg['payment_link'])): ?>
  <div style="padding:0 40px 24px;text-align:center">
    <a href="<?= htmlspecialchars($cfg['payment_link']) ?>" style="display:inline-block;background:#0a0a0a;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:14px 32px;border-radius:8px">
      <?= $lang === 'es' ? '💳 Pagar ahora' : '💳 Pagar ara' ?>
    </a>
  </div>
  <?php endif; ?>

  <!-- PAGAMENT / NOTES -->
  <?php if (!empty($inv['payment_info']) || !empty($inv['notes'])): ?>
  <div class="info-blocks">
    <?php if (!empty($inv['payment_info'])): ?>
    <div class="info-block">
      <h5><?= $labels['pagament'] ?></h5>
      <p><?= htmlspecialchars($inv['payment_info']) ?></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($inv['notes'])): ?>
    <div class="info-block">
      <h5><?= $labels['obs'] ?></h5>
      <p><?= htmlspecialchars($inv['notes']) ?></p>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- FOOTER -->
  <div class="footer">
    <p><?= $labels['greetings'] ?></p>
    <p><strong><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></strong></p>
    <p><a href="<?= htmlspecialchars($cfg['site_url'] ?? 'https://akratechstudio.es') ?>"><?= htmlspecialchars($cfg['site_url'] ?? 'akratechstudio.es') ?></a> · <?= htmlspecialchars($cfg['email'] ?? '') ?> · <?= htmlspecialchars($cfg['phone'] ?? '') ?></p>
  </div>

</div>
</body>
</html>
