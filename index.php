<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Incluir modelos necesarios para like y comentarios
require_once 'models/Like.php';
require_once 'models/Comment.php';

$title = "BOLSILLUDO_INFO - Club Nacional de Football";

$news = [];
$fixturesByCompetition = [];

// Cargar noticias directamente desde la BD
$db = getDBConnection();
if ($db) {
    $sql = "SELECT n.id, n.title, n.content, n.created_at, n.image_url, u.username
            FROM news n
            JOIN users u ON n.author_id = u.id
            ORDER BY n.created_at DESC
            LIMIT 6";

    if ($stmt = $db->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        $news = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Cargar fixtures directamente desde la BD
if ($db) {
    $sql = "SELECT competicion, jornada, fecha_partido, equipo_local, equipo_visitante,
                   goles_local, goles_visitante, penales_local, penales_visitante, estado, resultado
            FROM fixtures
            ORDER BY
                FIELD(competicion, 'Torneo Apertura', 'Copa Libertadores', 'Copa Uruguay', 'Torneo Clausura', 'Torneo Intermedio'),
                CASE
                    WHEN estado = 'Pendiente' THEN 1
                    WHEN estado = 'Finalizado' THEN 2
                    ELSE 3
                END,
                fecha_partido ASC";

    if ($stmt = $db->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        $fixtures = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Organizar fixtures por competición
        foreach ($fixtures as $fixture) {
            $competition = $fixture['competicion'];
            if (!isset($fixturesByCompetition[$competition])) {
                $fixturesByCompetition[$competition] = [];
            }
            $fixturesByCompetition[$competition][] = $fixture;
        }
    }

    $db->close();
}

require_once 'includes/header.php';
?>

<!-- Añadir estilos para like y comentarios -->
<style>
    /* Estilos para like y comentarios */
    .like-btn {
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 20px;
        transition: all 0.3s ease;
        margin: 10px 0;
    }

    .like-btn:hover {
        background-color: #f0f0f0;
    }

    .heart {
        font-size: 1.2em;
        transition: all 0.3s ease;
    }

    .heart.liked {
        color: #e74c3c;
        animation: pulse 0.5s;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }

    .like-count {
        font-weight: bold;
    }

    .comentarios {
        margin-top: 15px;
        border-top: 1px solid #eee;
        padding-top: 15px;
    }

    .comentarios h4 {
        margin-bottom: 10px;
        font-size: 1.1em;
    }

    .comment-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 15px;
    }

    .comment-form textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
        min-height: 60px;
    }

    .comment-form button {
        align-self: flex-end;
        padding: 8px 16px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .comment-form button:hover {
        background-color: #0056b3;
    }

    .comentarios-lista {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .comentarios-lista li {
        padding: 10px;
        border-bottom: 1px solid #f0f0f0;
    }

    .comentarios-lista li:last-child {
        border-bottom: none;
    }

    .fecha-comentario {
        color: #888;
        font-size: 0.85em;
    }

    .no-comentarios {
        color: #888;
        font-style: italic;
    }

    /* Estilos para el sistema de acordeón de noticias */
    .noticia-card {
        position: relative;
        overflow: hidden;
    }

    .noticia-content p.more-content {
        display: none;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.4s ease, transform 0.4s ease;
    }

    .noticia-card.expanded .noticia-content p.more-content {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    .leer-mas, .leer-menos {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
        padding: 10px 20px;
        border: 2px solid #007bff;
        border-radius: 25px;
        transition: all 0.3s ease;
        background: transparent;
        width: fit-content;
        margin-top: 15px;
        cursor: pointer;
    }

    .leer-menos {
        display: none;
        background: #007bff;
        color: white;
    }

    .noticia-card.expanded .leer-mas {
        display: none;
    }

    .noticia-card.expanded .leer-menos {
        display: inline-flex;
    }

    .leer-mas:hover {
        background: #007bff;
        color: white;
        transform: translateX(5px);
    }

    .leer-menos:hover {
        background: #0056b3;
        border-color: #0056b3;
        transform: translateX(-5px);
    }

    .leer-mas i, .leer-menos i {
        transition: transform 0.3s ease;
    }

    .leer-mas:hover i {
        transform: translateX(5px);
    }

    .leer-menos:hover i {
        transform: translateX(-5px);
    }

    /* Estilos mejorados para la sección de noticias */
    .noticias {
        padding: 80px 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .noticias h2 {
        text-align: center;
        font-size: 2.5em;
        margin-bottom: 50px;
        color: #333;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }

    .noticias-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        padding: 20px 0;
    }

    .noticia-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .noticia-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .noticia-img-container {
        position: relative;
        width: 100%;
        aspect-ratio: 16/9;
        overflow: hidden;
    }

    .noticia-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.3s ease;
    }

    .noticia-card:hover .noticia-img {
        transform: scale(1.05);
    }

    .noticia-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .noticia-card:hover .noticia-overlay {
        opacity: 1;
    }

    .noticia-overlay i {
        color: white;
        font-size: 2.5em;
        animation: bounce 2s infinite;
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }

    .noticia-content {
        padding: 25px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .fecha-noticia {
        display: inline-block;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 500;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        width: fit-content;
    }

    .noticia-content h3 {
        font-size: 1.4em;
        margin-bottom: 15px;
        color: #333;
        line-height: 1.3;
        font-weight: 700;
        transition: color 0.3s ease;
    }

    .noticia-card:hover .noticia-content h3 {
        color: #007bff;
    }

    .noticia-content p {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
        font-size: 0.95em;
    }

    /* Responsive para noticias */
    @media (max-width: 768px) {
        .noticias-grid {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 0 10px;
        }

        .noticias h2 {
            font-size: 2em;
            margin-bottom: 30px;
        }

        .noticia-img-container {
            aspect-ratio: 16/10;
        }

        .noticia-content {
            padding: 20px;
        }

        .noticia-content h3 {
            font-size: 1.2em;
        }
    }

    @media (max-width: 480px) {
        .noticias {
            padding: 40px 0;
        }

        .noticias-grid {
            grid-template-columns: 1fr;
            gap: 15px;
            padding: 0 5px;
        }

        .noticia-img-container {
            aspect-ratio: 4/3;
        }

        .noticia-content {
            padding: 15px;
        }

        .noticia-content h3 {
            font-size: 1.1em;
        }
        
        .leer-mas, .leer-menos {
            padding: 8px 16px;
            font-size: 0.9em;
        }
    }

    /* Estilos para el mensaje de no hay noticias */
    .col-12 {
        grid-column: 1 / -1;
    }

    .text-center {
        text-align: center;
    }

    .text-muted {
        color: #6c757d;
        font-size: 1.1em;
        margin-bottom: 20px;
    }

    .btn {
        display: inline-block;
        padding: 12px 24px;
        background: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 25px;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .btn:hover {
        background: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,123,255,0.3);
    }

    /* Estilos mejorados para la galería */
    .galeria {
        padding: 80px 0;
        background: linear-gradient(135deg, 'includes/config.php';
        $host = 'localhost';
        $dbname = 'bolsilludo_info';
        $username = 'root';
        $password = '';
        
        try {
            $db = new mysqli($host, $username, $password, $dbname);
            if ($db->connect_error) {
                throw new Exception("Error de conexión: " . $db->connect_error);
            }
            return $db;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    ?>
    
    <!-- Resto del código CSS se mantiene igual -->
</style>

<!-- Portada -->
<section class="portada">
    <div class="container">
        <div class="portada-content">
            <h2>CLUB NACIONAL DE FOOTBALL</h2>
            <p>El Tricolor de América</p>
        </div>
    </div>
</section>

<!-- Noticias -->
<section class="noticias">
    <div class="container">
        <h2>Últimas Noticias</h2>
        <div class="noticias-grid">
            <?php if (!empty($news)): ?>
                <?php foreach ($news as $noticia): ?>
                    <div class="noticia-card" id="noticia-<?php echo $noticia['id']; ?>">
                        <div class="noticia-img-container">
                            <?php if (!empty($noticia['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($noticia['image_url']); ?>" alt="<?php echo htmlspecialchars($noticia['title']); ?>" class="noticia-img">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1577223625816-7546f13df25d?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" alt="Imagen por defecto" class="noticia-img">
                            <?php endif; ?>
                            <div class="noticia-overlay">
                                <i class="fas fa-newspaper"></i>
                            </div>
                        </div>

                        <div class="noticia-content">
                            <span class="fecha-noticia"><?php echo date('d F, Y', strtotime($noticia['created_at'])); ?></span>
                            <h3><?php echo htmlspecialchars($noticia['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($noticia['content'], 0, 150)); ?><?php echo strlen($noticia['content']) > 150 ? '...' : ''; ?></p>

                            <?php if (strlen($noticia['content']) > 150): ?>
                                <p class="more-content"><?php echo htmlspecialchars(substr($noticia['content'], 150)); ?></p>
                                <button class="leer-mas" data-id="<?php echo $noticia['id']; ?>">
                                    Leer más <i class="fas fa-arrow-down"></i>
                                </button>
                                <button class="leer-menos" data-id="<?php echo $noticia['id']; ?>">
                                    Leer menos <i class="fas fa-arrow-up"></i>
                                </button>
                            <?php endif; ?>

                            <!-- Botón de Like con corazón -->
                            <?php
                                // Obtener información de likes usando el modelo Like
                                $likesCount = Like::getLikesCount($noticia['id']);
                                $userLiked = false;
                                
                                if (isset($_SESSION['user_id'])) {
                                    $userLiked = Like::hasUserLiked($noticia['id'], $_SESSION['user_id']);
                                }
                                
                                // Obtener comentarios usando el modelo Comment
                                $comentarios = Comment::getComments($noticia['id']);
                            ?>
                            <button type="button" class="like-btn" data-news-id="<?php echo $noticia['id']; ?>">
                                <span class="heart <?php echo $userLiked ? 'liked' : ''; ?>">❤️</span>
                                <span class="like-count"><?php echo $likesCount; ?></span>
                            </button>

<!-- Sección de comentarios -->
                            <div class="comentarios-section">
                                <div class="comentarios-header">
                                    <h4 class="comentarios-titulo">
                                        <i class="fas fa-comments"></i>
                                        Comentarios
                                    </h4>
                                </div>
                                
                                <div class="comment-form-container">
                                    <form class="comment-form" data-news-id="<?php echo $noticia['id']; ?>">
                                        <div class="form-group">
                                            <textarea name="comment" rows="3" placeholder="¿Qué opinas sobre esta noticia?" required></textarea>
                                            <div class="form-actions">
                                                <button type="submit" class="btn-comment">
                                                    <i class="fas fa-paper-plane"></i>
                                                    Enviar comentario
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="comentarios-container">
                                    <ul class="comentarios-lista">
                                        <?php if (!empty($comentarios)): ?>
                                            <?php foreach ($comentarios as $c): ?>
                                                <li class="comentario-item">
                                                    <div class="comentario-card">
                                                        <div class="comentario-header">
                                                            <div class="comentario-avatar">
                                                                <i class="fas fa-user-circle"></i>
                                                            </div>
                                                            <div class="comentario-info">
                                                                <strong class="comentario-usuario"><?php echo htmlspecialchars($c['username']); ?></strong>
                                                                <small class="comentario-fecha">
                                                                    <i class="far fa-clock"></i>
                                                                    <?php echo date("d/m/Y H:i", strtotime($c['created_at'])); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="comentario-contenido">
                                                            <p><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="no-comentarios">
                                                <div class="empty-state">
                                                    <i class="far fa-comment-alt"></i>
                                                    <h5>¡Sé el primero en comentar!</h5>
                                                    <p>Comparte tu opinión sobre esta noticia</p>
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-news-state">
                        <i class="far fa-newspaper"></i>
                        <h3>No hay noticias disponibles</h3>
                        <p class="text-muted">Vuelve más tarde para ver las últimas actualizaciones</p>
                        <div class="text-center">
                            <a href="#" class="btn btn-primary">
                                <i class="fas fa-home"></i>
                                Volver al inicio
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Fixture -->
<section id="fixture" class="fixture">
    <div class="container">
        <h2>Fixture</h2>
        <div class="competicion-selector">
            <?php
            $first = true;
            foreach ($fixturesByCompetition as $competition => $fixtures) {
                $competitionId = strtolower(str_replace(' ', '-', $competition));
                echo '<button class="comp-btn ' . ($first ? 'active' : '') . '" data-torneo="' . $competitionId . '">' . htmlspecialchars($competition) . '</button>';
                $first = false;
            }
            ?>
        </div>

        <?php
        $first = true;
        foreach ($fixturesByCompetition as $competition => $fixtures) {
            $competitionId = strtolower(str_replace(' ', '-', $competition));
            echo '<div id="' . $competitionId . '" class="torneo-content ' . ($first ? 'active' : '') . '">';
            echo '<h3>' . htmlspecialchars($competition) . ' 2024</h3>';
            echo '<div class="partidos-grid">';

            foreach ($fixtures as $partido) {
                $resultado = '';
                $claseResultado = '';
                $estado = ucfirst(strtolower($partido['estado']));

                // Mostrar resultado
                if ($partido['estado'] === 'Finalizado') {
                    $gLocal = $partido['goles_local'] ?? 0;
                    $gVisit = $partido['goles_visitante'] ?? 0;

                    if (!empty($partido['penales_local']) && !empty($partido['penales_visitante'])) {
                        $resultado = "$gLocal ({$partido['penales_local']}) - ({$partido['penales_visitante']}) $gVisit";
                    } else {
                        $resultado = "$gLocal - $gVisit";
                    }

                    // Asignar color según resultado
                    switch ($partido['resultado']) {
                        case 'Ganado':
                            $claseResultado = 'ganado';
                            break;
                        case 'Perdido':
                            $claseResultado = 'perdido';
                            break;
                        case 'Empatado':
                            $claseResultado = 'empatado';
                            break;
                        default:
                            $claseResultado = '';
                    }
                } else {
                    $resultado = 'vs';
                }

                // Renderizar partido
                echo '<div class="partido">';
                echo '<div class="jornada-title">' . htmlspecialchars($partido['jornada']) . '</div>';

                if (!empty($partido['fecha_partido'])) {
                    $fechaFormateada = date('d/m/Y H:i', strtotime($partido['fecha_partido']));
                    echo '<div class="fecha"><i class="far fa-calendar-alt"></i> ' . $fechaFormateada . '</div>';
                }

                echo '<div class="equipos">';
                echo '<div class="equipo local">' . htmlspecialchars($partido['equipo_local']) . '</div>';
                echo '<div class="resultado-partido ' . $claseResultado . '">' . $resultado . '</div>';
                echo '<div class="equipo visitante">' . htmlspecialchars($partido['equipo_visitante']) . '</div>';
                echo '</div>';

                echo '<div class="centrar">';
                echo '<span class="estado ' . strtolower($partido['estado']) . '">' . $estado . '</span>';
                echo '</div>';

                echo '</div>'; // cierre partido
            }

            echo '</div>'; // cierre partidos-grid
            echo '</div>'; // cierre torneo-content
            $first = false;
        }
        ?>
    </div>
</section>

<!-- Galería Multimedia -->
<section id="galeria" class="galeria">
    <div class="container">
        <h2>Galería Bolsilluda</h2>
        <div class="galeria-grid">
            <div class="galeria-item" data-src="static/images/suarez_copa.jpeg">
                <img src="static/images/suarez_copa.jpeg" alt="Luis Suárez con la copa">
                <div class="galeria-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>
            <div class="galeria-item" data-src="static/images/gol_recoba.jpeg">
                <img src="static/images/gol_recoba.jpeg" alt="Gol chino Recoba">
                <div class="galeria-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>
            <div class="galeria-item" data-src="static/images/gol_diente.jpeg">
                <img src="static/images/gol_diente.jpeg" alt="Gol del Diente Lopez a Palmeiras">
                <div class="galeria-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>
            <div class="galeria-item" data-src="static/images/quinquenio.JPG">
                <img src="static/images/quinquenio.JPG" alt="quinquenio del bolso">
                <div class="galeria-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>
            <div class="galeria-item" data-src="static/images/vergessio.jpeg">
                <img src="static/images/vergessio.jpeg" alt="Gonzalo Bergessio máximo goleador tricolor del siglo XXI">
                <div class="galeria-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>
            <div class="galeria-item" data-src="static/images/125_aniversario.jpg">
                <img src="static/images/125_aniversario.jpg" alt="125 Aniversario del Club Nacional de Football">
                <div class="galeria-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para visualización de imágenes -->
    <div id="modal-galeria" class="modal">
        <span class="cerrar">&times;</span>
        <img class="modal-contenido" id="img-modal">
        <div class="caption-container">
            <p id="caption"></p>
        </div>
    </div>
</section>

<!-- Historia -->
<section id="historia" class="historia">
    <div class="container">
        <h2>Historia</h2>
        <div class="historia-content">
            <div class="historia-texto">
                <p>El Club Nacional de Football fue fundado el 14 de mayo de 1899 por estudiantes del Instituto Politécnico de la Universidad Católica de Uruguay. Desde sus inicios, el club se caracterizó por su espíritu competitivo y su pasión por el fútbol.</p>

                <h3>Los Primeros Años</h3>
                <p>Durante sus primeros años, Nacional estableció las bases de lo que sería una institución deportiva de renombre mundial. El club adoptó los colores azul, blanco y rojo, inspirados en los colores de José Gervasio Artigas.</p>

                <h3>Logros Internacionales</h3>
                <p>Nacional es uno de los clubes más exitosos de América, con múltiples títulos internacionales que incluyen la Copa Libertadores, la Copa Intercontinental y numerosos campeonatos nacionales.</p>
            </div>
            <div class="historia-logros">
                <div class="logro">
                    <span class="logro-numero">47</span>
                    <span class="logro-titulo">Campeonatos Uruguayos</span>
                </div>
                <div class="logro">
                    <span class="logro-numero">3</span>
                    <span class="logro-titulo">Copas Libertadores</span>
                </div>
                <div class="logro">
                    <span class="logro-numero">3</span>
                    <span class="logro-titulo">Copas Intercontinentales</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar el cambio entre competiciones
        const compButtons = document.querySelectorAll('.comp-btn');

        if (compButtons.length > 0) {
            compButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const torneoId = this.getAttribute('data-torneo');
                    const targetContent = document.getElementById(torneoId);

                    // Validar que el contenido objetivo existe
                    if (!targetContent) {
                        console.error('No se encontró el contenido para:', torneoId);
                        return;
                    }

                    // Remover clase active de todos los botones
                    compButtons.forEach(btn => btn.classList.remove('active'));

                    // Ocultar todos los contenidos
                    document.querySelectorAll('.torneo-content').forEach(content => {
                        content.classList.remove('active');
                    });

                    // Agregar clase active al botón clickeado
                    this.classList.add('active');

                    // Mostrar el contenido correspondiente
                    targetContent.classList.add('active');
                });
            });
        } else {
            console.warn('No se encontraron botones de competición');
        }

        // Funcionalidad del modal de la galería
        const modal = document.getElementById('modal-galeria');
        const imgModal = document.getElementById('img-modal');
        const caption = document.getElementById('caption');
        const cerrar = document.getElementsByClassName('cerrar')[0];

        // Abrir modal al hacer clic en una imagen
        document.querySelectorAll('.galeria-item').forEach(item => {
            item.onclick = function() {
                modal.style.display = 'block';
                imgModal.src = this.dataset.src;
                caption.innerHTML = this.querySelector('img').alt;
            }
        });

        // Cerrar modal
        if (cerrar) {
            cerrar.onclick = function() {
                modal.style.display = 'none';
            }
        }

        // Cerrar modal al hacer clic fuera de la imagen
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                modal.style.display = 'none';
            }
        });

        // Funcionalidad para expandir/contraer noticias
        const leerMasButtons = document.querySelectorAll('.leer-mas');
        const leerMenosButtons = document.querySelectorAll('.leer-menos');

        // Expandir noticia
        leerMasButtons.forEach(button => {
            button.addEventListener('click', function() {
                const noticiaId = this.getAttribute('data-id');
                const noticiaCard = document.getElementById('noticia-' + noticiaId);

                if (noticiaCard) {
                    noticiaCard.classList.add('expanded');

                    // Desplazar suavemente a la noticia expandida
                    noticiaCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Contraer noticia
        leerMenosButtons.forEach(button => {
            button.addEventListener('click', function() {
                const noticiaId = this.getAttribute('data-id');
                const noticiaCard = document.getElementById('noticia-' + noticiaId);

                if (noticiaCard) {
                    noticiaCard.classList.remove('expanded');

                    // Desplazar suavemente a la parte superior de la noticia
                    noticiaCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // FUNCIONALIDAD PARA LIKE Y COMENTARIOS
        // Like con corazón - Usamos delegación de eventos
        document.addEventListener("click", function(e) {
            const likeBtn = e.target.closest(".like-btn");
            if (likeBtn) {
                e.preventDefault();
                const newsId = likeBtn.dataset.newsId;
                
                // Crear FormData para enviar
                const formData = new FormData();
                formData.append('news_id', newsId);
                
                fetch("like.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return res.json();
                })
                .then(data => {
                    console.log("Respuesta like.php:", data);
                    if (data.success) {
                        likeBtn.querySelector(".like-count").innerText = data.likes;
                        likeBtn.querySelector(".heart").classList.toggle("liked", data.liked);
                        
                        // Animación de pulso cuando se da like
                        if (data.liked) {
                            const heart = likeBtn.querySelector(".heart");
                            heart.style.animation = 'none';
                            setTimeout(() => {
                                heart.style.animation = 'pulse 0.5s';
                            }, 10);
                        }
                    } else {
                        if (data.message === "Debes iniciar sesión" || data.error === "Debes iniciar sesión") {
                            alert("Debes iniciar sesión para dar like");
                        } else {
                            alert(data.message || data.error);
                        }
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Error al procesar el like");
                });
            }
        });

        // Comentarios - Usamos delegación de eventos
        document.addEventListener("submit", function(e) {
            const form = e.target;
            if (form.classList.contains("comment-form")) {
                e.preventDefault();
                const newsId = form.dataset.newsId;
                const textarea = form.querySelector("textarea");
                const commentText = textarea.value.trim();
                
                if (!commentText) {
                    alert("Por favor, escribe un comentario");
                    return;
                }
                
                // Crear FormData para enviar
                const formData = new FormData();
                formData.append('news_id', newsId);
                formData.append('comment', commentText);
                
                fetch("comment.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        // Limpiar textarea
                        textarea.value = "";
                        
                        // Encontrar el contenedor de comentarios correctamente
                        const comentariosContainer = form.closest('.comentarios-section').querySelector('.comentarios-container');
                        const comentariosLista = comentariosContainer.querySelector('.comentarios-lista');
                        const noCommentsItem = comentariosLista.querySelector(".no-comentarios, .empty-state");

                        if (noCommentsItem) {
                            noCommentsItem.remove();
                        }

                        // Crear nuevo elemento de comentario con la estructura correcta
                        const newComment = document.createElement("li");
                        newComment.className = "comentario-item";
                        newComment.innerHTML = `
                            <div class="comentario-card">
                                <div class="comentario-header">
                                    <div class="comentario-avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="comentario-info">
                                        <strong class="comentario-usuario">${data.username}</strong>
                                        <small class="comentario-fecha">
                                            <i class="far fa-clock"></i>
                                            Justo ahora
                                        </small>
                                    </div>
                                </div>
                                <div class="comentario-contenido">
                                    <p>${data.comment.replace(/\n/g, '<br>')}</p>
                                </div>
                            </div>
                        `;

                        comentariosLista.insertBefore(newComment, comentariosLista.firstChild);
                        
                        // Animación de entrada
                        newComment.style.opacity = "0";
                        newComment.style.transform = "translateY(-10px)";
                        
                        setTimeout(() => {
                            newComment.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                            newComment.style.opacity = "1";
                            newComment.style.transform = "translateY(0)";
                        }, 10);
                    } else {
                        if (data.message === "Debes iniciar sesión" || data.error === "Debes iniciar sesión") {
                            alert("Debes iniciar sesión para comentar");
                        } else {
                            alert(data.message || data.error);
                        }
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Error al publicar el comentario");
                });
            }
        });
    });

</script>

<?php require_once 'includes/footer.php'; ?>