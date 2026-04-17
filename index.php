<?php
// index.php - Page d'accueil
require_once 'auth.php';
check_auth();
require_once 'config/database.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$db = Database::getInstance();
$stats = [
    'total_documents' => $db->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
    'total_employes' => $db->query("SELECT COUNT(*) FROM employes")->fetchColumn(),
    'dernier_scan' => 'Non disponible'
];

$pageTitle = "Accueil - Gestion d'Archives";
$currentPage = 'index';
include 'templates/header.php';
?>
    <style>
        .search-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 18px;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .suggestions {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            display: none;
        }

        .suggestion-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }

        .suggestion-item:hover {
            background: #f8f9fa;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
        }

        .employee-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 25px;
            display: none;
        }

        .employee-header {
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .employee-name {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .folder-type {
            margin-bottom: 20px;
        }

        .folder-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .folder-content {
            display: none;
        }

        .document-item {
            padding: 10px 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 8px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .document-name {
            font-weight: 500;
            color: #2c3e50;
        }

        .document-size {
            color: #7f8c8d;
            font-size: 14px;
        }

        .toggle-icon {
            transition: transform 0.3s;
        }

        .toggle-icon.rotated {
            transform: rotate(90deg);
        }
        .title{padding: 5px;     color: #2c3e50;
    margin-bottom: 20px;
    font-weight: 300;
    border-bottom: 2px solid #e74c3c;
    padding-bottom: 10px;}

    </style>
<?php
// Fin styles
?>
        <div class="title"><h1>Gestion d'Archives</h1></div>

        <div class="search-container">
            <input type="text" id="search-input" class="search-input" placeholder="Rechercher un employé (nom, prénom, matricule)...">
            <div id="suggestions" class="suggestions"></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_documents']; ?></div>
                <div class="stat-label">Documents indexés</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_employes']; ?></div>
                <div class="stat-label">Employés</div>
            </div>
        </div>

        <div id="employee-details" class="employee-details">
            <div class="employee-header">
                <div class="employee-name" id="employee-name"></div>
                <div id="employee-matricule" style="color: #7f8c8d;"></div>
            </div>
            <div id="folders-container"></div>
        </div>

        <div id="all-matricules" style="margin-top: 25px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 15px; color: #2c3e50; font-weight: 500;">Liste des matricules disponibles</h3>
            <div id="matricules-grid" style="display: flex; flex-direction: column; gap: 8px;">
                <div style="color: #7f8c8d;">Chargement...</div>
            </div>
        </div>

        
    </div>

    <!-- Fenêtre Modale pour l'aperçu image -->
    <div id="image-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); overflow: auto;">
        <span id="close-modal" style="position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
        <div style="display: flex; justify-content: center; align-items: center; min-height: 100%; padding: 20px;">
            <img id="modal-content" style="max-width: 95%; max-height: 90vh; border-radius: 5px; box-shadow: 0 0 20px rgba(255,255,255,0.2);">
        </div>
        <div id="modal-caption" style="text-align: center; color: white; padding: 10px 0; font-size: 18px;"></div>
    </div>

    <script>
        const searchInput = document.getElementById('search-input');
        const suggestionsDiv = document.getElementById('suggestions');
        const employeeDetails = document.getElementById('employee-details');
        const employeeName = document.getElementById('employee-name');
        const employeeMatricule = document.getElementById('employee-matricule');
        const foldersContainer = document.getElementById('folders-container');

        let searchTimeout;

        document.addEventListener('DOMContentLoaded', () => {
            loadAllMatricules();
        });

        window.addEventListener('keydown', function(e) {
            if (e.key === 'F5') {
                e.preventDefault();
                refreshLastScan();
            }
        });

        async function refreshLastScan() {
            try {
                const lastPathResponse = await fetch('api/last-scan-path.php?t=' + Date.now());
                const lastPathData = await lastPathResponse.json();
                const lastPath = lastPathData.path;

                if (!lastPath) {
                    window.location = window.location.pathname + '?t=' + Date.now();
                    return;
                }

                const scanResponse = await fetch('api/scan-folder.php?t=' + Date.now(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ folder_path: lastPath, immediate: true })
                });

                if (!scanResponse.ok) {
                    console.warn('Le scan n\u2019a pas pu se terminer, rechargement des données...');
                    window.location = window.location.pathname + '?t=' + Date.now();
                    return;
                }

                window.location = window.location.pathname + '?t=' + Date.now();
            } catch (error) {
                console.error('Erreur de rafraichissement du scan :', error);
                window.location = window.location.pathname + '?t=' + Date.now();
            }
        }

        async function loadAllMatricules() {
            const grid = document.getElementById('matricules-grid');
            try {
                const response = await fetch('api/all-matricules.php?t=' + Date.now());
                const data = await response.json();
                
                if (data.length === 0) {
                    grid.innerHTML = '<div style="color: #7f8c8d;">Aucun matricule trouvé. Lancez un scan.</div>';
                    return;
                }

                grid.innerHTML = '';
                data.forEach(mat => {
                    const row = document.createElement('div');
                    row.style.display = 'flex';
                    row.style.justifyContent = 'space-between';
                    row.style.alignItems = 'center';
                    row.style.padding = '8px 12px';
                    row.style.background = '#f8f9fa';
                    row.style.borderRadius = '5px';
                    row.style.border = '1px solid #eee';

                    row.innerHTML = `
                        <span style="font-weight: 500; color: #2c3e50;">Matricule: <strong>${mat}</strong></span>
                        <button type="button" class="btn btn-primary" style="padding: 4px 12px; font-size: 13px;">Consulter</button>
                    `;
                    
                    row.style.cursor = 'pointer';
                    row.onclick = () => {
                        searchInput.value = mat;
                        loadEmployeeDetails(mat);
                        // Faire défiler vers les détails
                        document.getElementById('employee-details').scrollIntoView({ behavior: 'smooth' });
                    };
                    grid.appendChild(row);
                });
            } catch (error) {
                grid.innerHTML = '<div style="color: red;">Erreur de chargement</div>';
            }
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`api/search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsDiv.innerHTML = '';

                        if (data.length === 0) {
                            suggestionsDiv.style.display = 'none';
                            return;
                        }

                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.textContent = `${item.matricule} - ${item.nom || ''} ${item.prenom || ''}`.trim();
                            div.addEventListener('click', () => {
                                loadEmployeeDetails(item.matricule);
                                searchInput.value = div.textContent;
                                suggestionsDiv.style.display = 'none';
                            });
                            suggestionsDiv.appendChild(div);
                        });

                        suggestionsDiv.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Erreur recherche:', error);
                        suggestionsDiv.style.display = 'none';
                    });
            }, 300);
        });

        const modal = document.getElementById('image-modal');
        const modalImg = document.getElementById('modal-content');
        const captionText = document.getElementById('modal-caption');
        const closeModal = document.getElementById('close-modal');

        closeModal.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function openPreview(id, filename) {
            modal.style.display = "block";
            modalImg.src = `api/view-image.php?id=${id}`;
            captionText.innerHTML = filename;
        }

        function loadEmployeeDetails(matricule) {
            fetch(`api/matricule.php?matricule=${encodeURIComponent(matricule)}&t=${Date.now()}`)
                .then(response => response.json())
                .then(data => {
                    employeeName.textContent = matricule;
                    employeeMatricule.textContent = matricule;

                    foldersContainer.innerHTML = '';

                    for (const [type, documents] of Object.entries(data)) {
                        const folderDiv = document.createElement('div');
                        folderDiv.className = 'folder-type';

                        const titleDiv = document.createElement('div');
                        titleDiv.className = 'folder-title';
                        titleDiv.innerHTML = `
                            <span>${type} (${documents.length} document${documents.length > 1 ? 's' : ''})</span>
                            <span class="toggle-icon">▶</span>
                        `;

                        const contentDiv = document.createElement('div');
                        contentDiv.className = 'folder-content';

                        documents.forEach(doc => {
                            const docDiv = document.createElement('div');
                            docDiv.className = 'document-item';

                            const size = formatBytes(doc.taille_bytes);
                            const date = doc.date_scan ? new Date(doc.date_scan).toLocaleDateString('fr-FR') : 'N/A';

                            docDiv.innerHTML = `
                                <div>
                                    <div class="document-name">${doc.nom_fichier}</div>
                                    <div class="document-size">${size} • ${date}</div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button class="btn" style="background: #2ecc71; color: white;" onclick="openPreview(${doc.id}, '${doc.nom_fichier}')"> Aperçu</button>
                                    <a href="pdf.php?id=${doc.id}" class="btn btn-primary" target="_blank"> PDF</a>
                                </div>
                            `;

                            contentDiv.appendChild(docDiv);
                        });

                        titleDiv.addEventListener('click', () => {
                            const icon = titleDiv.querySelector('.toggle-icon');
                            if (contentDiv.style.display === 'none' || contentDiv.style.display === '') {
                                contentDiv.style.display = 'block';
                                icon.classList.add('rotated');
                            } else {
                                contentDiv.style.display = 'none';
                                icon.classList.remove('rotated');
                            }
                        });

                        folderDiv.appendChild(titleDiv);
                        folderDiv.appendChild(contentDiv);
                        foldersContainer.appendChild(folderDiv);
                    }

                    employeeDetails.style.display = 'block';
                    employeeDetails.scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => {
                    console.error('Erreur chargement employé:', error);
                });
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 o';
            const k = 1024;
            const sizes = ['o', 'Ko', 'Mo', 'Go'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });
    </script>
<?php include 'templates/footer.php'; ?>
