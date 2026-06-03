document.addEventListener("DOMContentLoaded", () => {
    
    // --- DETECCIÓN DESDE EL HTML (BODY) ---
    const leyActiva = document.body.getAttribute("data-ley") || "HOME";

    const selectorTablas = document.getElementById("navegador-tablas");
    const contenedorMenuLeyes = document.getElementById("menu-leyes-dinamico");
    const botonLeyActiva = document.getElementById("boton-ley-activa");
    
    // Limpiamos la ruta para obtener solo el nombre del archivo limpio
    const paginaActual = window.location.pathname.split("/").pop().split("?")[0].split("#")[0];

    // --- PASO 1: CARGAR EL MENÚ SUPERIOR DESDE LEYES.XML ---
    if (contenedorMenuLeyes) {
        fetch("leyes.xml")
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(data, "text/xml");
                const listaLeyes = xmlDoc.getElementsByTagName("ley");

                contenedorMenuLeyes.innerHTML = "";

                for (let i = 0; i < listaLeyes.length; i++) {
                    const sigla = listaLeyes[i].getElementsByTagName("sigla")[0].textContent;
                    const nombre = listaLeyes[i].getElementsByTagName("nombre")[0].textContent;
                    const archivo = listaLeyes[i].getElementsByTagName("archivo")[0].textContent;

                    const enlace = document.createElement("a");
                    enlace.href = archivo;
                    enlace.innerHTML = `👉 <strong>${sigla}</strong> - ${nombre}`;
                    contenedorMenuLeyes.appendChild(enlace);
                }

                if (botonLeyActiva) {
                    botonLeyActiva.innerHTML = `📚 Ley Activa: ${leyActiva} ▾`;
                }
            })
            .catch(error => console.error("Error cargando leyes.xml:", error));
    }

    // --- PASO 2: FILTRADO Y NAVEGACIÓN GENERAL DESDE TABLAS.XML ---
    if (selectorTablas) {
        fetch("tablas.xml")
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(data, "text/xml");
                const listadoTablas = xmlDoc.getElementsByTagName("tabla");
                
                // Limpieza absoluta inicial del selector
                selectorTablas.innerHTML = "";

                let tablasEncontradas = 0;

                // Si la ley activa es HOME, agregamos la opción por defecto al inicio
                if (leyActiva === "HOME") {
                    const opcionDefecto = document.createElement("option");
                    opcionDefecto.value = "";
                    opcionDefecto.textContent = "-- Selecciona una Tabla --";
                    selectorTablas.appendChild(opcionDefecto);
                }

                // Recorremos el XML para inyectar las opciones correspondientes
                for (let i = 0; i < listadoTablas.length; i++) {
                    const leyPertenece = listadoTablas[i].getElementsByTagName("ley")[0].textContent;
                    const nombre = listadoTablas[i].getElementsByTagName("nombre")[0].textContent;
                    const archivo = listadoTablas[i].getElementsByTagName("archivo")[0].textContent;
                    
                    // Filtrado: si es HOME pasan todas, si es una ley exige coincidencia exacta
                    if (leyActiva === "HOME" || leyPertenece === leyActiva) {
                        tablasEncontradas++;

                        const opcion = document.createElement("option");
                        opcion.value = archivo;
                        opcion.textContent = nombre;
                        
                        if (archivo === paginaActual) {
                            opcion.selected = true;
                        }
                        selectorTablas.appendChild(opcion);
                    }
                }

                // Si la ley está vacía en el tablas.xml
                if (tablasEncontradas === 0) {
                    selectorTablas.innerHTML = ""; 
                    const opcionVacia = document.createElement("option");
                    opcionVacia.value = "";
                    opcionVacia.textContent = `⚠️ (Sin tablas de análisis para ${leyActiva})`;
                    selectorTablas.appendChild(opcionVacia);
                }
            })
            .catch(error => console.error("Error leyendo tablas.xml:", error));

        // AJUSTE AQUÍ: Evitamos que intente navegar si el valor es nulo o es el carácter neutro (#)
        selectorTablas.addEventListener("change", (e) => {
            const destino = e.target.value;
            if (destino && destino !== "#") {
                window.location.href = destino;
            }
        });
    }

    // --- PASO 3: FILTRADO INTELIGENTE INTEGRADO DE COLUMNAS (MÓDULO 2) ---
    const filtros = document.querySelectorAll(".filtro-select");
    const filas = document.querySelectorAll("#tabla-dinamica tbody tr");

    function ejecutarFiltrado() {
        filas.forEach(fila => {
            let mostrarFila = true;

            filtros.forEach(select => {
                if (select.value !== "") {
                    const nombreAtributo = select.id.replace("filtro-", "data-");
                    const valorAtributoFila = fila.getAttribute(nombreAtributo);

                    if (valorAtributoFila !== select.value) {
                        mostrarFila = false;
                    }
                }
            });

            fila.style.display = mostrarFila ? "" : "none";
        });
    }

    filtros.forEach(select => {
        select.addEventListener("change", ejecutarFiltrado);
    });
});