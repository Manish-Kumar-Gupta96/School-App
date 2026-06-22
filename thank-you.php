<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You | VIC School Admissions</title>
    <!-- Bootstrap (Local) -->
    <link rel="stylesheet" href="assets/css/libs/bootstrap.min.css">
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="assets/css/libs/fa/all.min.css">
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f4f6fb 0%, #e5e9f2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .thank-you-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: #e0f2fe;
            color: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 24px;
            font-size: 2rem;
            animation: scaleUp 0.6s ease-out;
        }
        @keyframes scaleUp {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="thank-you-card">
        <div class="icon-circle">
            <i class="fa fa-envelope-open-text"></i>
        </div>
        <h2 class="fw-bold text-dark mb-2">Enquiry Received!</h2>
        <p class="text-muted mb-4">Thank you for showing interest in VIC School. Our admissions team has registered your lead and will contact you shortly.</p>
        <a href="index.php" class="btn btn-primary px-4 py-2" style="border-radius: 8px;">
            <i class="fa fa-home me-1"></i> Back to Homepage
        </a>
    </div>
</body>
</html>
