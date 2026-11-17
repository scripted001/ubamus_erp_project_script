



<!-- Sales insert feature, everyother thing in the try -->

/* =========================
   START TRANSACTION
========================= */
try {
    $pdo->beginTransaction();

    

    
   
    $pdo->commit();


} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = addslashes($e->getMessage());
    echo "<script>alert('Error processing sale: {$msg}'); window.history.back();</script>";
    exit();
}





/* DEBT */
    if ($payment_status === 'partial' && $balance > 0) {

        $pdo->prepare("
            INSERT INTO customer_debts
            (company_id, location_id, customer_id, sale_id, amount_due, due_date, status, created_at)
            VALUES (?,?,?,?,?,?,'Pending',NOW())
        ")->execute([
            $company_id,
            $location_id,
            $customer_id,
            $sale_id,
            $balance,
            $due_date

        ]);
    }




<!-- Continuation of create sale.php -->

   
    

    // -------------------------------
    // EMAIL INVOICE
    // -------------------------------
    // $mail = new PHPMailer(true);
    // $mail->isSMTP();
    // $mail->Host = 'smtp.hostinger.com';
    // $mail->SMTPAuth   = true;
    // $mail->Username   = 'support@ubamus.com';
    // $mail->Password   = 'Zaida@25';
    // $mail->SMTPSecure = 'ssl';
    // $mail->Port       = 465;

    // $mail->setFrom('support@ubamus.com', $company['company_name']);
    // $mail->addAddress($customer_email);       // Customer
    // $mail->addAddress($company['admin_email']); // Company admin
    // $mail->Subject = "Invoice $invoice_number";
    // $mail->Body = "Dear {$customer_name},

// Thank you for your patronage.

// Please find attached your invoice (Invoice No: {$invoice_number}) for your recent purchase with {$company['company_name']}.

// We greatly appreciate your support and look forward to serving you again.

// Kind regards,
// {$company['company_name']} Team";
//     $mail->addAttachment($invoice_file);
//     $mail->send();

    // -------------------------------
    // OPEN GENERATED INVOICE PDF IN NEW TAB
    // -------------------------------
    echo "<script>
    alert('Sale completed and invoice emailed successfully!');
    // Open the invoice PDF in a new tab
    window.open('{$invoice_file}', '_blank');
    // Redirect the current tab to daily-sales.php
    window.location.href = 'daily-sales.php';
// </script>";
// exit();

    