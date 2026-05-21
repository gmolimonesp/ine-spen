document.addEventListener("DOMContentLoaded", () => {
    
    // --- MÓDULO 1: NAVEGACIÓN DINÁMICA CON XML ---
    const selectorTablas = document.getElementById("navegador-tablas");
    
    if (selectorTablas) {
        fetch("tablas.xml")
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(data, "text/xml");
                const listadoTablas = xmlDoc.getElementsByTagName("tabla");
                
                // Obtener el nombre del archivo HTML actual donde estamos parados
                const paginaActual = window.location.pathname.split("/").pop();

                for (let i = 0; i < listadoTablas.length; i++) {
                    const nombre = listadoTablas[i].getElementsByTagName("nombre")[0].textContent;
                    const archivo = listadoTablas[i].getElementsByTagName("archivo")[0].textContent;
                    
                    const opcion = document.createElement("option");
                    opcion.value = archivo;
                    opcion.textContent = nombre;
                    
                    // Si es la página actual, la dejamos seleccionada en el menú
                    if (archivo === paginaActual) {
                        opcion.selected = true;
                    }
                    
                    selectorTablas.appendChild(opcion);
                }
            })
            .catch(error => console.error("Error leyendo el archivo XML del menú:", error));

        // Evento para cambiar de tabla al seleccionar una opción
        selectorTablas.addEventListener("change", (e) => {
            if (e.target.value) {
                window.location.href = e.target.value;
            }
        });
    }

    // --- MÓDULO 2: FILTRADO INTELIGENTE INTEGRADO ---
    // Detectamos todos los selects de filtros que existan en la tabla actual
    const filtros = document.querySelectorAll(".filtro-select");
    const filas = document.querySelectorAll("#tabla-dinamica tbody tr");

    function ejecutarFiltrado() {
        filas.forEach(fila => {
            let mostrarFila = true;

            // Revisamos cada filtro presente en el encabezado
            filtros.forEach(select => {
                if (select.value !== "") {
                    // Mapeamos el ID del filtro (ej: "filtro-tipo") a su data-attribute (ej: "data-tipo")
                    const nombreAtributo = select.id.replace("filtro-", "data-");
                    const valorAtributoFila = fila.getAttribute(nombreAtributo);

                    // Si la fila no coincide con la selección del filtro, la marcamos para ocultar
                    if (valorAtributoFila !== select.value) {
                        mostrarFila = false;
                    }
                }
            });

            // Aplicamos el cambio visual
            fila.style.display = mostrarFila ? "" : "none";
        });
    }

    // Añadimos el escuchador de eventos a todos los filtros activos de la página
    filtros.forEach(select => {
        select.addEventListener("change", ejecutarFiltrado);
    });
});
