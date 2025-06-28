<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/banner_functions.php';
require_once 'classes/BannerStats.php';

// Obter dados dos jogos
$jogos = obterJogosDeHoje();
$totalJogos = count($jogos);

// Calcular número de banners necessários
$jogosPorBanner = 5;
$totalBanners = ceil($totalJogos / $jogosPorBanner);

$pageTitle = "Banner Futebol";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-futbol text-primary-500 mr-3"></i>
        Gerar Banner de Futebol
    </h1>
    <p class="page-subtitle">
        <?php if ($totalJogos > 0): ?>
            <?php echo $totalJogos; ?> jogos disponíveis para hoje
        <?php else: ?>
            Nenhum jogo disponível para hoje
        <?php endif; ?>
    </p>
</div>

<?php if ($totalJogos > 0): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tema Selection -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Escolha o Tema do Banner</h3>
                    <p class="card-subtitle">Selecione o estilo que melhor se adequa ao seu projeto</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tema 1 -->
                        <div class="theme-card" data-theme="1">
                            <div class="theme-preview">
                                <img src="https://i.ibb.co/MJCWzXj/8966-media-3.png" alt="Tema 1" loading="lazy">
                            </div>
                            <div class="theme-info">
                                <h4 class="theme-name">Tema 1 (Clássico)</h4>
                                <p class="theme-description">Banner vertical com layout tradicional</p>
                                <div class="theme-actions">
                                    <button class="btn btn-primary w-full select-theme-btn" data-theme="1">
                                        <i class="fas fa-check"></i>
                                        Selecionar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tema 2 -->
                        <div class="theme-card" data-theme="2">
                            <div class="theme-preview">
                                <img src="https://i.ibb.co/6R7F9Y0/8966-media-2.png" alt="Tema 2" loading="lazy">
                            </div>
                            <div class="theme-info">
                                <h4 class="theme-name">Tema 2 (Moderno)</h4>
                                <p class="theme-description">Banner compacto com layout horizontal</p>
                                <div class="theme-actions">
                                    <button class="btn btn-primary w-full select-theme-btn" data-theme="2">
                                        <i class="fas fa-check"></i>
                                        Selecionar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tema 3 -->
                        <div class="theme-card" data-theme="3">
                            <div class="theme-preview">
                                <img src="https://i.ibb.co/x8PCQM3/8966-media-1.png" alt="Tema 3" loading="lazy">
                            </div>
                            <div class="theme-info">
                                <h4 class="theme-name">Tema 3 (Premium)</h4>
                                <p class="theme-description">Banner premium com design especial</p>
                                <div class="theme-actions">
                                    <button class="btn btn-primary w-full select-theme-btn" data-theme="3">
                                        <i class="fas fa-check"></i>
                                        Selecionar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tema 4 (Novo) -->
                        <div class="theme-card" data-theme="4">
                            <div class="theme-preview">
                                <img src="https://i.ibb.co/MJCWzXj/8966-media-3.png" alt="Tema 4" loading="lazy">
                            </div>
                            <div class="theme-info">
                                <h4 class="theme-name">Tema 4 (Agenda Esportiva)</h4>
                                <p class="theme-description">Banner com layout de agenda esportiva</p>
                                <div class="theme-actions">
                                    <button class="btn btn-primary w-full select-theme-btn" data-theme="4">
                                        <i class="fas fa-check"></i>
                                        Selecionar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Banner Preview (hidden initially) -->
            <div id="bannerPreview" class="card mt-6" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">Prévia do Banner</h3>
                    <p class="card-subtitle">Visualize como ficará seu banner</p>
                </div>
                <div class="card-body">
                    <div class="banner-preview-container">
                        <div id="previewLoading" class="preview-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Carregando prévia...</p>
                        </div>
                        <img id="previewImage" src="" alt="Prévia do Banner" class="banner-preview-image" style="display: none;">
                    </div>
                    
                    <div class="banner-navigation mt-4">
                        <div class="flex justify-between items-center">
                            <button id="prevBanner" class="btn btn-secondary" disabled>
                                <i class="fas fa-chevron-left"></i>
                                Anterior
                            </button>
                            
                            <div class="banner-pagination">
                                <span id="currentBanner">1</span> / <span id="totalBanners"><?php echo $totalBanners; ?></span>
                            </div>
                            
                            <button id="nextBanner" class="btn btn-secondary" <?php echo $totalBanners <= 1 ? 'disabled' : ''; ?>>
                                Próximo
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Panel -->
        <div class="space-y-6">
            <!-- Banner Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informações</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted">Jogos Hoje:</span>
                            <span class="font-medium"><?php echo $totalJogos; ?> jogos</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Banners Necessários:</span>
                            <span class="font-medium"><?php echo $totalBanners; ?> banner(s)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Jogos por Banner:</span>
                            <span class="font-medium">5 jogos</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Formato:</span>
                            <span class="font-medium">PNG</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download Options -->
            <div id="downloadOptions" class="card" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">Opções de Download</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3">
                        <button id="downloadCurrentBtn" class="btn btn-primary w-full">
                            <i class="fas fa-download"></i>
                            Baixar Banner Atual
                        </button>
                        
                        <button id="downloadAllBtn" class="btn btn-success w-full">
                            <i class="fas fa-file-archive"></i>
                            Baixar Todos os Banners (ZIP)
                        </button>
                        
                        <button id="sendTelegramBtn" class="btn btn-info w-full">
                            <i class="fab fa-telegram"></i>
                            Enviar para Telegram
                        </button>
                    </div>
                </div>
            </div>

            <!-- Customization Tips -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Dicas de Personalização</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-image text-primary-500 mt-0.5"></i>
                            <div>
                                <p class="font-medium">Personalize o Logo</p>
                                <p class="text-muted">Adicione seu logo em <a href="logo.php" class="text-primary-500 hover:underline">Gerenciar Logos</a></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-photo-video text-success-500 mt-0.5"></i>
                            <div>
                                <p class="font-medium">Altere o Fundo</p>
                                <p class="text-muted">Mude o plano de fundo em <a href="background.php" class="text-primary-500 hover:underline">Gerenciar Fundos</a></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-th-large text-warning-500 mt-0.5"></i>
                            <div>
                                <p class="font-medium">Customize os Cards</p>
                                <p class="text-muted">Edite os cards em <a href="card.php" class="text-primary-500 hover:underline">Gerenciar Cards</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-12">
            <div class="mb-4">
                <i class="fas fa-calendar-times text-6xl text-gray-300"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">Nenhum Jogo Disponível</h3>
            <p class="text-muted mb-6">Não há jogos programados para hoje ou ocorreu um erro ao buscar os dados.</p>
            <div class="flex gap-4 justify-center">
                <button onclick="location.reload()" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i>
                    Atualizar Dados
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i>
                    Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .theme-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        overflow: hidden;
        transition: var(--transition);
    }
    
    .theme-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-300);
    }
    
    .theme-card.selected {
        border-color: var(--primary-500);
        box-shadow: 0 0 0 2px var(--primary-500);
    }
    
    .theme-preview {
        height: 180px;
        overflow: hidden;
        border-bottom: 1px solid var(--border-color);
    }
    
    .theme-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .theme-card:hover .theme-preview img {
        transform: scale(1.05);
    }
    
    .theme-info {
        padding: 1rem;
    }
    
    .theme-name {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .theme-description {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 1rem;
    }
    
    .theme-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .banner-preview-container {
        width: 100%;
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        padding: 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 400px;
        position: relative;
    }
    
    .banner-preview-image {
        max-width: 100%;
        max-height: 600px;
        height: auto;
        border-radius: var(--border-radius-sm);
        box-shadow: var(--shadow-lg);
    }
    
    .preview-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: var(--text-muted);
    }
    
    .preview-loading i {
        font-size: 2rem;
    }
    
    .banner-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .banner-pagination {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-secondary);
        padding: 0.5rem 1rem;
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-sm);
    }
    
    .space-y-3 > * + * {
        margin-top: 0.75rem;
    }
    
    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }
    
    .mt-4 {
        margin-top: 1rem;
    }
    
    .mt-6 {
        margin-top: 1.5rem;
    }
    
    .mb-2 {
        margin-bottom: 0.5rem;
    }
    
    .mb-4 {
        margin-bottom: 1rem;
    }
    
    .mb-6 {
        margin-bottom: 1.5rem;
    }
    
    .mr-3 {
        margin-right: 0.75rem;
    }
    
    .mt-0\.5 {
        margin-top: 0.125rem;
    }
    
    .w-full {
        width: 100%;
    }
    
    .text-6xl {
        font-size: 3.75rem;
        line-height: 1;
    }
    
    .text-xl {
        font-size: 1.25rem;
        line-height: 1.75rem;
    }
    
    .py-12 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    
    .gap-3 {
        gap: 0.75rem;
    }
    
    .gap-4 {
        gap: 1rem;
    }
    
    .gap-6 {
        gap: 1.5rem;
    }
    
    .justify-center {
        justify-content: center;
    }
    
    .justify-between {
        justify-content: space-between;
    }
    
    .items-center {
        align-items: center;
    }
    
    .items-start {
        align-items: flex-start;
    }
    
    .flex {
        display: flex;
    }
    
    .text-primary-500 {
        color: var(--primary-500);
    }
    
    .text-success-500 {
        color: var(--success-500);
    }
    
    .text-warning-500 {
        color: var(--warning-500);
    }
    
    .text-gray-300 {
        color: var(--text-muted);
    }
    
    .btn-info {
        background: var(--primary-500);
        color: white;
    }
    
    .btn-info:hover {
        background: var(--primary-600);
    }
    
    /* Dark theme adjustments */
    [data-theme="dark"] .text-gray-300 {
        color: var(--text-muted);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeCards = document.querySelectorAll('.theme-card');
    const bannerPreview = document.getElementById('bannerPreview');
    const previewImage = document.getElementById('previewImage');
    const previewLoading = document.getElementById('previewLoading');
    const downloadOptions = document.getElementById('downloadOptions');
    const prevBannerBtn = document.getElementById('prevBanner');
    const nextBannerBtn = document.getElementById('nextBanner');
    const currentBannerSpan = document.getElementById('currentBanner');
    const totalBannersSpan = document.getElementById('totalBanners');
    const downloadCurrentBtn = document.getElementById('downloadCurrentBtn');
    const downloadAllBtn = document.getElementById('downloadAllBtn');
    const sendTelegramBtn = document.getElementById('sendTelegramBtn');
    
    let selectedTheme = null;
    let currentBannerIndex = 0;
    const totalBanners = <?php echo $totalBanners; ?>;
    
    // Theme selection
    themeCards.forEach(card => {
        card.addEventListener('click', function() {
            const themeId = this.getAttribute('data-theme');
            
            // Remove selection from all cards
            themeCards.forEach(c => c.classList.remove('selected'));
            
            // Add selection to clicked card
            this.classList.add('selected');
            
            // Update selected theme
            selectedTheme = themeId;
            
            // Reset to first banner
            currentBannerIndex = 0;
            currentBannerSpan.textContent = '1';
            
            // Update navigation buttons
            updateNavigationButtons();
            
            // Show preview and download options
            bannerPreview.style.display = 'block';
            downloadOptions.style.display = 'block';
            
            // Load preview
            loadPreview();
        });
    });
    
    // Select theme buttons
    document.querySelectorAll('.select-theme-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent the card click event
            
            const themeId = this.getAttribute('data-theme');
            const themeCard = document.querySelector(`.theme-card[data-theme="${themeId}"]`);
            
            if (themeCard) {
                themeCard.click();
            }
        });
    });
    
    // Navigation buttons
    prevBannerBtn.addEventListener('click', function() {
        if (currentBannerIndex > 0) {
            currentBannerIndex--;
            currentBannerSpan.textContent = (currentBannerIndex + 1).toString();
            updateNavigationButtons();
            loadPreview();
        }
    });
    
    nextBannerBtn.addEventListener('click', function() {
        if (currentBannerIndex < totalBanners - 1) {
            currentBannerIndex++;
            currentBannerSpan.textContent = (currentBannerIndex + 1).toString();
            updateNavigationButtons();
            loadPreview();
        }
    });
    
    // Download buttons
    downloadCurrentBtn.addEventListener('click', function() {
        if (selectedTheme) {
            const url = getDownloadUrl(selectedTheme, currentBannerIndex, false);
            window.location.href = url;
        }
    });
    
    downloadAllBtn.addEventListener('click', function() {
        if (selectedTheme) {
            const url = getDownloadUrl(selectedTheme, 0, true);
            window.location.href = url;
        }
    });
    
    // Send to Telegram
    sendTelegramBtn.addEventListener('click', function() {
        if (selectedTheme) {
            sendToTelegram();
        }
    });
    
    // Helper functions
    function updateNavigationButtons() {
        prevBannerBtn.disabled = currentBannerIndex === 0;
        nextBannerBtn.disabled = currentBannerIndex === totalBanners - 1 || totalBanners <= 1;
    }
    
    function loadPreview() {
        if (!selectedTheme) return;
        
        // Show loading
        previewImage.style.display = 'none';
        previewLoading.style.display = 'flex';
        
        // Get preview URL
        const previewUrl = getPreviewUrl(selectedTheme, currentBannerIndex);
        
        // Load image
        const img = new Image();
        img.onload = function() {
            previewImage.src = previewUrl;
            previewImage.style.display = 'block';
            previewLoading.style.display = 'none';
        };
        img.onerror = function() {
            previewLoading.innerHTML = '<i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar prévia</p>';
        };
        img.src = previewUrl;
    }
    
    function getPreviewUrl(theme, index) {
        let baseUrl;
        
        switch (theme) {
            case '1':
                baseUrl = 'gerar_fut.php';
                break;
            case '2':
                baseUrl = 'gerar_fut_2.php';
                break;
            case '3':
                baseUrl = 'gerar_fut_3.php';
                break;
            case '4':
                baseUrl = 'gerar_fut_4.php';
                break;
            default:
                baseUrl = 'gerar_fut.php';
        }
        
        return `${baseUrl}?grupo=${index}&v=${Date.now()}`;
    }
    
    function getDownloadUrl(theme, index, downloadAll) {
        let baseUrl;
        
        switch (theme) {
            case '1':
                baseUrl = 'gerar_fut.php';
                break;
            case '2':
                baseUrl = 'gerar_fut_2.php';
                break;
            case '3':
                baseUrl = 'gerar_fut_3.php';
                break;
            case '4':
                baseUrl = 'gerar_fut_4.php';
                break;
            default:
                baseUrl = 'gerar_fut.php';
        }
        
        if (downloadAll) {
            return `${baseUrl}?download_all=1`;
        } else {
            return `${baseUrl}?grupo=${index}&download=1`;
        }
    }
    
    function sendToTelegram() {
        Swal.fire({
            title: 'Enviar para Telegram',
            text: 'Deseja enviar os banners para o Telegram?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, enviar',
            cancelButtonText: 'Cancelar',
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Enviando...',
                    text: 'Enviando banners para o Telegram',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send AJAX request
                fetch('send_telegram_banners.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `banner_type=football_${selectedTheme}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: data.message,
                            icon: 'success',
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: data.message,
                            icon: 'error',
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao enviar para o Telegram',
                        icon: 'error',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                    });
                });
            }
        });
    }
});
</script>

<?php include "includes/footer.php"; ?>