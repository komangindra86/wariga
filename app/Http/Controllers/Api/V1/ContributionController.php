<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\UserContribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ContributionController extends Controller
{
    use ApiResponses;

    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'type' => ['required', Rule::in(['app_feedback', 'result_report', 'wariga_knowledge'])],
            'is_anonymous' => ['required', 'boolean'],
            'contact_email' => ['nullable', 'email:rfc', 'max:255', 'required_if:is_anonymous,false'],
            'subject' => ['required', 'string', 'min:5', 'max:180'],
            'message' => ['required', 'string', 'min:15', 'max:5000'],
            'activity_name' => ['nullable', 'string', 'max:180', 'required_if:type,result_report'],
            'related_date' => ['nullable', 'date_format:Y-m-d', 'required_if:type,result_report'],
            'region' => ['nullable', 'string', 'max:120'],
            'tradition' => ['nullable', 'string', 'max:160'],
            'source_reference' => ['nullable', 'string', 'max:500', 'required_if:type,wariga_knowledge'],
            'consent' => ['accepted'],
        ]);
        if ($validator->fails()) {
            return $this->failure('Kontribusi belum dapat dikirim.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }

        $data = $validator->validated();
        $contribution = UserContribution::query()->create([
            ...collect($data)->except('consent')->all(),
            'user_id' => $data['is_anonymous'] ? null : auth('sanctum')->id(),
            'contact_email' => $data['is_anonymous'] ? null : $data['contact_email'],
            'status' => 'pending_review',
            'consented_at' => now(),
        ]);

        return $this->success([
            'id' => $contribution->id,
            'status' => $contribution->status,
            'status_label' => 'Menunggu tinjauan',
        ], 'Terima kasih. Kontribusi Anda masuk ke antrean tinjauan dan tidak langsung mengubah aturan.', 201);
    }
}
