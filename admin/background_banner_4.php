<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Incluir classes necessárias
require_once 'classes/UserImage.php';
require_once 'classes/BannerCache.php';

$userImage = new UserImage();
$bannerCache = new BannerCache();
$userId = $_SESSION['user_id'];

$background_types = [
    'background_banner_4' => ['name' => 'Fundo Banner Tema 4', 'fixed_filename' => 'background_banner_4'],
];

$current_bg_key = 'background_banner_4'; // Sempre usar a chave específica para o tema 4
$current_bg_config = $background_types[$current_bg_key];
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $posted_bg_type = $_POST['bg_type'] ?? null;
    if ($posted_bg_type && isset($background_types[$posted_bg_type])) {
        $fixed_filename_base = $background_types[$posted_bg_type]['fixed_filename'];

        if (isset($_POST['upload']) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($file['type'], $allowedTypes)) {
                $uploadPath = './wtec/Img/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = $fixed_filename_base . '_user_' . $userId . '.' . $extension;
                $destination = $uploadPath . $fileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $imagePath = "wtec/Img/" . $fileName;
                    if ($userImage->saveUserImage($userId, $posted_bg_type, $imagePath, 'file')) {
                        // 🔥 INVALIDAR CACHE AUTOMATICAMENTE
                        $bannerCache->clearUserCache($userId);
                        
                        $successMessage = "Plano de fundo atualizado com sucesso! Cache de banners limpo automaticamente.";
                    } else {
                        $errorMessage = "Erro ao salvar as informações da imagem.";
                    }
                } else { 
                    $errorMessage = 'Falha ao mover o arquivo enviado.'; 
                }
            } else { 
                $errorMessage = 'Tipo de arquivo inválido.'; 
            }
        } elseif (isset($_POST['url-submit'])) {
            $imageUrl = filter_var($_POST['image-url'], FILTER_SANITIZE_URL);
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                if ($userImage->saveUserImage($userId, $posted_bg_type, $imageUrl, 'url')) {
                    // 🔥 INVALIDAR CACHE AUTOMATICAMENTE
                    $bannerCache->clearUserCache($userId);
                    
                    $successMessage = "Plano de fundo atualizado com sucesso! Cache de banners limpo automaticamente.";
                } else {
                    $errorMessage = "Erro ao salvar as informações da imagem.";
                }
            } else { 
                $errorMessage = 'A URL fornecida não é válida.'; 
            }
        }
    } else {
        $errorMessage = "Tipo de plano de fundo inválido enviado.";
    }
    
    // Redirecionar após POST para evitar reenvio
    if (!empty($successMessage) || !empty($errorMessage)) {
        $message = !empty($successMessage) ? $successMessage : $errorMessage;
        $type = !empty($successMessage) ? 'success' : 'error';
        
        // Usar sessão para passar a mensagem
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        
        // Redirecionar para a mesma página (GET)
        header("Location: background_banner_4.php");
        exit();
    }
}

// Verificar se há mensagem flash da sessão
if (isset($_SESSION['flash_message'])) {
    if ($_SESSION['flash_type'] === 'success') {
        $successMessage = $_SESSION['flash_message'];
    } else {
        $errorMessage = $_SESSION['flash_message'];
    }
    
    // Limpar mensagem da sessão
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Buscar configuração atual do background
$currentConfig = $userImage->getUserImageConfig($userId, $current_bg_key);
$methord = "Não Definido";
$imageFilex = '';
$showPreview = false;

if ($currentConfig) {
    $uploadType = $currentConfig['upload_type'];
    $imagePath = $currentConfig['image_path'];
    
    if ($uploadType == "file" && !empty($imagePath)) {
        $imageFilex = "/admin/" . $imagePath;
        $methord = "Arquivo Enviado";
        $showPreview = true;
    } elseif ($uploadType == "url" && filter_var($imagePath, FILTER_VALIDATE_URL)) {
        $imageFilex = $imagePath;
        $methord = "URL Externa";
        $showPreview = true;
    } elseif ($uploadType == "default") {
        $imageFilex = "/admin/" . $imagePath;
        $methord = "Fundo Padrão";
        $showPreview = true;
    }
}

$pageTitle = "Fundo Banner Tema 4";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-photo-video text-primary-500 mr-3"></i>
        Fundo para Banner Tema 4 (Agenda Esportiva)
    </h1>
    <p class="page-subtitle">Configure o fundo utilizado nos banners do Tema 4</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Preview Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Visualização</h3>
            <p class="card-subtitle">Prévia do fundo atual para o Tema 4</p>
        </div>
        <div class="card-body">
            <div class="preview-container">
                <label class="form-label">Prévia Atual:</label>
                <div class="preview-area">
                    <?php if ($showPreview): ?>
                        <img src="<?= $imageFilex ?>?v=<?= time() ?>" alt="Preview do Fundo" class="preview-image">
                    <?php else: ?>
                        <div class="preview-placeholder">
                            <i class="fas fa-image text-4xl text-gray-400 mb-2"></i>
                            <span class="text-gray-500">Nenhum fundo definido</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="current-method-info">
                    <span class="method-badge">Método Atual: <strong><?= $methord ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Alterar Fundo</h3>
            <p class="card-subtitle">Envie um novo arquivo ou use uma URL</p>
        </div>
        <div class="card-body">
            <div class="method-switcher">
                <input type="radio" id="upload-radio" name="upload-type" value="file" checked>
                <label for="upload-radio">
                    <i class="fas fa-upload"></i>
                    Enviar Arquivo
                </label>
                
                <input type="radio" id="url-radio" name="upload-type" value="url">
                <label for="url-radio">
                    <i class="fas fa-link"></i>
                    Usar URL
                </label>
            </div>

            <div class="forms-container">
                <!-- Upload Form -->
                <form method="post" enctype="multipart/form-data" id="upload-form" class="method-form">
                    <input type="hidden" name="bg_type" value="<?= $current_bg_key ?>">
                    <div class="form-group">
                        <label for="image" class="form-label">Selecione uma imagem:</label>
                        <input class="form-input" type="file" name="image" id="image" accept="image/*">
                        <p class="form-help">Formatos aceitos: PNG, JPG, GIF, WebP</p>
                    </div>
                    <button class="btn btn-primary w-full" type="submit" name="upload">
                        <i class="fas fa-upload"></i>
                        Enviar Arquivo
                    </button>
                </form>

                <!-- URL Form -->
                <form method="post" id="url-form" class="method-form" style="display: none;">
                    <input type="hidden" name="bg_type" value="<?= $current_bg_key ?>">
                    <div class="form-group">
                        <label for="image-url" class="form-label">URL da imagem:</label>
                        <input class="form-input" type="text" name="image-url" id="image-url" placeholder="https://exemplo.com/fundo.jpg">
                        <p class="form-help">Insira a URL completa da imagem</p>
                    </div>
                    <button class="btn btn-primary w-full" type="submit" name="url-submit">
                        <i class="fas fa-save"></i>
                        Salvar URL
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cache Info Alert -->
<?php if (!empty($successMessage) && strpos($successMessage, 'Cache') !== false): ?>
<div class="card mt-6 border-success-200">
    <div class="card-header">
        <h3 class="card-title text-success-600">
            <i class="fas fa-rocket text-success-500 mr-2"></i>
            Cache Atualizado Automaticamente
        </h3>
    </div>
    <div class="card-body">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-success-500 mt-1"></i>
            <div>
                <p class="font-medium text-success-700">Seus próximos banners usarão o novo fundo!</p>
                <p class="text-sm text-success-600 mt-1">
                    O cache foi limpo automaticamente. Os próximos banners do Tema 4 que você gerar 
                    já utilizarão o fundo atualizado.
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Info Section -->
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle text-primary-500 mr-2"></i>
            Informações Importantes
        </h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold mb-3">📋 Onde é usado:</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-success-500"></i>
                        Banners do Tema 4 (Agenda Esportiva)
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-success-500"></i>
                        Plano de fundo completo do banner
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-success-500"></i>
                        Tamanho recomendado: 800x1200px
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-3">💡 Dicas de uso:</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-lightbulb text-warning-500"></i>
                        Use imagens escuras para melhor contraste
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-lightbulb text-warning-500"></i>
                        Prefira fundos com gradientes ou texturas sutis
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-lightbulb text-warning-500"></i>
                        Evite imagens muito detalhadas que possam distrair
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .preview-container {
        margin-top: 1.5rem;
    }

    .preview-area {
        width: 100%;
        height: 250px;
        background: var(--bg-secondary);
        border: 2px dashed var(--border-color);
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 0.5rem;
        position: relative;
        overflow: hidden;
    }

    .preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--text-muted);
    }

    .current-method-info {
        text-align: center;
        margin-top: 1rem;
    }

    .method-badge {
        display: inline-block;
        background: var(--bg-tertiary);
        color: var(--text-secondary);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        font-size: 0.875rem;
    }

    .method-badge strong {
        color: var(--primary-500);
    }

    .method-switcher {
        display: flex;
        background: var(--bg-tertiary);
        border-radius: var(--border-radius);
        padding: 0.25rem;
        margin-bottom: 1.5rem;
    }

    .method-switcher input[type="radio"] {
        display: none;
    }

    .method-switcher label {
        flex: 1;
        text-align: center;
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-radius: var(--border-radius-sm);
        transition: var(--transition);
        font-weight: 500;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .method-switcher input[type="radio"]:checked + label {
        background: var(--primary-500);
        color: white;
        box-shadow: var(--shadow-sm);
    }

    .forms-container {
        margin-bottom: 1.5rem;
    }

    .method-form {
        animation: fadeIn 0.3s ease-out;
    }

    .form-help {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }

    .border-success-200 {
        border-color: rgba(34, 197, 94, 0.3);
    }

    .text-success-600 {
        color: var(--success-600);
    }

    .text-success-700 {
        color: var(--success-700);
    }

    .mt-6 {
        margin-top: 1.5rem;
    }

    .mt-1 {
        margin-top: 0.25rem;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .preview-placeholder {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-gray-400 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .text-gray-500 {
        color: var(--text-muted);
    }

    [data-theme="dark"] .border-success-200 {
        border-color: rgba(34, 197, 94, 0.2);
    }

    [data-theme="dark"] .text-success-600 {
        color: var(--success-400);
    }

    [data-theme="dark"] .text-success-700 {
        color: var(--success-300);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadRadio = document.getElementById('upload-radio');
    const urlRadio = document.getElementById('url-radio');
    const uploadForm = document.getElementById('upload-form');
    const urlForm = document.getElementById('url-form');
    
    function switchForms() {
        if (uploadRadio.checked) {
            uploadForm.style.display = 'block';
            urlForm.style.display = 'none';
        } else {
            uploadForm.style.display = 'none';
            urlForm.style.display = 'block';
        }
    }
    
    uploadRadio.addEventListener('change', switchForms);
    urlRadio.addEventListener('change', switchForms);
    switchForms();

    <?php if (!empty($successMessage)): ?>
    Swal.fire({
        title: 'Sucesso!',
        text: '<?= addslashes($successMessage) ?>',
        icon: 'success',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
        confirmButtonColor: '#3b82f6'
    });
    <?php elseif (!empty($errorMessage)): ?>
    Swal.fire({
        title: 'Erro!',
        text: '<?= addslashes($errorMessage) ?>',
        icon: 'error',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
        confirmButtonColor: '#ef4444'
    });
    <?php endif; ?>
});
</script>

<?php include "includes/footer.php"; ?>