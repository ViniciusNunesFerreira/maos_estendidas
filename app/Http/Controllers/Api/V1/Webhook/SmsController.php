<?php


namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetSms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    /**
     * Receber status de envio de SMS do Twilio
     * 
     * POST /api/v1/webhooks/sms/status
     */
    public function status(Request $request): JsonResponse
    {
        try {
            Log::info('SMS webhook received', ['payload' => $request->all()]);

            // Verificar assinatura Twilio
            if (!$this->verifyTwilioSignature($request)) {
                Log::warning('Invalid Twilio signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $messageSid = $request->input('MessageSid');
            $status = $request->input('SmsStatus');

            // Atualizar status do SMS
            $this->updateSmsStatus($messageSid, $status);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing SMS webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal error',
            ], 500);
        }
    }

    /**
     * Verificar assinatura Twilio
     */
    private function verifyTwilioSignature(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Signature');
        $url = $request->fullUrl();
        $params = $request->all();
        $authToken = config('services.twilio.auth_token');

        $data = '';
        ksort($params);
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $expectedSignature = base64_encode(hash_hmac('sha1', $url . $data, $authToken, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Atualizar status do SMS
     */
    private function updateSmsStatus(string $messageSid, string $status): void
    {
        PasswordResetSms::where('twilio_sid', $messageSid)
            ->update([
                'status' => $status,
                'delivered_at' => in_array($status, ['delivered', 'sent']) ? now() : null,
            ]);
    }
}