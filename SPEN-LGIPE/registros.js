document.addEventListener("DOMContentLoaded", () => {
    // Referencias a los elementos select del DOM
    const filtroTipo = document.getElementById("filtro-tipo");
    const filtroCandidatura = document.getElementById("filtro-candidatura");
    const filtroCoalicion = document.getElementById("filtro-coalicion");
    const filtroAmbito = document.getElementById("filtro-ambito");
    const filtroReelegible = document.getElementById("filtro-reelegible");
    
    // Obtener todas las filas de datos de la tabla
    const filas = document.querySelectorAll("#tabla-actores tbody tr");

    // Función principal de filtrado acumulativo
    function filtrarTabla() {
        const valTipo = filtroTipo.value;
        const valCandidatura = filtroCandidatura.value;
        const valCoalicion = filtroCoalicion.value;
        const valAmbito = filtroAmbito.value;
        const valReelegible = filtroReelegible.value;

        filas.forEach(fila => {
            // Evaluamos si el data-attribute coincide con la selección o si el filtro está vacío
            const coincideTipo = !valTipo || fila.getAttribute("data-tipo") === valTipo;
            const coincideCand = !valCandidatura || fila.getAttribute("data-candidatura") === valCandidatura;
            const coincideCoal = !valCoalicion || fila.getAttribute("data-coalicion") === valCoalicion;
            const coincideAmb = !valAmbito || fila.getAttribute("data-ambito") === valAmbito;
            const coincideReel = !valReelegible || fila.getAttribute("data-reelegible") === valReelegible;

            // Si pasa todos los criterios de filtrado se muestra, si no, se oculta
            if (coincideTipo && coincideCand && coincideCoal && coincideAmb && coincideReel) {
                fila.style.display = "";
            } else {
                fila.style.display = "none";
            }
        });
    }

    // Escuchadores de eventos para ejecutar el filtro ante cualquier cambio de las listas
    filtroTipo.addEventListener("change", filtrarTabla);
    filtroCandidatura.addEventListener("change", filtrarTabla);
    filtroCoalicion.addEventListener("change", filtrarTabla);
    filtroAmbito.addEventListener("change", filtrarTabla);
    filtroReelegible.addEventListener("change", filtrarTabla);
});
