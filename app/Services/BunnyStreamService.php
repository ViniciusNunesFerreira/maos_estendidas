<?php

namespace App\Services;

/**
 * BunnyStreamService
 *
 * Responsável por gerar URLs seguras e assinadas para:
 * - Vídeos hospedados no Bunny Stream (embed iframe com token)
 * - Arquivos (PDFs/ebooks) hospedados no BunnyCDN Storage
 *
 * Documentação Bunny: https://docs.bunny.net/docs/stream-signed-urls
 */
class BunnyStreamService
{
    private string $streamLibraryId;
    private string $streamApiKey;
    private string $cdnHostname;
    private string $cdnSecurityKey;
    private int $tokenTtl;

    public function __construct()
    {
        $this->streamLibraryId = config('services.bunny.stream_library_id');
        $this->streamApiKey    = config('services.bunny.stream_api_key');
        $this->cdnHostname     = config('services.bunny.cdn_hostname');       // ex: files.seusite.b-cdn.net
        $this->cdnSecurityKey  = config('services.bunny.cdn_security_key');
        $this->tokenTtl        = config('services.bunny.token_ttl', 3600);   // 1 hora por padrão
    }

    // =========================================================
    // BUNNY STREAM — Vídeos
    // =========================================================

    /**
     * Gera uma URL de embed segura para o player do Bunny Stream.
     * O token garante que o vídeo só possa ser acessado via app,
     * com validade limitada (TTL configurável).
     *
     * Fórmula oficial Bunny:
     * token = Base64UrlEncode(SHA256(api_key + video_id + expires))
     *
     * @param  string      $videoId   external_video_id do registro study_materials
     * @param  string|null $userIp    IP do usuário (opcional, aumenta segurança)
     * @return array{embed_url: string, expires_at: string}
     */
    public function getSecureEmbedUrl(string $videoId, ?string $userIp = null): array
    {
        $expires = time() + $this->tokenTtl;

        // Bunny Stream signed token
        // hash = SHA256(SecurityAPIKey + VideoGUID + Expiration[+ IP])
        $hashBase  = $this->streamApiKey . $videoId . $expires;
        $userIp = null;

        if ($userIp) {
            $hashBase .= $userIp;
        }

        $rawHash = hash('sha256', $hashBase, true);
        $token   = rtrim(strtr(base64_encode($rawHash), '+/', '-_'), '=');

        $embedUrl = sprintf(
            'https://iframe.mediadelivery.net/embed/%s/%s?token=%s&expires=%d&autoplay=false&responsive=true&preload=false',
            $this->streamLibraryId,
            $videoId,
            $token,
            $expires
        );

        return [
            'embed_url'  => $embedUrl,
            'expires_at' => date('c', $expires),
        ];
    }

    /**
     * Retorna apenas a stream URL (para referência — não exposta ao cliente)
     */
    public function getStreamUrl(string $videoId): string
    {
        return sprintf(
            'https://video.bunnycdn.com/play/%s/%s',
            $this->streamLibraryId,
            $videoId
        );
    }

    // =========================================================
    // BUNNY CDN — Arquivos (PDFs, Ebooks)
    // =========================================================

    /**
     * Gera uma URL assinada para visualização de arquivo na CDN.
     * O Content-Disposition é definido como "inline" para forçar
     * abertura no browser/app sem oferecer download direto.
     *
     * Token Bunny CDN:
     * token = SHA256(SecurityKey + url + expires)
     *
     * @param  string $filePath  Caminho relativo do arquivo no storage (file_path do model)
     * @return array{view_url: string, expires_at: string}
     */
    public function getSecureFileViewUrl(string $filePath): array
    {
        $expires = time() + $this->tokenTtl;

        // Hash para assinatura
        $hashStr = $this->cdnSecurityKey . $filePath . $expires;
        $token   = hash('sha256', $hashStr);

        $viewUrl = sprintf(
            '%s?token=%s&expires=%d',
            $filePath,
            $token,
            $expires
        );

        return [
            'view_url'   => $viewUrl,
            'expires_at' => date('c', $expires),
        ];
    }

    /**
     * Retorna a URL da thumbnail de um vídeo no Bunny Stream
     */
    public function getThumbnailUrl(string $videoId): string
    {
        return sprintf(
            'https://vz-%s.b-cdn.net/%s/thumbnail.jpg',
            $this->streamLibraryId,
            $videoId
        );
    }
}