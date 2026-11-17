<?php
  include('db.php');
  include('company_data.php');


    $domain = $_SERVER['HTTP_HOST'];

    // Remove "www." only if it appears at the beginning
    $domain = preg_replace('/^www\./i', '', $domain);
      // echo $domain;
    // Selecting company data for smtp 

    $stmt = $pdo->prepare("SELECT * FROM companies WHERE company_domain = ?");
    $stmt->execute([$domain]);
    $companydata = $stmt->fetch(PDO::FETCH_ASSOC);

    // creating smtp details
    $supportmail = $companydata['company_support_email'];
    $customercare = $companydata['company_customer_care'];
    $companyEmail = $companydata['company_email'];
    $companyMobile = $companydata['company_mobile'];
  
    $support_email_password = $companydata['support_email_password'];
    $customer_care_password = $companydata['customer_care_password'];

    // assigning smtp to sessions
    $_SESSION['supportmail'] = $supportmail;
    $_SESSION['customercare'] = $customercare;
    $_SESSION['company_email'] = $companyEmail;
    $_SESSION['company_mobile'] = $companyMobile;
    $_SESSION['support_email_password'] = $support_email_password;
    $_SESSION['customer_care_password'] = $customer_care_password;

    // echo $_SESSION['supportmail'];

?>