<?php
// admin.php - Interface d'administration
require_once 'auth.php';
check_auth();
require_once 'config/database.php';

$db = Database::getInstance();
$stats = [
    'total_documents' => $db->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
    'total_employes' => $db->query("SELECT COUNT(*) FROM employes")->fetchColumn(),
    'dernier_scan' => 'Non disponible'
];

try {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS scan_history ('
        . 'chemin TEXT PRIMARY KEY, '
        . 'dernier_scan TIMESTAMP NOT NULL'
        . ')'
    );
    $lastScanStmt = $db->query('SELECT chemin FROM scan_history ORDER BY dernier_scan DESC LIMIT 1');
    $last_scan_path = $lastScanStmt->fetchColumn() ?: 'archives';
} catch (Exception $e) {
    $last_scan_path = 'archives';
}

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

$pageTitle = "Administration - Gestion d'Archives";
$currentPage = 'admin';
$activeColor = '#e74c3c';
include 'templates/header.php';
?>
    <style>
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 300;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
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
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .help-text {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .scan-status-info {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .status-loading {
            background: #e3f2fd;
            color: #0277bd;
            border: 1px solid #0277bd;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .results-container {
            margin-top: 20px;
        }
        
        .result-header {
            font-size: 18px;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .matricule-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .matricule-item {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .matricule-item:hover {
            background: #e9ecef;
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }
        
        .folder-tree {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        
        .folder-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-family: monospace;
            font-size: 14px;
            color: #2c3e50;
        }
        
        .folder-item:last-child {
            border-bottom: none;
        }
    </style>
<?php
// Fin styles
?>
        <h1>Administration</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title">Statistiques</div>
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
        </div>

        <div class="card">
            <div class="card-title">Scanner un dossier</div>
            <form id="scan-form">
                <div class="form-group">
                    <label for="folder-path">Chemin du dossier à scanner:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="folder-path" name="folder_path" value="<?php echo htmlspecialchars($last_scan_path, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ex: /var/www/archives" required style="flex-grow: 1;">
                        <button type="button" id="browse-btn" class="btn btn-primary" style="margin-right: 0;">Parcourir...</button>
                    </div>
                    <div class="help-text">Entrez un chemin absolu ou utilisez le bouton Parcourir.</div>
                </div>
                <button type="submit" class="btn btn-primary">Lancer le scan</button>
            </form>
            
            <!-- Explorateur de fichiers (caché par défaut) -->
            <div id="file-browser" style="display: none; margin-top: 20px; border: 1px solid #ddd; border-radius: 5px; padding: 15px; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 id="current-dir-label" style="font-size: 14px; color: #666; word-break: break-all;">/</h4>
                    <button type="button" id="close-browser" class="btn" style="padding: 2px 8px; background: #ddd;">×</button>
                </div>
                <div id="dir-list" style="max-height: 300px; overflow-y: auto; background: white; border: 1px solid #eee;">
                    <!-- Liste des dossiers chargée en JS -->
                </div>
            </div>

            <div id="scan-status" style="margin-top: 20px;"></div>

            <!-- Historique des scans -->
            <div id="scan-history-section" style="margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                <h4 style="font-size: 16px; color: #2c3e50; margin-bottom: 15px;">Dossiers scannés récemment :</h4>
                <div id="recent-paths" style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Chargé via JS -->
                </div>
            </div>

            <div id="scan-results" style="margin-top: 20px;"></div>
        </div>

        <div class="card">
            <div class="card-title">Actions d'administration</div>
            <button type="button" id="clear-cache-btn" class="btn btn-danger">Vider le cache PDF</button>
            <div id="cache-status" style="margin-top: 10px;"></div>
        </div>
    </div>

    <script>
        const browseBtn = document.getElementById('browse-btn');
        const fileBrowser = document.getElementById('file-browser');
        const dirList = document.getElementById('dir-list');
        const currentDirLabel = document.getElementById('current-dir-label');
        const closeBrowser = document.getElementById('close-browser');
        const folderPathInput = document.getElementById('folder-path');

        if (browseBtn) {
            browseBtn.addEventListener('click', () => {
                fileBrowser.style.display = 'block';
                loadDirectories(folderPathInput.value || '');
            });
        }

        if (closeBrowser) {
            closeBrowser.addEventListener('click', () => {
                fileBrowser.style.display = 'none';
            });
        }

        async function loadDirectories(path) {
            dirList.innerHTML = '<div style="padding: 10px;">Chargement...</div>';
            try {
                const response = await fetch(`api/list-directories.php?path=${encodeURIComponent(path)}`);
                const data = await response.json();
                
                if (data.error) {
                    dirList.innerHTML = `<div style="padding: 10px; color: red;">Erreur: ${data.error}</div>`;
                    return;
                }

                currentDirLabel.textContent = data.current_path;
                
                let html = '';
                
                // Ajouter le lien ".." pour remonter
                if (data.current_path !== data.parent_path) {
                    html += `<div class="dir-item" onclick="loadDirectories('${data.parent_path.replace(/\\/g, '/')}')" style="cursor: pointer; padding: 8px 12px; border-bottom: 1px solid #eee; background: #f0f0f0;">
                        <strong>📁 .. (Parent)</strong>
                    </div>`;
                }

                data.directories.forEach(dir => {
                    html += `
                        <div class="dir-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-bottom: 1px solid #eee;">
                            <span onclick="loadDirectories('${dir.path.replace(/\\/g, '/')}')" style="cursor: pointer; flex-grow: 1;">📂 ${dir.name}</span>
                            <button type="button" class="btn" onclick="selectFolder('${dir.path.replace(/\\/g, '/')}')" style="padding: 2px 8px; font-size: 12px; background: #2ecc71; color: white;">Choisir</button>
                        </div>`;
                });

                if (data.directories.length === 0 && data.current_path === data.parent_path) {
                    html += '<div style="padding: 10px;">Aucun dossier trouvé.</div>';
                }

                dirList.innerHTML = html;
            } catch (error) {
                dirList.innerHTML = `<div style="padding: 10px; color: red;">Erreur réseau: ${error.message}</div>`;
            }
        }

        window.loadDirectories = loadDirectories;

        window.selectFolder = function(path) {
            folderPathInput.value = path;
            fileBrowser.style.display = 'none';
        };

        async function triggerScan(folderPath, immediate = false) {
            const statusDiv = document.getElementById('scan-status');
            const resultsDiv = document.getElementById('scan-results');
            statusDiv.innerHTML = '<div class="scan-status-info status-loading">Scan en cours... Veuillez patienter.</div>';
            resultsDiv.innerHTML = '';

            try {
                const response = await fetch('api/scan-folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        folder_path: folderPath,
                        immediate: immediate ? true : false
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    statusDiv.innerHTML = '<div class="scan-status-info status-error">Erreur: ' + (data.error || 'Erreur lors du scan') + '</div>';
                    return;
                }

                statusDiv.innerHTML = '<div class="scan-status-info status-success">' + data.message + '<br><strong>' + data.documents_count + ' document(s)</strong> trouvé(s)</div>';
                loadScanHistory();

                const matricules = data.matricules || [];
                const folders = data.folders || [];

                let resultsHtml = '<div class="results-container">';
                if (matricules.length > 0) {
                    resultsHtml += '<div class="result-header">Matricules trouvés:</div>';
                    resultsHtml += '<div class="matricule-list">';
                    matricules.forEach(mat => {
                        resultsHtml += '<div class="matricule-item">' + escapeHtml(mat) + '</div>';
                    });
                    resultsHtml += '</div>';
                }
                if (folders.length > 0) {
                    resultsHtml += '<div class="result-header" style="margin-top: 20px;">Structure des dossiers:</div>';
                    resultsHtml += '<div class="folder-tree">';
                    folders.forEach(folder => {
                        resultsHtml += '<div class="folder-item">' + escapeHtml(folder) + '</div>';
                    });
                    resultsHtml += '</div>';
                }
                resultsHtml += '</div>';
                resultsDiv.innerHTML = resultsHtml;
            } catch (error) {
                statusDiv.innerHTML = '<div class="scan-status-info status-error">Erreur réseau: ' + error.message + '</div>';
            }
        }

        async function loadScanHistory() {
            const historyDiv = document.getElementById('recent-paths');
            try {
                const response = await fetch('api/get-history.php');
                const data = await response.json();
                
                if (data.length === 0) {
                    historyDiv.innerHTML = '<div style="color: #7f8c8d; font-style: italic;">Aucun historique.</div>';
                    return;
                }

                historyDiv.innerHTML = '';
                data.forEach(item => {
                    const row = document.createElement('div');
                    row.style.display = 'flex';
                    row.style.justifyContent = 'space-between';
                    row.style.alignItems = 'center';
                    row.style.padding = '10px';
                    row.style.background = '#f8f9fa';
                    row.style.borderRadius = '5px';
                    row.style.border = '1px solid #eee';
                    
                    row.innerHTML = `
                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-grow: 1; margin-right: 15px;">
                            <strong style="font-size: 14px; color: #2c3e50;">${item.chemin}</strong><br>
                            <small style="color: #999;">Dernier scan : ${new Date(item.dernier_scan).toLocaleString('fr-FR')}</small>
                        </div>
                        <button type="button" class="btn btn-primary" style="padding: 5px 12px; font-size: 13px; margin: 0;" onclick="selectFolder('${item.chemin.replace(/\\/g, '/')}')">Réutiliser</button>
                    `;
                    historyDiv.appendChild(row);
                });
            } catch (error) {
                console.error('Erreur historique:', error);
            }
        }

        // Charger l'historique au démarrage
        document.addEventListener('DOMContentLoaded', loadScanHistory);

        const clearCacheBtn = document.getElementById('clear-cache-btn');
        const cacheStatus = document.getElementById('cache-status');

        if (clearCacheBtn) {
            clearCacheBtn.addEventListener('click', async () => {
                if (!confirm('Vider tout le cache PDF ?')) return;
                
                clearCacheBtn.disabled = true;
                cacheStatus.textContent = 'Nettoyage en cours...';
                
                try {
                    const response = await fetch('api/clear-cache.php', { method: 'POST' });
                    const data = await response.json();
                    
                    if (data.error) {
                        cacheStatus.innerHTML = `<span style="color: red;">Erreur: ${data.error}</span>`;
                    } else {
                        cacheStatus.innerHTML = `<span style="color: green;">${data.fichiers_supprimes} fichiers supprimés (${data.espace_liberte})</span>`;
                    }
                } catch (error) {
                    cacheStatus.innerHTML = `<span style="color: red;">Erreur réseau: ${error.message}</span>`;
                } finally {
                    clearCacheBtn.disabled = false;
                }
            });
        }

        document.getElementById('scan-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const folderPath = document.getElementById('folder-path').value.trim();
            if (!folderPath) {
                document.getElementById('scan-status').innerHTML = '<div class="scan-status-info status-error">Veuillez entrer un chemin de dossier</div>';
                return;
            }
            await triggerScan(folderPath, false);
        });

        window.addEventListener('keydown', async function(e) {
            if (e.key === 'F5') {
                const folderPath = document.getElementById('folder-path').value.trim();
                const browserVisible = fileBrowser.style.display === 'block';
                e.preventDefault();

                if (browserVisible) {
                    const currentPath = currentDirLabel.textContent.trim();
                    loadDirectories(currentPath || folderPath || '');
                }

                if (folderPath) {
                    await triggerScan(folderPath, true);
                }
            }
        });

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    </script>
<?php include 'templates/footer.php'; ?>
