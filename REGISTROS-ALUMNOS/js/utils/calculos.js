class CalculosUtils {
    static calcularPromedio(materias) {
        if (!materias || materias.length === 0) return 0;
        
        const sumaNotas = materias.reduce((total, materia) => total + materia.nota, 0);
        return sumaNotas / materias.length;
    }

    static determinarEstado(promedio) {
        if (promedio === 0) return "Sin calificaciones";
        return promedio >= 10 ? "Aprobado" : "Desaprobado"; // Cambiado a 10
    }

    static validarNota(nota) {
        const numNota = parseFloat(nota);
        return !isNaN(numNota) && numNota >= 0 && numNota <= 20; // Cambiado a 20
    }

    static validarDatosAlumno(nombre, apellido) {
        return nombre && apellido && 
               nombre.trim() !== '' && 
               apellido.trim() !== '' &&
               nombre.length >= 2 &&
               apellido.length >= 2;
    }

    static formatearPromedio(promedio) {
        return promedio.toFixed(2);
    }

    static obtenerColorNota(nota) {
        return nota >= 10 ? 'nota-aprobada' : 'nota-desaprobada'; // Cambiado a 10
    }

    static obtenerClaseEstado(estado) {
        switch(estado) {
            case 'Aprobado': return 'status-aprobado';
            case 'Desaprobado': return 'status-desaprobado';
            default: return 'status-sin-calificaciones';
        }
    }

    static calcularEstadisticas(alumnos) {
        const total = alumnos.length;
        const aprobados = alumnos.filter(a => a.estado === "Aprobado").length;
        const desaprobados = alumnos.filter(a => a.estado === "Desaprobado").length;
        const sinCalificaciones = alumnos.filter(a => a.estado === "Sin calificaciones").length;
        
        const promedios = alumnos.map(a => a.promedio).filter(p => p > 0);
        const promedioGeneral = promedios.length > 0 ? 
            promedios.reduce((a, b) => a + b, 0) / promedios.length : 0;

        return {
            total,
            aprobados,
            desaprobados,
            sinCalificaciones,
            promedioGeneral
        };
    }
}