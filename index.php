<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$title = "BOLSILLUDO_INFO - Club Nacional de Football";

// Ruta del archivo de caché para noticias
$cacheFileNews = __DIR__ . '/cache_news.json';
$cacheTimeNews = 30; // segundos

// Ruta del archivo de caché para fixtures
$cacheFileFixtures = __DIR__ . '/cache_fixtures.json';
$cacheTimeFixtures = 3600; // 1 hora para fixtures

$news = [];
$fixturesByCompetition = [];

// Cargar noticias desde caché o BD
if (file_exists($cacheFileNews) && (time() - filemtime($cacheFileNews) < $cacheTimeNews)) {
    $news = json_decode(file_get_contents($cacheFileNews), true);
} else {
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
            file_put_contents($cacheFileNews, json_encode($news));
        }
        $db->close();
    }
}

// Cargar fixtures desde caché o BD
if (file_exists($cacheFileFixtures) && (time() - filemtime($cacheFileFixtures) < $cacheTimeFixtures)) {
    $fixturesByCompetition = json_decode(file_get_contents($cacheFileFixtures), true);
} else {
    $db = getDBConnection();
    if ($db) {
        // Consulta optimizada para obtener todos los fixtures agrupados por competición
        $sql = "SELECT competicion, jornada, fecha_partido, equipo_local, equipo_visitante, 
                       goles_local, goles_visitante, penales_local, penales_visitante, estado, resultado
                FROM fixtures 
                ORDER BY 
                    FIELD(competicion, 'Torneo Intermedio', 'Copa Libertadores', 'Copa Uruguay', 'Torneo Clausura'),
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

            file_put_contents($cacheFileFixtures, json_encode($fixturesByCompetition));
        }
        $db->close();
    }
}

require_once 'includes/header.php';
?>

<style>

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
    cursor: pointer;
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
    aspect-ratio: 16/9; /* Proporción fija 16:9 */
    overflow: hidden;
}

.noticia-img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Cubre todo el contenedor */
    object-position: center; /* Centra la imagen */
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
    flex: 1; /* Ocupa el espacio restante */
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
    margin-bottom: 20px;
    font-size: 0.95em;
    flex: 1; /* Toma el espacio disponible */
}

.leer-mas {
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
    margin-top: auto; /* Se empuja hacia abajo */
}

.leer-mas:hover {
    background: #007bff;
    color: white;
    transform: translateX(5px);
}

.leer-mas i {
    transition: transform 0.3s ease;
}

.leer-mas:hover i {
    transform: translateX(5px);
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
        aspect-ratio: 16/10; /* Ligeramente más alto en móvil */
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
        aspect-ratio: 4/3; /* Más cuadrada en móviles pequeños */
    }
    
    .noticia-content {
        padding: 15px;
    }
    
    .noticia-content h3 {
        font-size: 1.1em;
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
        height: 200px;
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
    }
    
    .noticia-img-container {
        height: 180px;
    }
    
    .noticia-content {
        padding: 15px;
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
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .galeria h2 {
        text-align: center;
        font-size: 2.5em;
        margin-bottom: 50px;
        color: #333;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }

    .galeria-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        padding: 20px 0;
    }

    .galeria-item {
        position: relative;
        aspect-ratio: 4/3; /* Mantiene proporción constante */
        overflow: hidden;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f0f0f0;
    }

    .galeria-item:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.25);
    }

    .galeria-item img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Ajusta la imagen sin distorsionar */
        object-position: center;
        transition: transform 0.3s ease;
    }

    .galeria-item:hover img {
        transform: scale(1.1);
    }

    .galeria-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .galeria-item:hover .galeria-overlay {
        opacity: 1;
    }

    .galeria-overlay i {
        color: white;
        font-size: 2em;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        padding-top: 100px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.9);
        backdrop-filter: blur(5px);
    }

    .modal-contenido {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
        border-radius: 10px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }

    .cerrar {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
    }

    .cerrar:hover,
    .cerrar:focus {
        color: #bbb;
        text-decoration: none;
    }

    .caption-container {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
        text-align: center;
        color: #ccc;
        padding: 10px 0;
        height: 150px;
    }

    #caption {
        font-size: 1.2em;
        margin: 20px 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .galeria-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .galeria h2 {
            font-size: 2em;
            margin-bottom: 30px;
        }
        
        .modal-contenido {
            width: 95%;
        }
        
        .caption-container {
            width: 95%;
        }
        
        .cerrar {
            font-size: 30px;
            top: 10px;
            right: 20px;
        }
    }

    @media (max-width: 480px) {
        .galeria-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .galeria {
            padding: 40px 0;
        }
        
        .container {
            padding: 0 15px;
        }
    }
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

    <!-- Fixture -->
    <section id="fixture" class="fixture">
        <div class="container">
            <h2>Fixture</h2>
            <div class="competicion-selector">
                <?php
                $first = true;
                foreach ($fixturesByCompetition as $competition => $fixtures) {
                    $competitionId = strtolower(str_replace(' ', '-', $competition));
                    echo '<button class="comp-btn ' . ($first ? 'active' : '') . '" data-torneo="' . $competitionId . '">' . $competition . '</button>';
                    $first = false;
                }
                ?>
            </div>

            <?php
            $first = true;
            foreach ($fixturesByCompetition as $competition => $fixtures) {
                $competitionId = strtolower(str_replace(' ', '-', $competition));
                echo '<div id="' . $competitionId . '" class="torneo-content ' . ($first ? 'active' : '') . '">';
                echo '<h3>' . $competition . ' 2024</h3>';
                echo '<div class="partidos-grid">';

                foreach ($fixtures as $partido) {
                    $resultado = '';
                    $claseResultado = '';

                    if ($partido['estado'] === 'Finalizado') {
                        if (!empty($partido['penales_local']) && !empty($partido['penales_visitante'])) {
                            $resultado = $partido['goles_local'] . '(' . $partido['penales_local'] . ') - (' . $partido['penales_visitante'] . ')' . $partido['goles_visitante'];
                        } else {
                            $resultado = $partido['goles_local'] . ' - ' . $partido['goles_visitante'];
                        }

                        // Determinar clase CSS según resultado
                        if ($partido['resultado'] === 'Ganado') {
                            $claseResultado = 'ganado';
                        } elseif ($partido['resultado'] === 'Perdido') {
                            $claseResultado = 'perdido';
                        } elseif ($partido['resultado'] === 'Empatado') {
                            $claseResultado = 'empatado';
                        }
                    } else {
                        $resultado = 'vs';
                    }

                    echo '<div class="partido">';
                    echo '<div class="jornada-title">' . $partido['jornada'] . '</div>';

                    if (!empty($partido['fecha_partido'])) {
                        $fechaFormateada = date('d/m/Y H:i', strtotime($partido['fecha_partido']));
                        echo '<div class="fecha">' . $fechaFormateada . '</div>';
                    }

                    echo '<div class="equipos">';
                    echo '<div class="equipo local">' . $partido['equipo_local'] . '</div>';
                    echo '<div class="resultado-partido ' . $claseResultado . '">' . $resultado . '</div>';
                    echo '<div class="equipo visitante">' . $partido['equipo_visitante'] . '</div>';
                    echo '</div>';

                    echo '<div class="centrar">';
                    echo '<span class="estado ' . strtolower($partido['estado']) . '">' . $partido['estado'] . '</span>';
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


<!-- Noticias -->
<section id="noticias" class="noticias">
    <div class="container">
        <h2>Últimas Noticias</h2>
        <div class="noticias-grid">
            <?php if (!empty($news)): ?>
                <?php foreach ($news as $noticia): ?>
                    <div class="noticia-card">
                        <div class="noticia-img-container">
                            <?php if (!empty($noticia['image_url'])): ?>
                                <img src="<?php echo $noticia['image_url']; ?>" alt="<?php echo htmlspecialchars($noticia['title']); ?>" class="noticia-img">
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
                            <p><?php echo substr(htmlspecialchars($noticia['content']), 0, 150); ?><?php echo strlen($noticia['content']) > 150 ? '...' : ''; ?></p>
                            <a href="#" class="leer-mas">Leer más <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No hay noticias disponibles.</p>
                    <?php if (Auth::isLoggedIn() && Auth::isAdmin()): ?>
                        <a href="admin/news_new.php" class="btn btn-primary">Agregar Primera Noticia</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>



    <!-- Galería Multimedia -->
    <section id="galeria" class="galeria">
        <div class="container">
            <h2>Galería Bolsilluda</h2>
            <div class="galeria-grid">
                <div class="galeria-item" data-src="/bolsilludo/static/images/suarez_copa.jpeg">
                    <img src="/bolsilludo/static/images/suarez_copa.jpeg" alt="Luis Suárez con la copa">
                    <div class="galeria-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="galeria-item" data-src="/bolsilludo/static/images/gol_recoba.jpeg">
                    <img src="/bolsilludo/static/images/gol_recoba.jpeg" alt="Gol chino Recoba">
                    <div class="galeria-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="galeria-item" data-src="/bolsilludo/static/images/gol_diente.jpeg">
                    <img src="/bolsilludo/static/images/gol_diente.jpeg" alt="Gol del Diente Lopez a Palmeiras">
                    <div class="galeria-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="galeria-item" data-src="/bolsilludo/static/images/quinquenio.JPG">
                    <img src="/bolsilludo/static/images/quinquenio.JPG" alt="quinquenio del bolso">
                    <div class="galeria-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="galeria-item" data-src="/bolsilludo/static/images/vergessio.jpeg">
                    <img src="/bolsilludo/static/images/vergessio.jpeg" alt="Gonzalo Bergessio máximo goleador tricolor del siglo XXI">
                    <div class="galeria-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="galeria-item" data-src="/bolsilludo/static/images/125_aniversario.jpg">
                    <img src="/bolsilludo/static/images/125_aniversario.jpg" alt="125 Aniversario del Club Nacional de Football">
                    <div class="galeria-overlay">
                        <i class="fas fa-search-plus"></i>
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
        });
    </script>

<?php require_once 'includes/footer.php'; ?> 
