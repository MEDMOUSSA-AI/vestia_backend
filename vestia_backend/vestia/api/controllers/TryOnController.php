namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
 
class TryOnController extends Controller
{
    // مفتاح Replicate — ضعه في .env
    // REPLICATE_API_TOKEN=r8_xxxxxxxxxxxxxxxxxxxx
    // الحصول على المفتاح مجاناً من: https://replicate.com
 
    public function generate(Request $request)
    {
        $request->validate([
            'person_image'     => 'required|image|max:5120',  // max 5MB
            'garment_image_url' => 'required|url',
        ]);
 
        // ── 1. Upload person image to temp storage ────────────────
        $personPath = $request->file('person_image')
            ->store('tryon/temp', 'public');
 
        $personUrl = Storage::disk('public')->url($personPath);
 
        // ── 2. Call Replicate API (IDM-VTON model) ────────────────
        $response = Http::withToken(config('services.replicate.token'))
            ->timeout(120)
            ->post('https://api.replicate.com/v1/predictions', [
                'version' => 'c871bb9b046607b680449ecbae55fd8c6d945e0a1948644bf2361b3d021d3ff4',
                // IDM-VTON — أدق نموذج Virtual Try-On مفتوح المصدر
                'input' => [
                    'human_img'   => $personUrl,
                    'garm_img'    => $request->garment_image_url,
                    'garment_des' => 'clothing item',
                    'is_checked'  => true,
                    'is_checked_crop' => false,
                    'denoise_steps' => 30,
                    'seed'        => 42,
                ],
            ]);
 
        if (! $response->successful()) {
            return response()->json(['error' => 'AI service unavailable'], 503);
        }
 
        $prediction = $response->json();
        $predictionId = $prediction['id'];
 
        // ── 3. Poll until result is ready (max 90 sec) ───────────
        $resultUrl = null;
        $attempts  = 0;
 
        while ($attempts < 30) {
            sleep(3);
            $poll = Http::withToken(config('services.replicate.token'))
                ->get("https://api.replicate.com/v1/predictions/{$predictionId}");
 
            $data   = $poll->json();
            $status = $data['status'] ?? '';
 
            if ($status === 'succeeded') {
                // IDM-VTON returns array of output URLs
                $output    = $data['output'] ?? [];
                $resultUrl = is_array($output) ? ($output[1] ?? $output[0] ?? null) : $output;
                break;
            }
 
            if ($status === 'failed' || $status === 'canceled') {
                return response()->json(['error' => 'Generation failed: ' . ($data['error'] ?? 'unknown')], 500);
            }
 
            $attempts++;
        }
 
        // ── 4. Clean up temp file ─────────────────────────────────
        Storage::disk('public')->delete($personPath);
 
        if (! $resultUrl) {
            return response()->json(['error' => 'Timeout — please try again'], 504);
        }
 
        // ── 5. Return result ──────────────────────────────────────
        return response()->json([
            'result_url' => $resultUrl,
            'status'     => 'success',
        ]);
    }
}
 
