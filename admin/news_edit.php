 <?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::requireAdmin();

$title = "Editar Noticia";
$errors = [];

// Obtener ID de la noticia
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

// Obtener datos de la noticia
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$news = $result->fetch_assoc();

if (!$news) {
    $_SESSION['flash_error'] = 'Noticia no encontrada';
    header("Location: index.php");
    exit();
}

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
        $stmt = $db->prepare("UPDATE news SET title = ?, content = ?, image_url = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title_input, $content, $image_url, $id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Noticia actualizada exitosamente';
            header("Location: index.php");
            exit();
        } else {
            $errors['general'] = 'Error al actualizar la noticia: ' . $db->error;
        }
    }
}

$db->close();

require_once '../includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="admin-sidebar">
                    <h3>Panel de Admin</h3>
                    <ul class="admin-menu">
                        <li><a href="index.php">Gestión de Noticias</a></li>
                        <li><a href="news_new.php">Nueva Noticia</a></li>
                        <li><a href="news_edit.php?id=<?php echo $id; ?>" class="active">Editar Noticia</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-9">
                <div class="admin-content">
                    <div class="admin-card">
                        <h3>Editar Noticia</h3>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Título</label>
                                <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                       id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($news['title']); ?>" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image_url" class="form-label">URL de la Imagen (opcional)</label>
                                <input type="url" class="form-control" id="image_url" name="image_url" 
                                       value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : htmlspecialchars($news['image_url']); ?>"
                                       placeholder="https://ejemplo.com/imagen.jpg">
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Contenido</label>
                                <textarea class="form-control <?php echo isset($errors['content']) ? 'is-invalid' : ''; ?>" 
                                          id="content" name="content" rows="10" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : htmlspecialchars($news['content']); ?></textarea>
                                <?php if (isset($errors['content'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['content']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Actualizar Noticia</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
