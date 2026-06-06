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

                // =================================================================
                // 🔥 MODIFICACIÓN 1: MANDAMOS A LLAMAR TU FUNCIÓN AQUÍ, PASANDO EL XML
                // =================================================================
                if (leyActiva !== "HOME") {
                    generarNavegacionLineal(xmlDoc, leyActiva);
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

    // =================================================================
    // Versión Final: Alineación de extremos, MAYÚSCULAS y ubicación al FINAL
    // =================================================================
    function generarNavegacionLineal(xmlDoc, leyActiva) {
        // --- INYECCIÓN AUTOMÁTICA DE ESTILOS CSS ---
        if (!document.getElementById("estilos-navegacion-secuencial")) {
            const estilos = document.createElement("style");
            estilos.id = "estilos-navegacion-secuencial"; // <-- CORREGIDO: Aquí ya tiene la 'e'
            estilos.textContent = `
                .navegacion-secuencial-contenedor {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background-color: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    padding: 8px 15px;
                    margin-top: 15px;
                    margin-bottom: 15px;
                    font-family: sans-serif;
                }
                .bloque-nav-izq {
                    display: flex;
                    justify-content: flex-start;
                    flex: 1;
                }
                .bloque-nav-der {
                    display: flex;
                    justify-content: flex-end;
                    flex: 1;
                    text-align: right;
                }
                .btn-nav {
                    color: #1a365d;
                    text-decoration: none;
                    font-weight: bold;
                    font-size: 12.5px;
                    transition: color 0.2s ease-in-out;
                }
                .btn-nav:hover {
                    color: #2b4c7e;
                    text-decoration: none;
                }
                .btn-nav.disabled {
                    color: #adb5bd;
                    pointer-events: none;
                    cursor: default;
                    user-select: none;
                }
            `;
            document.head.appendChild(estilos);
        }

        // 1. Obtener el archivo HTML actual
        const archivoActual = window.location.pathname.split("/").pop();

        // 2. Filtrar las tablas de la ley (omitiendo la cabecera '#')
        const nodosTablas = Array.from(xmlDoc.getElementsByTagName("tabla")).filter(nodo => {
            const ley = nodo.getElementsByTagName("ley")[0].textContent;
            const archivo = nodo.getElementsByTagName("archivo")[0].textContent;
            return ley === leyActiva && archivo !== "#";
        });

        const N = nodosTablas.length;
        if (N === 0) return;

        // 3. Obtener el índice actual de la página abierta
        const idxActual = nodosTablas.findIndex(nodo => {
            return nodo.getElementsByTagName("archivo")[0].textContent === archivoActual;
        });

        // Crear el contenedor de navegación
        const contenedorNav = document.createElement("div");
        contenedorNav.className = "navegacion-secuencial-contenedor";

        // Variables para armar las etiquetas HTML de los botones
        let htmlBotonAnterior = '';
        let htmlBotonSiguiente = '';

        // ================= REGLA 1: CONTROL DEL BOTÓN "ANTERIOR" (<<) =================
        if (idxActual <= 0) {
            htmlBotonAnterior = `<span class="btn-nav disabled">&lt;&lt;</span>`;
        } else {
            const archivoAnt = nodosTablas[idxActual - 1].getElementsByTagName("archivo")[0].textContent;
            const nombreAnt = nodosTablas[idxActual - 1].getElementsByTagName("nombre")[0].textContent;
            // .toUpperCase() fuerza el texto a MAYÚSCULAS
            htmlBotonAnterior = `<a href="${archivoAnt}" class="btn-nav">&lt;&lt; ${nombreAnt.toUpperCase()}</a>`;
        }

        // ================= REGLA 2: CONTROL DEL BOTÓN "SIGUIENTE" (>>) =================
        if (idxActual === -1 || idxActual === N - 1) {
            htmlBotonSiguiente = `<span class="btn-nav disabled">&gt;&gt;</span>`;
        } else {
            const archivoSig = nodosTablas[idxActual + 1].getElementsByTagName("archivo")[0].textContent;
            const nombreSig = nodosTablas[idxActual + 1].getElementsByTagName("nombre")[0].textContent;
            // .toUpperCase() fuerza el texto a MAYÚSCULAS
            htmlBotonSiguiente = `<a href="${archivoSig}" class="btn-nav">&gt;&gt; ${nombreSig.toUpperCase()}</a>`;
        }

        // 4. Ensamble de bloques
        contenedorNav.innerHTML = `
            <div class="bloque-nav-izq">${htmlBotonAnterior}</div>
            <div class="bloque-nav-der">${htmlBotonSiguiente}</div>
        `;

        // 5. CORREGIDO: Colocar el contenedor al FINAL de la tabla
        const tablaMaestra = document.getElementById("tabla-dynamica") || document.getElementById("tabla-dinamica");
        if (tablaMaestra) {
            // Se inserta justo después de la tabla en su mismo contenedor padre
            tablaMaestra.parentNode.appendChild(contenedorNav);
        }
    }
});