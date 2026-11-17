<?php
  include('../config/db.php');
  include('../config/validation.php');
  include('user_data.php');
  include('../app_data.php');
  include('../company_data.php');

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        
        $production_id = $_POST['production_id'];

        // Fetch existing staff data
        $stmt = $pdo->prepare("SELECT * FROM stock_production WHERE id = ?");
        $stmt->execute([$production_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
           
        $_SESSION['production_id'] = $existing['id'];
        // echo $_SESSION['production_id'];
        header("location: finish_production.php");
        exit();

    }else{
        echo "<script>alert('Error sending production identity to the database.'); window.location.href = 'existing_production.php';</script>";
        exit;
    }

  




?>