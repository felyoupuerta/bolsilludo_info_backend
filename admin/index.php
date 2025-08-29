<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::requireAdmin();

$title = "Panel de Administración";

// Obtener todas las noticias
$db = getDBConnection();
$news_result = $db->query("SELECT news.*, users.username 
                          FROM news 
                          JOIN users ON news.author_id = users.id 
                          ORDER BY news.created_at DESC");
$news = [];
while ($row = $news_result->fetch_assoc()) {
    $news[] = $row;
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
                        <li><a href="index.php" class="active">Gestión de Noticias</a></li>
						<li><a href="panel_usuarios.php" class="active">Gestión de Usuarios</a></li>
                        <li><a href="panel_partidos.php" class="active">Gestión de Partidos y Resultados</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-9">
                <div class="admin-content">
                    <!-- News List -->
                    <div class="admin-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3>Gestión de Noticias</h3>
                            <a href="news_new.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nueva Noticia
                            </a>
                        </div>
                        
                        <?php if (!empty($news)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Autor</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($news as $article): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($article['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo substr(htmlspecialchars($article['content']), 0, 100); ?>...</small>
                                                </td>
                                                <td><?php echo htmlspecialchars($article['username']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($article['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="news_edit.php?id=<?php echo $article['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" 
                                                              action="news_delete.php" 
                                                              style="display: inline-block;"
                                                              onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta noticia?');">
                                                            <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                                <h5>No hay noticias</h5>
                                <p class="text-muted">Comienza creando la primera noticia</p>
                                <a href="news_new.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primera Noticia
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?> 
