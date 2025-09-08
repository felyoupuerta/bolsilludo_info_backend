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
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    /* Resto de estilos para galería, historia, etc. (completado lógicamente) */
    .galeria-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .galeria-item {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
        cursor: pointer;
    }

    .galeria-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .galeria-item:hover img {
        transform: scale(1.1);
    }

    /* Sección de historia */
    .historia {
        padding: 80px 0;
        background: white;
    }

    .historia-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: center;
    }

    .historia-texto {
        font-size: 1.1em;
        line-height: 1.7;
        color: #555;
    }

    .historia-logros {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .logro {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .logro-numero {
        font-size: 2em;
        font-weight: bold;
        color: #007bff;
        background: #f8f9fa;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .logro-titulo {
        font-size: 1.1em;
        color: #333;
    }

    /* Responsive para historia */
    @media (max-width: 768px) {
        .historia-container {
            grid-template-columns: 1fr;
            gap: 30px;
        }
    }
</style>