// --- Logique d'affichage et de filtrage ---
const appsGrid = document.getElementById('apps-grid');
const serviceFilterMenu = document.getElementById('service-filter-menu');
const categoryFilterMenu = document.getElementById('category-filter-menu');
const currentYearSpan = document.getElementById('currentYear');
const searchInput = document.getElementById('search-input');


let currentServiceFilter = 'Tous'; 
let currentCategoryFilter = 'Toutes'; 
let currentSearchTerm = '';

const globalCategoriesForServiceFilter = ["Ressources Humaines", "Communication"]; 

// Fonction pour afficher les applications
function renderApplications(appsToRender) {
    appsGrid.innerHTML = ''; 
    if (appsToRender.length === 0) {
        appsGrid.innerHTML = '<p class="text-slate-500 col-span-full text-center py-12 text-lg">Aucune application ne correspond à ces filtres.</p>';
        return;
    }
    appsToRender.sort((a, b) => a.name.localeCompare(b.name));

    appsToRender.forEach(app => {
        let iconBackground = 'bg-blue-100'; // Default for national or unknown
        let iconColor = app.iconColor || 'text-blue-600';
        let scopeIconHtml = '';

        if (app.scope === 'local') {
            iconBackground = 'bg-green-100';
            iconColor = 'text-green-700';
            // Icône Font Awesome pour local
            scopeIconHtml = `
                <div class="scope-icon-container" title="Application Locale (Île-de-France)">
                    <i class="fas fa-map-pin text-green-600"></i>
                </div>`;
        } else if (app.scope === 'national') {
            // Icône Font Awesome pour national
             scopeIconHtml = `
                <div class="scope-icon-container" title="Application Nationale">
                   <i class="fas fa-flag text-blue-600"></i>
                </div>`;
        }


        const appCard = `
            <a href="${app.url.startsWith('#') ? 'javascript:void(0);': app.url}" ${app.url.startsWith('#') ? '' : 'target="_blank" rel="noopener noreferrer"'} 
               class="app-card bg-white p-5 rounded-xl shadow-md hover:shadow-lg flex flex-col items-center text-center h-full ${app.url.startsWith('#') ? 'cursor-not-allowed opacity-70' : ''}">
                ${scopeIconHtml}
                <div class="w-20 h-20 ${iconBackground} rounded-full flex items-center justify-center mb-5 text-3xl">
                    <i class="fas ${app.icon} ${iconColor}"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-700 mb-1 flex-grow">${app.name}</h3>
                <p class="text-sm text-slate-500 mt-auto">${app.service}</p>
                <p class="text-xs text-slate-400 mt-1">${app.category}</p>
            </a>
        `;
        appsGrid.innerHTML += appCard;
    });
}

// Fonction pour appliquer le filtre actif et afficher les applications
function applyFiltersAndRender() {
    let appsToShow = [...applications];
    const searchTerm = currentSearchTerm.toLowerCase();

    // Priorité au filtre actif le plus récent (Service ou Catégorie)
    if (currentServiceFilter !== 'Tous') {
         appsToShow = appsToShow.filter(app => 
             app.service === currentServiceFilter || 
             globalCategoriesForServiceFilter.includes(app.category)
         );
    } else if (currentCategoryFilter !== 'Toutes') {
         appsToShow = appsToShow.filter(app => app.category === currentCategoryFilter);
    }
    // Si les deux sont à "Tous"/"Toutes", appsToShow reste la liste complète.

    // Appliquer la recherche textuelle en dernier
    if (searchTerm) {
        appsToShow = appsToShow.filter(app => app.name.toLowerCase().includes(searchTerm));
    }
    
    renderApplications(appsToShow); 
}

// Fonction générique pour créer un bouton de filtre
function createFilterButton(value, filterType, menuElement, isActive) {
     const filterButton = document.createElement('button');
     filterButton.className = 'filter-button block w-full text-left px-4 py-2.5 text-sm text-slate-600 rounded-md hover:bg-blue-50 hover:text-blue-600 transition-colors focus:outline-none focus:ring-1 focus:ring-blue-500';
     filterButton.textContent = value;
     
     if (isActive) {
         filterButton.classList.add('active'); 
     }

     filterButton.onclick = () => {
        if (filterType === 'service') {
            currentServiceFilter = value;
            currentCategoryFilter = 'Toutes'; // Réinitialiser l'autre filtre
        } else if (filterType === 'category') {
            currentCategoryFilter = value;
            currentServiceFilter = 'Tous'; // Réinitialiser l'autre filtre
        }
         
        // Mettre à jour l'état actif des boutons dans les deux listes
        renderServiceFilters(); 
        renderCategoryFilters(); 

        applyFiltersAndRender(); 
     };
     menuElement.appendChild(filterButton);
}


// Fonction pour générer les filtres de service
function renderServiceFilters() {
    const servicesSet = new Set(applications.map(app => app.service).filter(s => s && s.trim() !== "" && s !== "À définir"));
    const services = ['Tous', ...Array.from(servicesSet).sort()]; 
     if (applications.some(app => app.service === "À définir")) {
         services.push("À définir");
     }
    serviceFilterMenu.innerHTML = ''; 
    services.forEach(service => createFilterButton(service, 'service', serviceFilterMenu, service === currentServiceFilter));
}

// Fonction pour générer les filtres de catégorie
function renderCategoryFilters() {
    const categoriesSet = new Set(applications.map(app => app.category).filter(c => c && c.trim() !== "" && c !== "À définir"));
    const categories = ['Toutes', ...Array.from(categoriesSet).sort()]; 
     if (applications.some(app => app.category === "À définir")) {
         categories.push("À définir");
     }
    categoryFilterMenu.innerHTML = ''; 
    categories.forEach(category => createFilterButton(category, 'category', categoryFilterMenu, category === currentCategoryFilter));
}

// Fonction pour initialiser les accordéons
function setupAccordions() {
    const accordions = document.querySelectorAll('.accordion-toggle');
    accordions.forEach(accordion => {
        const content = accordion.nextElementSibling;
        const icon = accordion.querySelector('.accordion-icon');
        // Forcer l'état initial fermé
        accordion.setAttribute('aria-expanded', 'false');
        content.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';


        accordion.addEventListener('click', () => {
            const isExpanded = accordion.getAttribute('aria-expanded') === 'true';
            accordion.setAttribute('aria-expanded', !isExpanded);
            content.classList.toggle('hidden'); 
            icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
        });
    });
}

// Initialisation lors du chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    if (currentYearSpan) {
        currentYearSpan.textContent = new Date().getFullYear(); 
    }

    // Écouteur pour la barre de recherche
    searchInput.addEventListener('input', (e) => {
        currentSearchTerm = e.target.value;
        applyFiltersAndRender();
    });

    renderServiceFilters(); 
    renderCategoryFilters(); 
    setupAccordions(); 
    applyFiltersAndRender(); 
});