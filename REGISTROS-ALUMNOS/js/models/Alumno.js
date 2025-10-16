class Alumno {
    constructor(id, nombre, apellido, materias = []) {
        this.id = id;
        this.nombre = nombre;
        this.apellido = apellido;
        this.materias = materias;
        this.promedio = 0;
        this.estado = "Sin calificaciones";
        this.actualizarPromedioYEstado();
    }

    agregarMateria(nombre, nota) {
        // Verificar si la materia ya existe
        const materiaExistente = this.materias.find(m => m.nombre.toLowerCase() === nombre.toLowerCase());
        if (materiaExistente) {
            throw new Error(`La materia "${nombre}" ya existe para este alumno`);
        }

        this.materias.push({ 
            nombre: nombre.trim(), 
            nota: parseFloat(nota),
            fecha: new Date().toISOString().split('T')[0]
        });
        this.actualizarPromedioYEstado();
        return this;
    }

    eliminarMateria(nombreMateria) {
        const index = this.materias.findIndex(m => m.nombre.toLowerCase() === nombreMateria.toLowerCase());
        if (index === -1) {
            throw new Error(`La materia "${nombreMateria}" no existe`);
        }
        
        this.materias.splice(index, 1);
        this.actualizarPromedioYEstado();
        return this;
    }

    actualizarPromedioYEstado() {
        if (this.materias.length > 0) {
            const sumaNotas = this.materias.reduce((total, materia) => total + materia.nota, 0);
            this.promedio = sumaNotas / this.materias.length;
            this.estado = this.promedio >= 10 ? "Aprobado" : "Desaprobado"; // Cambiado a 10
        } else {
            this.promedio = 0;
            this.estado = "Sin calificaciones";
        }
    }

    toJSON() {
        return {
            id: this.id,
            nombre: this.nombre,
            apellido: this.apellido,
            materias: this.materias,
            promedio: this.promedio,
            estado: this.estado,
            fechaActualizacion: new Date().toISOString()
        };
    }

    // Métodos estáticos para crear desde JSON
    static fromJSON(data) {
        const alumno = new Alumno(data.id, data.nombre, data.apellido, data.materias);
        alumno.promedio = data.promedio || 0;
        alumno.estado = data.estado || "Sin calificaciones";
        return alumno;
    }
}