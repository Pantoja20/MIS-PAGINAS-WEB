class AlumnoService {
    constructor() {
        this.storageKey = 'sistema_alumnos';
        this.alumnos = this.cargarAlumnos();
        this.nextId = this.calcularNextId();
    }

    cargarAlumnos() {
        try {
            const data = localStorage.getItem(this.storageKey);
            if (data) {
                const alumnosData = JSON.parse(data);
                return alumnosData.map(alumnoData => Alumno.fromJSON(alumnoData));
            }
        } catch (error) {
            console.error('Error cargando alumnos:', error);
            this.mostrarError('Error al cargar los datos de alumnos');
        }
        return [];
    }

    guardarAlumnos() {
        try {
            const data = JSON.stringify(this.alumnos.map(alumno => alumno.toJSON()));
            localStorage.setItem(this.storageKey, data);
        } catch (error) {
            console.error('Error guardando alumnos:', error);
            this.mostrarError('Error al guardar los datos');
        }
    }

    calcularNextId() {
        if (this.alumnos.length === 0) return 1;
        const maxId = Math.max(...this.alumnos.map(alumno => alumno.id));
        return maxId + 1;
    }

    // CREATE - Crear nuevo alumno
    crearAlumno(nombre, apellido) {
        if (!CalculosUtils.validarDatosAlumno(nombre, apellido)) {
            throw new Error('Nombre y apellido son requeridos (mínimo 2 caracteres cada uno)');
        }

        const alumno = new Alumno(this.nextId++, nombre.trim(), apellido.trim());
        this.alumnos.push(alumno);
        this.guardarAlumnos();
        this.mostrarExito('Alumno creado exitosamente');
        return alumno;
    }

    // READ - Obtener todos los alumnos
    obtenerTodos() {
        return this.alumnos.sort((a, b) => a.apellido.localeCompare(b.apellido));
    }

    // READ - Obtener alumno por ID
    obtenerPorId(id) {
        const alumno = this.alumnos.find(alumno => alumno.id === id);
        if (!alumno) {
            throw new Error(`Alumno con ID ${id} no encontrado`);
        }
        return alumno;
    }

    // READ - Buscar alumnos por nombre o apellido
    buscarPorNombre(termino) {
        if (!termino || termino.trim() === '') return this.obtenerTodos();
        
        const searchTerm = termino.toLowerCase().trim();
        return this.alumnos.filter(alumno => 
            alumno.nombre.toLowerCase().includes(searchTerm) ||
            alumno.apellido.toLowerCase().includes(searchTerm) ||
            `${alumno.nombre} ${alumno.apellido}`.toLowerCase().includes(searchTerm)
        );
    }

    // UPDATE - Actualizar datos del alumno
    actualizarAlumno(id, nombre, apellido) {
        const alumno = this.obtenerPorId(id);
        if (!alumno) {
            throw new Error('Alumno no encontrado');
        }

        if (!CalculosUtils.validarDatosAlumno(nombre, apellido)) {
            throw new Error('Nombre y apellido son requeridos (mínimo 2 caracteres cada uno)');
        }

        alumno.nombre = nombre.trim();
        alumno.apellido = apellido.trim();
        this.guardarAlumnos();
        this.mostrarExito('Alumno actualizado exitosamente');
        return alumno;
    }

    // UPDATE - Agregar materia a alumno
    agregarMateria(id, nombreMateria, nota) {
        const alumno = this.obtenerPorId(id);
        if (!alumno) {
            throw new Error('Alumno no encontrado');
        }

        if (!CalculosUtils.validarNota(nota)) {
            throw new Error('La nota debe ser un número entre 0 y 20'); // Cambiado a 20
        }

        if (!nombreMateria || nombreMateria.trim() === '') {
            throw new Error('El nombre de la materia es requerido');
        }

        alumno.agregarMateria(nombreMateria, nota);
        this.guardarAlumnos();
        this.mostrarExito(`Materia "${nombreMateria}" agregada exitosamente`);
        return alumno;
    }

    // UPDATE - Eliminar materia de alumno
    eliminarMateria(id, nombreMateria) {
        const alumno = this.obtenerPorId(id);
        if (!alumno) {
            throw new Error('Alumno no encontrado');
        }

        alumno.eliminarMateria(nombreMateria);
        this.guardarAlumnos();
        this.mostrarExito(`Materia "${nombreMateria}" eliminada exitosamente`);
        return alumno;
    }

    // UPDATE - Actualizar nota de materia
    actualizarNotaMateria(id, nombreMateria, nuevaNota) {
        const alumno = this.obtenerPorId(id);
        if (!alumno) {
            throw new Error('Alumno no encontrado');
        }

        if (!CalculosUtils.validarNota(nuevaNota)) {
            throw new Error('La nota debe ser un número entre 0 y 20'); // Cambiado a 20
        }

        const materia = alumno.materias.find(m => m.nombre.toLowerCase() === nombreMateria.toLowerCase());
        if (!materia) {
            throw new Error(`La materia "${nombreMateria}" no existe para este alumno`);
        }

        materia.nota = parseFloat(nuevaNota);
        alumno.actualizarPromedioYEstado();
        this.guardarAlumnos();
        this.mostrarExito(`Nota de "${nombreMateria}" actualizada exitosamente`);
        return alumno;
    }

    // DELETE - Eliminar alumno
    eliminarAlumno(id) {
        const index = this.alumnos.findIndex(alumno => alumno.id === id);
        if (index === -1) {
            throw new Error('Alumno no encontrado');
        }

        const alumnoEliminado = this.alumnos.splice(index, 1)[0];
        this.guardarAlumnos();
        this.mostrarExito('Alumno eliminado exitosamente');
        return alumnoEliminado;
    }

    // MÉTODOS DE INFORMACIÓN Y ADMINISTRACIÓN

    // Mostrar información del almacenamiento
    mostrarInformacionAlmacenamiento() {
        const data = localStorage.getItem(this.storageKey);
        if (!data) {
            return {
                existe: false,
                mensaje: 'No hay datos guardados en el almacenamiento',
                datos: null,
                tamaño: '0 KB',
                cantidadAlumnos: 0,
                claveAlmacenamiento: this.storageKey
            };
        }

        const alumnosData = JSON.parse(data);
        const tamaño = (new Blob([data]).size / 1024).toFixed(2);
        
        return {
            existe: true,
            mensaje: `Datos encontrados en LocalStorage`,
            datos: alumnosData,
            tamaño: `${tamaño} KB`,
            cantidadAlumnos: alumnosData.length,
            claveAlmacenamiento: this.storageKey
        };
    }

    // Exportar datos a archivo JSON
    exportarDatos() {
        try {
            const data = this.mostrarInformacionAlmacenamiento();
            if (!data.existe) {
                throw new Error('No hay datos para exportar');
            }

            const blob = new Blob([JSON.stringify(data.datos, null, 2)], { 
                type: 'application/json' 
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `backup-alumnos-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            this.mostrarExito('Datos exportados exitosamente');
        } catch (error) {
            this.mostrarError('Error al exportar datos: ' + error.message);
        }
    }

    // Importar datos desde archivo JSON
    importarDatos(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validar tipo de archivo
        if (!file.name.endsWith('.json')) {
            this.mostrarError('Solo se permiten archivos JSON');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const datos = JSON.parse(e.target.result);
                if (!Array.isArray(datos)) {
                    throw new Error('El archivo debe contener un array de alumnos');
                }

                // Validar estructura básica de los datos
                const datosValidos = datos.every(alumno => 
                    alumno.id && alumno.nombre && alumno.apellido && Array.isArray(alumno.materias)
                );

                if (!datosValidos) {
                    throw new Error('Estructura de datos inválida');
                }

                // Confirmar importación
                if (confirm(`¿Importar ${datos.length} alumnos? Esto reemplazará los datos actuales.`)) {
                    this.alumnos = datos.map(alumnoData => Alumno.fromJSON(alumnoData));
                    this.nextId = this.calcularNextId();
                    this.guardarAlumnos();
                    this.mostrarExito(`Datos importados exitosamente. ${datos.length} alumnos cargados.`);
                    
                    // Recargar la página para actualizar la interfaz
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (error) {
                this.mostrarError('Error al importar datos: ' + error.message);
            }
        };

        reader.onerror = () => {
            this.mostrarError('Error al leer el archivo');
        };

        reader.readAsText(file);
        
        // Limpiar el input file
        event.target.value = '';
    }

    // Limpiar todos los datos
    limpiarTodosLosDatos() {
        try {
            localStorage.removeItem(this.storageKey);
            this.alumnos = [];
            this.nextId = 1;
            this.mostrarExito('Todos los datos han sido eliminados');
            return true;
        } catch (error) {
            this.mostrarError('Error al limpiar los datos: ' + error.message);
            return false;
        }
    }

    // Obtener estadísticas detalladas
    obtenerEstadisticasDetalladas() {
        const alumnosConMaterias = this.alumnos.filter(a => a.materias.length > 0);
        const alumnosSinMaterias = this.alumnos.filter(a => a.materias.length === 0);
        
        const promedios = alumnosConMaterias.map(a => a.promedio);
        const promedioGeneral = promedios.length > 0 ? 
            promedios.reduce((a, b) => a + b, 0) / promedios.length : 0;

        // Estadísticas por materia
        const materiasStats = {};
        this.alumnos.forEach(alumno => {
            alumno.materias.forEach(materia => {
                if (!materiasStats[materia.nombre]) {
                    materiasStats[materia.nombre] = {
                        nombre: materia.nombre,
                        totalAlumnos: 0,
                        sumaNotas: 0,
                        notas: []
                    };
                }
                materiasStats[materia.nombre].totalAlumnos++;
                materiasStats[materia.nombre].sumaNotas += materia.nota;
                materiasStats[materia.nombre].notas.push(materia.nota);
            });
        });

        // Calcular promedios por materia
        Object.keys(materiasStats).forEach(materia => {
            const stats = materiasStats[materia];
            stats.promedio = stats.sumaNotas / stats.totalAlumnos;
            stats.maxNota = Math.max(...stats.notas);
            stats.minNota = Math.min(...stats.notas);
        });

        return {
            general: {
                totalAlumnos: this.alumnos.length,
                totalConMaterias: alumnosConMaterias.length,
                totalSinMaterias: alumnosSinMaterias.length,
                promedioGeneral: promedioGeneral,
                aprobados: this.alumnos.filter(a => a.estado === "Aprobado").length,
                desaprobados: this.alumnos.filter(a => a.estado === "Desaprobado").length,
                sinCalificaciones: this.alumnos.filter(a => a.estado === "Sin calificaciones").length
            },
            porMateria: materiasStats
        };
    }

    // MÉTODOS DE UTILIDAD PARA NOTIFICACIONES

    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }

    mostrarAdvertencia(mensaje) {
        this.mostrarNotificacion(mensaje, 'warning');
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Remover notificaciones existentes
        const toastsExistentes = document.querySelectorAll('.toast');
        toastsExistentes.forEach(toast => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });

        // Crear notificación toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;
        
        const icono = tipo === 'success' ? 'check-circle' : 
                     tipo === 'error' ? 'exclamation-circle' :
                     tipo === 'warning' ? 'exclamation-triangle' : 'info-circle';

        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${icono}"></i>
                <span>${mensaje}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Estilos para el toast
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${this.obtenerColorNotificacion(tipo)};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            max-width: 400px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        `;

        // Estilos para el contenido del toast
        const toastContent = toast.querySelector('.toast-content');
        toastContent.style.cssText = `
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        `;

        // Estilos para el botón de cerrar
        const toastClose = toast.querySelector('.toast-close');
        toastClose.style.cssText = `
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background 0.3s;
        `;

        toastClose.addEventListener('mouseover', function() {
            this.style.background = 'rgba(255,255,255,0.2)';
        });

        toastClose.addEventListener('mouseout', function() {
            this.style.background = 'none';
        });

        document.body.appendChild(toast);

        // Remover automáticamente después de 5 segundos
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }, 5000);
    }

    obtenerColorNotificacion(tipo) {
        const colores = {
            success: '#2ecc71',
            error: '#e74c3c',
            warning: '#f39c12',
            info: '#3498db'
        };
        return colores[tipo] || colores.info;
    }

    // MÉTODOS DE VALIDACIÓN ADICIONALES

    validarAlumnoExiste(id) {
        return this.alumnos.some(alumno => alumno.id === id);
    }

    validarMateriaExiste(id, nombreMateria) {
        const alumno = this.obtenerPorId(id);
        if (!alumno) return false;
        return alumno.materias.some(m => m.nombre.toLowerCase() === nombreMateria.toLowerCase());
    }

    obtenerMateriasUnicas() {
        const materias = new Set();
        this.alumnos.forEach(alumno => {
            alumno.materias.forEach(materia => {
                materias.add(materia.nombre);
            });
        });
        return Array.from(materias).sort();
    }

    // Backup automático
    crearBackupAutomatico() {
        try {
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const backupKey = `${this.storageKey}_backup_${timestamp}`;
            const data = localStorage.getItem(this.storageKey);
            if (data) {
                localStorage.setItem(backupKey, data);
            }
        } catch (error) {
            console.error('Error creando backup automático:', error);
        }
    }

    // Restaurar desde backup
    restaurarDesdeBackup(backupKey) {
        try {
            const data = localStorage.getItem(backupKey);
            if (data) {
                localStorage.setItem(this.storageKey, data);
                this.alumnos = this.cargarAlumnos();
                this.nextId = this.calcularNextId();
                this.mostrarExito('Backup restaurado exitosamente');
                return true;
            }
            return false;
        } catch (error) {
            this.mostrarError('Error al restaurar backup: ' + error.message);
            return false;
        }
    }
}

// Agregar estilos para las animaciones del toast
if (!document.querySelector('#toast-styles')) {
    const toastStyles = document.createElement('style');
    toastStyles.id = 'toast-styles';
    toastStyles.textContent = `
        @keyframes slideInRight {
            from { 
                transform: translateX(100%); 
                opacity: 0; 
            }
            to { 
                transform: translateX(0); 
                opacity: 1; 
            }
        }
        @keyframes slideOutRight {
            from { 
                transform: translateX(0); 
                opacity: 1; 
            }
            to { 
                transform: translateX(100%); 
                opacity: 0; 
            }
        }
        
        .toast-success { background: #2ecc71 !important; }
        .toast-error { background: #e74c3c !important; }
        .toast-warning { background: #f39c12 !important; }
        .toast-info { background: #3498db !important; }
    `;
    document.head.appendChild(toastStyles);
}