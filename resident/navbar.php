<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Barangay Portal Navigation</title>

  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    /* --- DARK UI THEME --- */
    :root {
        --bg-dark: #0F1115;
        --navbar-bg: rgba(15, 17, 21, 0.95);
        --text-main: #ffffff;
        --text-muted: #9ca3af;
        --accent-color: #3b82f6;
        --border-color: #2d333b;
    }

    body {
        background-color: var(--bg-dark);
        font-family: 'Inter', sans-serif;
        color: var(--text-main);
    }

    /* Navbar Styling */
    .main-header {
        background-color: var(--navbar-bg) !important;
        border-bottom: 1px solid var(--border-color);
        backdrop-filter: blur(10px);
        height: 70px; /* Slightly taller for modern look */
    }

    .navbar-brand {
        display: flex;
        align-items: center;
    }

    .brand-text {
        color: var(--text-main) !important;
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .brand-image {
        height: 40px;
        width: 40px;
        object-fit: cover;
        border: 2px solid var(--accent-color);
        margin-right: 10px;
    }

    /* Navigation Links */
    .navbar-nav .nav-item {
        margin-left: 5px;
    }

    .nav-link {
        color: var(--text-muted) !important;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 8px 16px !important;
        border-radius: 6px;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
    }

    .nav-link:hover {
        color: var(--text-main) !important;
        background-color: rgba(255, 255, 255, 0.05);
    }

    /* Active State (Replaces the red border) */
    .nav-link.active {
        color: var(--accent-color) !important;
        background-color: rgba(59, 130, 246, 0.1);
    }

    /* Mobile Toggler */
    .navbar-toggler {
        border: none;
        padding: 0;
    }
    .navbar-toggler-icon {
        filter: invert(1); /* White icon */
    }
  </style>
</head>
<body>

<div class="wrapper">

  <nav class="main-header navbar navbar-expand-md navbar-dark">
    <div class="container">
      
      <a href="index.php" class="navbar-brand">
        <img src="assets/dist/img/<?= $image ?>" alt="logo" class="brand-image img-circle elevation-3">
        <span class="brand-text">BARANGAY PORTAL</span>
      </a>

      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse order-3" id="navbarCollapse">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a href="index.php" class="nav-link">HOME</a>
            </li>
            <li class="nav-item">
              <a href="ourofficial.php" class="nav-link">OUR OFFICIALS</a>
            </li>
            <li class="nav-item">
              <a href="login.php" class="nav-link active">
                <i class="fas fa-user-alt mr-1"></i> LOGIN
              </a>
            </li>
        </ul>
      </div>

    </div>
  </nav>
  </div>

<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>