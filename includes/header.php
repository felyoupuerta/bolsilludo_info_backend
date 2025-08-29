<?php
// Detectar si estamos en una subcarpeta
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$basePath = $isInSubfolder ? '../' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title . ' - ' : ''; ?>BOLSILLUDO_INFO - Club Nacional de Football</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>static/style.css">
    <link rel="icon" href="static/images/Logo.png">    
    <style>
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-menu li {
            margin: 0;
        }
        
        .nav-btn {
            color: #333;
            background: black;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
            display: inline-block;
            white-space: nowrap;
        }
        
        .nav-btn:hover {
            background: linear-gradient(135deg, #e9ecef, #ced4da);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            color: #333;
            text-decoration: none;
        }
        
        .nav-btn.admin {
            background: linear-gradient(135deg, #ffc107, #ffb300);
            color: #000;
        }
        
        .nav-btn.admin:hover {
            background: linear-gradient(135deg, #ffb300, #ffa000);
            color: #000;
        }
        
        .nav-btn.logout {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .nav-btn.logout:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            color: white;
        }
        
        .nav-btn.login {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .nav-btn.login:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            color: white;
        }
        
        .nav-btn.register {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .nav-btn.register:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
            color: white;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .nav-btn {
                font-size: 12px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>BOLSILLUDO_INFO</h1>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="<?php echo $basePath; ?>index.php" class="nav-btn">Inicio</a></li>
                        <li><a href="<?php echo $basePath; ?>index.php#fixture" class="nav-btn">Fixture</a></li>
                        <li><a href="<?php echo $basePath; ?>index.php#noticias" class="nav-btn">Noticias</a></li>
                        <li><a href="<?php echo $basePath; ?>index.php#galeria" class="nav-btn">Galería</a></li>
                        <li><a href="<?php echo $basePath; ?>index.php#historia" class="nav-btn">Historia</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><a href="<?php echo $basePath; ?>admin/index.php" class="nav-btn admin">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo $basePath; ?>logout.php" class="nav-btn logout">Cerrar Sesión (<?php echo $_SESSION['username']; ?>)</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo $basePath; ?>login.php" class="nav-btn login">Iniciar Sesión</a></li>
                            <li><a href="<?php echo $basePath; ?>register.php" class="nav-btn register">Registrarse</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php 
        require_once $basePath . 'includes/flash.php';
        displayFlashMessages();
        ?>
    </div>
