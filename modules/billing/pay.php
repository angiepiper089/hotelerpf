<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk','Finance']);

$invoiceId = (int) ($_POST['invoice_id'] ?? 0);
$amount = (float) ($_POST['amount'] ?? 0);
$method = $_POST['method'] ?? 'Cash';

$stmt = $pdo->prepare('SELECT * FROM Invoices WHERE InvoiceID = ?');
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) { flash('error', 'Invoice not found.'); redirectTo('list.php'); }
if (!in_array($method, ['Cash','Card','BankTransfer'], true)) $method = 'Cash';

$stmtPaid = $pdo->prepare('SELECT COALESCE(SUM(Amount),0) s FROM Payments WHERE InvoiceID = ?');
$stmtPaid->execute([$invoiceId]);
$paidSoFar = (float) $stmtPaid->fetch()['s'];
$balance = round($invoice['TotalAmount'] - $paidSoFar, 2);

if ($amount <= 0 || $amount > $balance + 0.01) {
    flash('error', 'Invalid payment amount.');
    redirectTo('invoice.php?id=' . $invoiceId);
}

$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO Payments (InvoiceID, Amount, Method, ReceivedBy) VALUES (?, ?, ?, ?)')
        ->execute([$invoiceId, $amount, $method, $_SESSION['user_id']]);

    $newPaid = $paidSoFar + $amount;
    $newStatus = $newPaid >= (float) $invoice['TotalAmount'] - 0.01 ? 'Paid' : 'Partial';
    $pdo->prepare('UPDATE Invoices SET Status = ? WHERE InvoiceID = ?')->execute([$newStatus, $invoiceId]);

    logAudit($pdo, 'PAYMENT', 'Invoices', $invoiceId, "Payment of $amount ($method) recorded, invoice status: $newStatus");
    $pdo->commit();
    flash('success', 'Payment recorded successfully.');
} catch (Exception $e) {
    $pdo->rollBack();
    flash('error', 'Failed to record payment: ' . $e->getMessage());
}

redirectTo('invoice.php?id=' . $invoiceId);
