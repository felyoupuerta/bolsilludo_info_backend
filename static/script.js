// Funcionalidad para cambiar entre torneos en el fixture
document.addEventListener('DOMContentLoaded', function() {
    // Cambio de torneos
    const compButtons = document.querySelectorAll('.comp-btn');
    const torneoContents = document.querySelectorAll('.torneo-content');
    
    compButtons.forEach(button => {
        button.addEventListener('click', function() {
            const torneoId = this.getAttribute('data-torneo');
            
            // Actualizar botones
            compButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Mostrar contenido correspondiente
            torneoContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === torneoId) {
                    content.classList.add('active');
                }
            });
        });
    });
    
    // Simulación de datos de partidos
    const partidosData = {
        'torneo-apertura': [
            {
                jornada: 'Fecha 1',
                fecha: '15 de Marzo, 2025 - 15:30',
                local: 'Nacional',
                visitante: 'Peñarol',
                estado: 'pendiente'
            },
            {
                jornada: 'Fecha 2',
                fecha: '22 de Marzo, 2025 - 16:00',
                local: 'River Plate',
                visitante: 'Nacional',
                estado: 'pendiente'
            },
            {
                jornada: 'Fecha 3',
                fecha: '29 de Marzo, 2025 - 18:00',
                local: 'Nacional',
                visitante: 'Defensor',
                estado: 'pendiente'
            }
        ],
        'libertadores': [
            {
                jornada: 'Fase de Grupos',
                fecha: '5 de Abril, 2025 - 20:00',
                local: 'Nacional',
                visitante: 'Flamengo',
                estado: 'pendiente'
            },
            {
                jornada: 'Fase de Grupos',
                fecha: '12 de Abril, 2025 - 21:30',
                local: 'Boca Juniors',
                visitante: 'Nacional',
                estado: 'pendiente'
            }
        ],
        'sudamericana': [
            {
                jornada: 'Primera Fase',
                fecha: '19 de Abril, 2025 - 19:00',
                local: 'Nacional',
                visitante: 'Independiente',
                estado: 'pendiente'
            }
        ]
    };
    
    // Generar partidos para cada torneo
    for (const torneo in partidosData) {
        const container = document.getElementById(torneo);
        if (container) {
            const partidosGrid = container.querySelector('.partidos-grid');
            if (partidosGrid) {
                partidosGrid.innerHTML = ''; // Limpiar contenido existente
                
                partidosData[torneo].forEach(partido => {
                    const partidoEl = document.createElement('div');
                    partidoEl.className = 'partido';
                    partidoEl.innerHTML = `
                        <div class="jornada-title">${partido.jornada}</div>
                        <div class="fecha">${partido.fecha}</div>
                        <div class="equipos">
                            <div class="equipo local">${partido.local}</div>
                            <div class="resultado-partido">vs</div>
                            <div class="equipo visitante">${partido.visitante}</div>
                        </div>
                        <div class="centrar">
                            <span class="estado ${partido.estado}">${partido.estado.charAt(0).toUpperCase() + partido.estado.slice(1)}</span>
                        </div>
                    `;
                    partidosGrid.appendChild(partidoEl);
                });
            }
        }
    }
}); 
