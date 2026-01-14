<?php

namespace App\Services;

use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PixService
{
    private string $pixKey;
    private string $merchantName;
    private string $merchantCity;

    public function __construct()
    {
        $this->pixKey = config('casalar.pix.key');
        $this->merchantName = config('casalar.pix.merchant_name', 'CASA LAR');
        $this->merchantCity = config('casalar.pix.merchant_city', 'GUARUJA');
    }

    /**
     * Gerar QR Code PIX
     */
    public function generate(array $data): array
    {
        $transactionId = $this->generateTransactionId();
        $amount = $data['amount'];
        $description = $data['description'] ?? '';

        // Gerar payload PIX (Copia e Cola)
        $payload = $this->generatePayload($amount, $transactionId, $description);

        // Gerar QR Code
        $qrCodeBase64 = base64_encode(
            QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->generate($payload)
        );

        return [
            'transaction_id' => $transactionId,
            'qr_code' => $payload,
            'qr_code_base64' => $qrCodeBase64,
            'copy_paste' => $payload,
            'amount' => $amount,
        ];
    }

    /**
     * Verificar status do pagamento
     */
    public function checkStatus(string $transactionId): array
    {
        // Aqui seria a integração com o PSP (Banco/Fintech)
        // Por exemplo: Mercado Pago, PagSeguro, Gerencianet, etc.
        
        // Simulação de resposta
        return [
            'transaction_id' => $transactionId,
            'paid' => false,
            'paid_at' => null,
            'gateway_id' => null,
        ];
    }

    /**
     * Gerar payload PIX (padrão EMV)
     */
    private function generatePayload(float $amount, string $transactionId, string $description): string
    {
        $payload = '';

        // Payload Format Indicator
        $payload .= $this->formatField('00', '01');

        // Merchant Account Information - PIX
        $pixKey = $this->formatField('01', $this->pixKey);
        $pixDescription = $description ? $this->formatField('02', substr($description, 0, 25)) : '';
        $gui = $this->formatField('00', 'br.gov.bcb.pix');
        
        $merchantAccount = $gui . $pixKey . $pixDescription;
        $payload .= $this->formatField('26', $merchantAccount);

        // Merchant Category Code
        $payload .= $this->formatField('52', '0000');

        // Transaction Currency (986 = BRL)
        $payload .= $this->formatField('53', '986');

        // Transaction Amount
        $payload .= $this->formatField('54', number_format($amount, 2, '.', ''));

        // Country Code
        $payload .= $this->formatField('58', 'BR');

        // Merchant Name
        $payload .= $this->formatField('59', substr($this->merchantName, 0, 25));

        // Merchant City
        $payload .= $this->formatField('60', substr($this->merchantCity, 0, 15));

        // Additional Data Field Template (Transaction ID)
        $additionalData = $this->formatField('05', $transactionId);
        $payload .= $this->formatField('62', $additionalData);

        // CRC16
        $payload .= '6304';
        $crc = $this->calculateCRC16($payload);
        $payload .= strtoupper($crc);

        return $payload;
    }

    /**
     * Formatar campo EMV
     */
    private function formatField(string $id, string $value): string
    {
        $length = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $length . $value;
    }

    /**
     * Calcular CRC16 (CCITT-FALSE)
     */
    private function calculateCRC16(string $payload): string
    {
        $polynomial = 0x1021;
        $result = 0xFFFF;

        for ($i = 0; $i < strlen($payload); $i++) {
            $result ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($result & 0x8000) !== 0) {
                    $result = ($result << 1) ^ $polynomial;
                } else {
                    $result <<= 1;
                }
            }
        }

        return str_pad(dechex($result & 0xFFFF), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Gerar ID de transação único
     */
    private function generateTransactionId(): string
    {
        return 'PIX' . now()->format('ymdHis') . strtoupper(Str::random(5));
    }

    /**
     * Validar chave PIX
     */
    public function validatePixKey(string $key): bool
    {
        // CPF
        if (preg_match('/^\d{11}$/', $key)) {
            return true;
        }

        // CNPJ
        if (preg_match('/^\d{14}$/', $key)) {
            return true;
        }

        // Email
        if (filter_var($key, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // Telefone
        if (preg_match('/^\+55\d{10,11}$/', $key)) {
            return true;
        }

        // Chave aleatória (EVP)
        if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $key)) {
            return true;
        }

        return false;
    }
}