<?php
include('config/db.php'); // DB connection

$domain = $_SESSION['domain'];

// echo $domain;


try {
    // Prepare the SQL statement
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE company_domain = :domain_name LIMIT 1");
    
    // Bind the parameter
    $stmt->bindParam(':domain_name', $domain, PDO::PARAM_STR);
    
    // Execute
    $stmt->execute();
    
    // Fetch the result
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        $company_id = $company['id'];
        $company_logo = $company['company_logo'];
        $company_name = $company['company_name'];
        $company_IDname = $company['company_name'];

    } else {
        // echo "Company not found.";
    }

} catch (PDOException $e) {
    echo "Error fetching company ID: " . $e->getMessage();
}

// echo $company_id;
?>
