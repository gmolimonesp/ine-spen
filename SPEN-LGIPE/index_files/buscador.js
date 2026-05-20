// CONTROLADOR DE CAMBIO DE PESTAÑAS (Preserva el texto de búsqueda)
function openBook(evt, bookId) {
    var i, bookContent, tabButtons;
    
    bookContent = document.getElementsByClassName("book-content");
    for (i = 0; i < bookContent.length; i++) {
        bookContent[i].classList.remove("active");
    }

    tabButtons = document.getElementsByClassName("tab-button");
    for (i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove("active");
    }

    document.getElementById(bookId).classList.add("active");
    if(evt) evt.currentTarget.classList.add("active");

    // MODIFICACIÓN: Ya no se vacía el input. Ejecuta la búsqueda automáticamente
    // en el nuevo libro usando la palabra que ya estaba escrita.
    searchInActiveTab();
}

// BUSCADOR INTELIGENTE CON RESALTADO EN TIEMPO REAL
function searchInActiveTab() {
    var input, filter, activeTab, table, tr, td, i, j;
    input = document.getElementById("searchInput");
    filter = input.value.trim().toUpperCase();
    
    activeTab = document.querySelector(".book-content.active");
    if (!activeTab) return;
    
    table = activeTab.getElementsByTagName("table");
    if (!table || table.length === 0) return;
    
    tr = table[0].getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        if (tr[i].classList.contains('row-descanso')) {
            tr[i].style.display = filter === "" ? "" : "none";
            continue;
        }

        td = tr[i].getElementsByTagName("td");
        var rowContainsFilter = false;
        
        removeHighlight(tr[i]);
        
        if (filter !== "") {
            for (j = 0; j < td.length; j++) {
                if (td[j]) {
                    var text = td[j].textContent || td[j].innerText;
                    if (text.toUpperCase().indexOf(filter) > -1) {
                        rowContainsFilter = true;
                        highlightText(td[j], input.value.trim());
                    }
                }
            }
            tr[i].style.display = rowContainsFilter ? "" : "none";
        } else {
            tr[i].style.display = "";
        }
    }
}

function highlightText(element, needle) {
    if (!needle) return;
    var escapedNeedle = needle.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    var regex = new RegExp("(" + escapedNeedle + ")", "gi");
    
    var walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null, false);
    var textNodes = [];
    while (walker.nextNode()) { textNodes.push(walker.currentNode); }
    
    textNodes.forEach(function(node) {
        var matches = node.nodeValue.match(regex);
        if (matches) {
            var span = document.createElement('span');
            span.innerHTML = node.nodeValue.replace(regex, '<mark class="match-highlight">$1</mark>');
            node.parentNode.insertBefore(span, node);
            node.parentNode.removeChild(node);
        }
    });
}

function removeHighlight(element) {
    var highlights = element.querySelectorAll('mark.match-highlight');
    highlights.forEach(function(highlight) {
        var parent = highlight.parentNode;
        parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
        parent.normalize();
    });
}

// ==========================================================================
// 📌 DETECCIÓN DE SCROLL PARA EL BOTÓN "TOP"
// ==========================================================================
window.onscroll = function() { scrollControl() };

function scrollControl() {
    var mybutton = document.getElementById("btnTop");
    // Si baja más de 300px desde el inicio, el botón aparece con estilo flex
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
        mybutton.style.display = "flex";
    } else {
        mybutton.style.display = "none";
    }
}

// Función de retorno al inicio de la pantalla con suavizado técnico
function topFunction() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth' /* Transición suave de lectura */
    });
}
