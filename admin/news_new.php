 <?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::requireAdmin();

$title = "Nueva Noticia";
$errors = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title_input = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image_url = trim($_POST['image_url']);
    
    // Validaciones
    if (empty($title_input)) {
        $errors['title'] = 'El título es obligatorio';
    }
    
    if (empty($content)) {
        $errors['content'] = 'El contenido es obligatorio';
    }
    
    if (empty($errors)) {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO news (title, content, image_url, author_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title_input, $content, $image_url, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Noticia creada exitosamente';
            header("Location: index.php");
            exit();
        } else {
            $errors['general'] = 'Error al crear la noticia: ' . $db->error;
        }
        
        $db->close();
    }
}

require_once '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="admin-sidebar">
                    <h3>Panel de Admin</h3>
					<h3>Panel de Usuarios</h3>
                    <ul class="admin-menu">
                        <li><a href="index.php">Gestión de Noticias</a></li>
                        <li><a href="news_new.php" class="active">Nueva Noticia</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-9">
                <div class="admin-content">
                    <div class="admin-card">
                        <h3>Crear Nueva Noticia</h3>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Título</label>
                                <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                       id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image_url" class="form-label">URL de la Imagen (opcional)</label>
                                <input type="url" class="form-control" id="image_url" name="image_url" 
                                       value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>"
                                       placeholder="https://ejemplo.com/imagen.jpg">
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Contenido</label>
                                <textarea class="form-control <?php echo isset($errors['content']) ? 'is-invalid' : ''; ?>" 
                                          id="content" name="content" rows="10" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                                <?php if (isset($errors['content'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['content']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Crear Noticia</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
