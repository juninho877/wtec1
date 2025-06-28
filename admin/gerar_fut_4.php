<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}


header('Content-Type: image/png');

// Funções de criptografia
function getChaveRemota() {
    $url_base64 = 'aHR0cHM6Ly9hcGlmdXQucHJvamVjdHguY2xpY2svQXV0b0FwaS9BRVMvY29uZmlna2V5LnBocA==';
    $auth_base64 = 'dmFxdW9UQlpFb0U4QmhHMg==';

    $url = base64_decode($url_base64);
    $auth = base64_decode($auth_base64);

    $postData = json_encode(['auth' => $auth]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    if ($response === false) {
        error_log("Erro no cURL ao obter chave remota: " . curl_error($ch));
    }
    curl_close($ch);

    return $response ? json_decode($response, true)['chave'] ?? null : null;
}

function descriptografarURL($urlCodificada, $chave) {
    list($url_criptografada, $iv) = explode('::', base64_decode($urlCodificada), 2);
    $decrypted = openssl_decrypt($url_criptografada, 'aes-256-cbc', $chave, 0, $iv);
    if ($decrypted === false) {
        error_log("Erro ao descriptografar URL: " . openssl_error_string());
    }
    return $decrypted;
}

// Obter dados dos jogos
$chave_secreta = getChaveRemota();
$parametro_criptografado = 'SVI0Sjh1MTJuRkw1bmFyeFdPb3cwOXA2TFo3RWlSQUxLbkczaGE4MXBiMWhENEpOWkhkSFZoeURaWFVDM1lTZzo6RNBu5BBhzmFRkTPPSikeJg==';
$json_url = $chave_secreta ? descriptografarURL($parametro_criptografado, $chave_secreta) : null;

$jogos = [];
if ($json_url) {
    $ch = curl_init($json_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $json_content = curl_exec($ch);
    if ($json_content === false) {
        error_log("Erro no cURL ao obter JSON: " . curl_error($ch));
    }
    curl_close($ch);

    if ($json_content !== false) {
        $todos_jogos = json_decode($json_content, true);
        if ($todos_jogos === null) {
            error_log("Erro ao decodificar JSON: " . json_last_error_msg());
        } else {
            // Incluir todos os jogos (removida filtragem rígida por data_jogo)
            $jogos = $todos_jogos;
        }
    }
}

if (empty($jogos)) {
    $im = imagecreatetruecolor(600, 100);
    $bg = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $bg);
    $color = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 10, 40, "Nenhum jogo disponível.", $color);
    imagepng($im);
    imagedestroy($im);
    exit;
}

// Dividir jogos em grupos de 5
$jogosPorBanner = 5;
$gruposDeJogos = array_chunk(array_keys($jogos), $jogosPorBanner);

// Função para carregar escudos
function carregarEscudo($url, $maxSize = 60) {
    if (empty($url)) {
        $img = imagecreatetruecolor($maxSize, $maxSize);
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $bg);
        $textColor = imagecolorallocate($img, 100, 100, 100);
        imagestring($img, 3, 5, $maxSize/2 - 7, "No\nLogo", $textColor);
        return $img;
    }
    $img = @imagecreatefromstring(@file_get_contents($url));
    if (!$img) {
        error_log("Erro ao carregar escudo: $url");
        $img = imagecreatetruecolor($maxSize, $maxSize);
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $bg);
        $textColor = imagecolorallocate($img, 100, 100, 100);
        imagestring($img, 3, 5, $maxSize/2 - 7, "No\nLogo", $textColor);
        return $img;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min($maxSize/$w, $maxSize/$h, 1);
    $newW = intval($w * $scale);
    $newH = intval($h * $scale);
    $imgResized = imagecreatetruecolor($newW, $newH);
    imagealphablending($imgResized, false);
    imagesavealpha($imgResized, true);
    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($img);
    return $imgResized;
}

// Função para desenhar texto
function desenharTexto($im, $texto, $x, $y, $cor, $tamanho=12, $angulo=0, $fonteCustom = null) {
    $fontPath = __DIR__ . '/fonts/CalSans-Regular.ttf';
    $fonteUsada = $fonteCustom ?? $fontPath;
    if (file_exists($fonteUsada)) {
        $bbox = imagettfbbox($tamanho, $angulo, $fonteUsada, $texto);
        $alturaTexto = abs($bbox[7] - $bbox[1]);
        imagettftext($im, $tamanho, $angulo, $x, $y + $alturaTexto, $cor, $fonteUsada, $texto);
    } else {
        error_log("Fonte não encontrada: $fonteUsada");
        imagestring($im, 5, $x, $y, $texto, $cor);
    }
}

// Função para obter imagem do JSON
function getImageFromJson($jsonPath) {
    $jsonContent = @file_get_contents($jsonPath);
    if ($jsonContent === false) {
        error_log("Erro ao carregar JSON de imagem: $jsonPath");
        return null;
    }
    
    $data = json_decode($jsonContent, true);
    if (empty($data) || !isset($data[0]['ImageName'])) {
        error_log("JSON de imagem inválido ou sem ImageName: $jsonPath");
        return null;
    }
    
    $imagePath = str_replace('../', '', $data[0]['ImageName']);
    $content = @file_get_contents($imagePath);
    if ($content === false) {
        error_log("Erro ao carregar imagem: $imagePath");
    }
    return $content;
}
function tratarCanaisTransmissao($lista) {
    if (!is_array($lista)) return '';

    $canais = [];
    foreach ($lista as $canal) {
        $nome = strtoupper($canal['nome'] ?? '');
        if (strpos($nome, 'YOUTUBE(') === 0) {
            $canais[] = 'YOUTUBE';
        } else {
            $canais[] = $canal['nome'];
        }
    }

    // Remover duplicados
    $canais = array_unique($canais);

    // Retornar apenas os 3 primeiros
    return implode(', ', array_slice($canais, 0, 3));
}

function gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga) {
    $fundoJogoPath = __DIR__ . '/imgelementos/fundo_jogo.png';
    $fundoJogo = file_exists($fundoJogoPath) ? imagecreatefrompng($fundoJogoPath) : null;
    
    // CONFIGURAÇÕES AJUSTÁVEIS
    $config = [
        'espacamento_vertical' => 10,
        'altura_jogo' => 150,
        'espaco_cabecalho' => 200,
        'posicoes' => [
            'liga' => ['x' => 130, 'y' => 17],
            'escudo1' => ['x' => 130, 'y' => 55],
            'escudo2' => ['x' => 625, 'y' => 55],
            'nome_time1' => ['x' => 188, 'y' => 40],
            'nome_time2' => ['x' => 608, 'y' => 40],
            'vs' => ['x' => 370, 'y' => 45],
            'data' => ['x' => 530, 'y' => 3448],
            'horario' => ['x' => 365, 'y' => 15],
            'canais' => ['x' => 290, 'y' => 110],
            'logo' => ['x' => 20, 'y' => 1, 'largura' => 180],
            'titulo1' => ['x' => 500, 'y' => 70],
            'titulo2' => ['x' => 500, 'y' => 110],
            'data_cabecalho' => ['x' => 300, 'y' => 180]
        ]
    ];

    // FONTE ESPECIAL PARA OS TIMES
    $fonteTimes = __DIR__ . '/fonts/AvilockBold.ttf';
    $tamanhoFonteTimes = 18;

    $heightPorJogo = $config['altura_jogo'];
    $espacamentoVertical = $config['espacamento_vertical'];
    $espacoCabecalho = $config['espaco_cabecalho'];
    $posicoes = $config['posicoes'];

    // CABEÇALHO
    $fonteTitulo = __DIR__ . '/fonts/AvilockBold.ttf';
    $fonteData = __DIR__ . '/fonts/AvilockBold.ttf';
    $corBranco = imagecolorallocate($im, 255, 255, 255);

    // Logo
    $logoContent = getImageFromJson('api/fzstore/logo_banner_4.json');
    if ($logoContent !== false) {
        $logo = @imagecreatefromstring($logoContent);
        if ($logo !== false) {
            $logoLarguraDesejada = $posicoes['logo']['largura'];
            $logoPosX = $posicoes['logo']['x'];
            $logoPosY = $posicoes['logo']['y'];
            
            $logoWidthOriginal = imagesx($logo);
            $logoHeightOriginal = imagesy($logo);
            $logoHeight = (int)($logoHeightOriginal * ($logoLarguraDesejada / $logoWidthOriginal));
            
            $logoRedimensionada = imagecreatetruecolor($logoLarguraDesejada, $logoHeight);
            imagealphablending($logoRedimensionada, false);
            imagesavealpha($logoRedimensionada, true);
            imagecopyresampled($logoRedimensionada, $logo, 0, 0, 0, 0, 
                             $logoLarguraDesejada, $logoHeight, 
                             $logoWidthOriginal, $logoHeightOriginal);
            
            imagecopy($im, $logoRedimensionada, $logoPosX, $logoPosY, 
                     0, 0, $logoLarguraDesejada, $logoHeight);
            
            imagedestroy($logo);
            imagedestroy($logoRedimensionada);
        }
    }

    // Título
    imagettftext($im, 51, 0, $posicoes['titulo1']['x'], $posicoes['titulo1']['y'], 
                $corBranco, $fonteTitulo, "AGENDA ");
    imagettftext($im, 36, 0, $posicoes['titulo2']['x'], $posicoes['titulo2']['y'], 
                $corBranco, $fonteTitulo, "ESPORTIVA");
    
    // Data
    setlocale(LC_TIME, 'pt_BR.utf8', 'portuguese');
    $dataHoje = date('Y-m-d');
    $timestamp = strtotime($dataHoje);
    $diaSemana = strftime('%A', $timestamp);
    $linhaData = strtoupper($diaSemana) . ' - ' . strtoupper(strftime('%d/%B', $timestamp));
    imagettftext($im, 47, 0, $posicoes['data_cabecalho']['x'], $posicoes['data_cabecalho']['y'], 
                $corBranco, $fonteData, $linhaData);

    // JOGOS
    $yAtual = $espacoCabecalho;

    
    foreach ($grupoJogos as $idx) {
        if (!isset($jogos[$idx])) continue;

        if ($fundoJogo) {
            $alturaCard = $heightPorJogo - 10;
            $larguraCard = $width - $padding * 2;
            $cardResized = imagecreatetruecolor($larguraCard, $alturaCard);
            imagealphablending($cardResized, false);
            imagesavealpha($cardResized, true);
            imagecopyresampled($cardResized, $fundoJogo, 0, 0, 0, 0, 
                              $larguraCard, $alturaCard, 
                              imagesx($fundoJogo), imagesy($fundoJogo));
            imagecopy($im, $cardResized, $padding, $yAtual, 
                     0, 0, $larguraCard, $alturaCard);
            imagedestroy($cardResized);
        }

        $jogo = $jogos[$idx];
        $time1 = $jogo['time1'] ?? 'Time 1';
        $time2 = $jogo['time2'] ?? 'Time 2';

        // Remover termos como sub-20, sub17, u17
        $time1 = preg_replace('/\b(sub[\s-]?20|sub[\s-]?17|u17)\b/i', '', $time1);
        $time2 = preg_replace('/\b(sub[\s-]?20|sub[\s-]?17|u17)\b/i', '', $time2);
        $time1 = trim(preg_replace('/\s+/', ' ', $time1));
        $time2 = trim(preg_replace('/\s+/', ' ', $time2));

        $liga = $jogo['competicao'] ?? 'Liga';
        $hora = $jogo['horario'] ?? '';
$canais = tratarCanaisTransmissao($jogo['canais'] ?? []);
        $escudo1 = $jogo['img_time1_url'] ?? '';
        $escudo2 = $jogo['img_time2_url'] ?? '';

        $tamEscudo = 45;
        $tamVS = 50;

        // Carregar imagens
        $imgEscudo1 = carregarEscudo($escudo1, $tamEscudo);
        $imgEscudo2 = carregarEscudo($escudo2, $tamEscudo);
        $vsImg = carregarImagem(__DIR__ . '/imgelementos/vs.png', $tamVS, $tamVS);

        $yTop = $yAtual + ($espacamentoVertical / 2);

        // Elementos do jogo
        desenharTexto($im, $liga, $posicoes['liga']['x'], $yTop + $posicoes['liga']['y'], 
                     $branco, 17, 0, $fontLiga);

        // Escudos
        imagecopy($im, $imgEscudo1, $posicoes['escudo1']['x'], $yTop + $posicoes['escudo1']['y'], 
                 0, 0, $tamEscudo, $tamEscudo);
        imagecopy($im, $vsImg, $posicoes['vs']['x'], $yTop + $posicoes['vs']['y'], 
                 0, 0, $tamVS, $tamVS);
        imagecopy($im, $imgEscudo2, $posicoes['escudo2']['x'], $yTop + $posicoes['escudo2']['y'], 
                 0, 0, $tamEscudo, $tamEscudo);

        // Nomes dos times centralizados
        $nome_time1_y = $yTop + $posicoes['nome_time1']['y'] + ($tamEscudo / 2) + 8;
        $nome_time2_y = $yTop + $posicoes['nome_time2']['y'] + ($tamEscudo / 2) + 8;
        desenharTexto($im, $time1, $posicoes['nome_time1']['x'], $nome_time1_y, 
                     $branco, $tamanhoFonteTimes, 0, $fonteTimes);
        $bbox2 = imagettfbbox($tamanhoFonteTimes, 0, $fonteTimes, $time2);
$larguraTexto2 = $bbox2[2] - $bbox2[0];
$posX_nome_time2 = $posicoes['nome_time2']['x'] - $larguraTexto2;

desenharTexto($im, $time2, $posX_nome_time2, $nome_time2_y, 
              $branco, $tamanhoFonteTimes, 0, $fonteTimes);


        // Outros elementos
        desenharTexto($im, date('d/m'), $posicoes['data']['x'], $yTop + $posicoes['data']['y'], 
                     $branco, 12, 0, $fontLiga);
        desenharTexto($im, $hora, $posicoes['horario']['x'], $yTop + $posicoes['horario']['y'], 
                     $branco, 22, 0, $fontLiga);
        desenharTexto($im, $canais, $posicoes['canais']['x'], $yTop + $posicoes['canais']['y'], 
                     $branco, 16, 0, $fontLiga);

        imagedestroy($imgEscudo1);
        imagedestroy($imgEscudo2);
        imagedestroy($vsImg);

        $yAtual += $heightPorJogo + $espacamentoVertical;
    }


    if ($fundoJogo) imagedestroy($fundoJogo);
}

function carregarImagem($path, $width, $height) {
    $original = imagecreatefrompng($path);
    $resized = imagecreatetruecolor($width, $height);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $original, 0, 0, 0, 0, 
                      $width, $height, imagesx($original), imagesy($original));
    imagedestroy($original);
    return $resized;
}

// Configurações do banner
$width = 800;
$heightPorJogo = 140;
$padding = 15;
$espacoExtra = 400;
$fontLiga = __DIR__ . '/fonts/BebasNeue-Regular.ttf';

// Download de todos os banners em ZIP
if (isset($_GET['download_all']) && $_GET['download_all'] == 1) {
    $zip = new ZipArchive();
    $zipNome = "banners_jogos_" . date('Y-m-d') . ".zip";
    $tempFiles = [];

    if ($zip->open($zipNome, ZipArchive::CREATE) === TRUE) {
        foreach ($gruposDeJogos as $index => $grupoJogos) {
            $height = $jogosPorBanner * $heightPorJogo + $padding * 2 + $espacoExtra;
            $im = imagecreatetruecolor($width, $height);
            $preto = imagecolorallocate($im, 0, 0, 0);
            $branco = imagecolorallocate($im, 255, 255, 255);

            // Fundo obtido do JSON
            $fundoContent = getImageFromJson('api/fzstore/background_banner_4.json');
            if ($fundoContent !== false) {
                $fundo = @imagecreatefromstring($fundoContent);
                if ($fundo !== false) {
                    imagecopyresampled($im, $fundo, 0, 0, 0, 0, $width, $height, imagesx($fundo), imagesy($fundo));
                    imagedestroy($fundo);
                } else {
                    imagefill($im, 0, 0, $branco);
                }
            } else {
                imagefill($im, 0, 0, $branco);
            }

            gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);

            $nomeArquivo = __DIR__ . "/banner_jogos_" . date('Y-m-d') . "_parte" . ($index + 1) . ".png";
            imagepng($im, $nomeArquivo);
            $zip->addFile($nomeArquivo, basename($nomeArquivo));
            $tempFiles[] = $nomeArquivo;
            imagedestroy($im);
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipNome . '"');
        readfile($zipNome);

        foreach ($tempFiles as $file) {
            if (file_exists($file)) unlink($file);
        }
        unlink($zipNome);
        exit;
    } else {
        error_log("Erro ao criar arquivo ZIP: $zipNome");
    }
}

// Geração de banner individual
$grupoIndex = isset($_GET['grupo']) ? (int)$_GET['grupo'] : 0;
if (!isset($gruposDeJogos[$grupoIndex])) {
    $im = imagecreatetruecolor(600, 100);
    $bg = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $bg);
    $color = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 10, 40, "Banner inválido.", $color);
    imagepng($im);
    imagedestroy($im);
    exit;
}

$grupoJogos = $gruposDeJogos[$grupoIndex];
$height = $jogosPorBanner * $heightPorJogo + $padding * 2 + $espacoExtra;
$im = imagecreatetruecolor($width, $height);
$preto = imagecolorallocate($im, 0, 0, 0);
$branco = imagecolorallocate($im, 255, 255, 255);

// Fundo obtido do JSON
$fundoContent = getImageFromJson('api/fzstore/background_banner_4.json');
if ($fundoContent !== false) {
    $fundo = @imagecreatefromstring($fundoContent);
    if ($fundo !== false) {
        imagecopyresampled($im, $fundo, 0, 0, 0, 0, $width, $height, imagesx($fundo), imagesy($fundo));
        imagedestroy($fundo);
    } else {
        imagefill($im, 0, 0, $branco);
    }
} else {
    imagefill($im, 0, 0, $branco);
}

gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);

if (isset($_GET['download']) && $_GET['download'] == 1) {
    $nomeArquivo = "banner_jogos_" . date('Y-m-d') . "_parte" . ($grupoIndex + 1) . ".png";
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
}

imagepng($im);
imagedestroy($im);
exit;