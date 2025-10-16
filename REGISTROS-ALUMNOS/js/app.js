class SistemaAlumnosApp {
    constructor() {
        this.alumnoService = new AlumnoService();
        this.alumnoEditando = null;
        this.init();
    }

    init() {
        this.cargarAlumnos();
        this.actualizarEstadisticas();
        this.configurarEventListeners();
    }

    configurarEventListeners() {
        // Buscar en tiempo real
        const buscarInput = document.getElementById('buscar-input');
        if (buscarInput) {
            buscarInput.addEventListener('input', (e) => {
                this.buscarAlumnos(e.target.value);
            });
        }

        // Cerrar modal haciendo click fuera
        const modal = document.getElementById('confirm-modal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.ocultarModal();
                }
            });
        }
    }

    cargarAlumnos(alumnos = null) {
        const listaAlumnos = document.getElementById('lista-alumnos');
        const noAlumnos = document.getElementById('no-alumnos');
        
        if (!listaAlumnos) return;

        const alumnosMostrar = alumnos || this.alumnoService.obtenerTodos();

        if (alumnosMostrar.length === 0) {
            listaAlumnos.innerHTML = '';
            noAlumnos.classList.remove('hidden');
            return;
        }

        noAlumnos.classList.add('hidden');
        listaAlumnos.innerHTML = alumnosMostrar.map(alumno => this.crearTarjetaAlumno(alumno)).join('');
    }

    crearTarjetaAlumno(alumno) {
        const materiasHTML = alumno.materias.length > 0 
            ? alumno.materias.map(materia => `
                <div class="materia-item">
                    <span>${materia.nombre}</span>
                    <span class="nota ${CalculosUtils.obtenerColorNota(materia.nota)}">
                        ${materia.nota}
                    </span>
                </div>
            `).join('')
            : '<p class="text-center" style="color: var(--gray);">No hay materias registradas</p>';

        return `
            <div class="alumno-card" data-alumno-id="${alumno.id}">
                <div class="alumno-header">
                    <div class="alumno-info">
                        <h3>${alumno.nombre} ${alumno.apellido}</h3>
                        <div class="alumno-id">ID: ${alumno.id}</div>
                    </div>
                    <div class="alumno-status ${CalculosUtils.obtenerClaseEstado(alumno.estado)}">
                        ${alumno.estado}
                    </div>
                </div>

                ${alumno.materias.length > 0 ? `
                    <div class="alumno-promedio">
                        <div>Promedio General</div>
                        <div class="promedio-value">${CalculosUtils.formatearPromedio(alumno.promedio)}</div>
                    </div>
                ` : ''}

                <div class="materias-section">
                    <h4><i class="fas fa-book"></i> Materias</h4>
                    ${materiasHTML}
                </div>

                <div class="alumno-actions">
                    <button class="btn btn-primary btn-sm" onclick="app.mostrarFormMateria(${alumno.id})">
                        <i class="fas fa-plus"></i> Materia
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="app.mostrarEditarAlumno(${alumno.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="app.mostrarConfirmarEliminar(${alumno.id})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        `;
    }

    mostrarFormularioAlumno(alumno = null) {
        const form = document.getElementById('form-alumno');
        const titulo = document.getElementById('form-titulo');
        const formElement = document.getElementById('alumno-form');
        
        this.alumnoEditando = alumno;

        if (alumno) {
            titulo.textContent = 'Editar Alumno';
            document.getElementById('alumno-id').value = alumno.id;
            document.getElementById('nombre').value = alumno.nombre;
            document.getElementById('apellido').value = alumno.apellido;
        } else {
            titulo.textContent = 'Nuevo Alumno';
            formElement.reset();
            document.getElementById('alumno-id').value = '';
        }

        form.classList.remove('hidden');
        form.scrollIntoView({ behavior: 'smooth' });
    }

    ocultarFormulario() {
        document.getElementById('form-alumno').classList.add('hidden');
        this.alumnoEditando = null;
    }

    guardarAlumno(event) {
        event.preventDefault();
        
        const id = document.getElementById('alumno-id').value;
        const nombre = document.getElementById('nombre').value;
        const apellido = document.getElementById('apellido').value;

        try {
            if (id) {
                // Editar alumno existente
                this.alumnoService.actualizarAlumno(parseInt(id), nombre, apellido);
            } else {
                // Crear nuevo alumno
                this.alumnoService.crearAlumno(nombre, apellido);
            }

            this.ocultarFormulario();
            this.cargarAlumnos();
            this.actualizarEstadisticas();
        } catch (error) {
            // El error ya se muestra desde el servicio
        }
    }

    mostrarFormMateria(alumnoId) {
        const alumno = this.alumnoService.obtenerPorId(alumnoId);
        if (!alumno) return;

        document.getElementById('materia-alumno-id').value = alumnoId;
        document.getElementById('form-materia').classList.remove('hidden');
        document.getElementById('materia-form').reset();
        document.getElementById('form-materia').scrollIntoView({ behavior: 'smooth' });
    }

    ocultarFormMateria() {
        document.getElementById('form-materia').classList.add('hidden');
    }

    agregarMateria(event) {
        event.preventDefault();
        
        const alumnoId = parseInt(document.getElementById('materia-alumno-id').value);
        const nombreMateria = document.getElementById('materia-nombre').value;
        const nota = document.getElementById('materia-nota').value;

        try {
            this.alumnoService.agregarMateria(alumnoId, nombreMateria, nota);
            this.ocultarFormMateria();
            this.cargarAlumnos();
            this.actualizarEstadisticas();
        } catch (error) {
            // El error ya se muestra desde el servicio
        }
    }

    mostrarEditarAlumno(alumnoId) {
        const alumno = this.alumnoService.obtenerPorId(alumnoId);
        if (alumno) {
            this.mostrarFormularioAlumno(alumno);
        }
    }

    mostrarConfirmarEliminar(alumnoId) {
        const alumno = this.alumnoService.obtenerPorId(alumnoId);
        if (!alumno) return;

        const modal = document.getElementById('confirm-modal');
        const titulo = document.getElementById('modal-titulo');
        const mensaje = document.getElementById('modal-mensaje');
        const btnConfirmar = document.getElementById('modal-confirm-btn');

        titulo.textContent = 'Eliminar Alumno';
        mensaje.innerHTML = `¿Estás seguro de que deseas eliminar a <strong>${alumno.nombre} ${alumno.apellido}</strong>? Esta acción no se puede deshacer.`;
        
        btnConfirmar.onclick = () => this.eliminarAlumno(alumnoId);
        modal.classList.remove('hidden');
    }

    eliminarAlumno(alumnoId) {
        try {
            this.alumnoService.eliminarAlumno(alumnoId);
            this.ocultarModal();
            this.cargarAlumnos();
            this.actualizarEstadisticas();
        } catch (error) {
            this.ocultarModal();
        }
    }

    buscarAlumnos(termino) {
        const resultados = this.alumnoService.buscarPorNombre(termino);
        this.cargarAlumnos(resultados);
    }

    actualizarEstadisticas() {
        const estadisticas = CalculosUtils.calcularEstadisticas(this.alumnoService.alumnos);
        
        document.getElementById('total-alumnos').textContent = estadisticas.total;
        document.getElementById('total-aprobados').textContent = estadisticas.aprobados;
        document.getElementById('total-desaprobados').textContent = estadisticas.desaprobados;
        document.getElementById('promedio-general').textContent = 
            CalculosUtils.formatearPromedio(estadisticas.promedioGeneral);
    }

    ocultarModal() {
        document.getElementById('confirm-modal').classList.add('hidden');
    }
}

// Funciones globales para los event handlers del HTML
function mostrarFormularioAlumno() {
    app.mostrarFormularioAlumno();
}

function ocultarFormulario() {
    app.ocultarFormulario();
}

function guardarAlumno(event) {
    app.guardarAlumno(event);
}

function agregarMateria(event) {
    app.agregarMateria(event);
}

function ocultarFormMateria() {
    app.ocultarFormMateria();
}

function buscarAlumnos() {
    const termino = document.getElementById('buscar-input').value;
    app.buscarAlumnos(termino);
}

function ocultarModal() {
    app.ocultarModal();
}

// Inicializar la aplicación cuando el DOM esté listo
let app;
document.addEventListener('DOMContentLoaded', () => {
    app = new SistemaAlumnosApp();
});