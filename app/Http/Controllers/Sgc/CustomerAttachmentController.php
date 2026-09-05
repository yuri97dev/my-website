<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreCustomerAttachmentRequest;
use App\Http\Requests\Sgc\UpdateCustomerAttachmentRequest;
use App\Models\SgcCompanyCustomer;
use App\Models\SgcCustomer;
use App\Models\SgcCustomerAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CustomerAttachmentController extends Controller
{
    public function index(Request $request, SgcCustomer $sgc_customer)
    {
        $request->validate([
            "company_id" => ["required", "integer", "exists:sgc_companies,id"],
        ]);

        $pivot = $this->resolveAccessiblePivot(
            $request,
            $sgc_customer,
            $request->integer("company_id"),
        );

        return response()->json([
            "attachments" => $pivot->attachments()
                ->orderByDesc("id")
                ->get($this->attachmentColumns()),
        ], 200);
    }

    public function store(StoreCustomerAttachmentRequest $request, SgcCustomer $sgc_customer)
    {
        $pivot = $this->resolveAccessiblePivot(
            $request,
            $sgc_customer,
            $request->integer("company_id"),
        );

        $file = $request->file("file");
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== "" ? ".{$extension}" : "");
        $directory = "sgc/customers/{$sgc_customer->id}/attachments/".date("Y/m/d");
        $stored_path = $file->storeAs($directory, $filename);

        abort_unless(
            is_string($stored_path) && $stored_path !== "",
            Response::HTTP_INTERNAL_SERVER_ERROR,
            "Não foi possível salvar o arquivo.",
        );

        $attachment = $pivot->attachments()->create([
            "type" => SgcCustomerAttachment::TYPE_OTHER,
            "original_name" => $file->getClientOriginalName(),
            "path" => $stored_path,
            "mime_type" => $file->getMimeType(),
            "size_bytes" => $file->getSize(),
            "original_size_bytes" => $file->getSize(),
        ]);

        if( Str::startsWith((string) $attachment->mime_type, "image/") ) {
            $attachment->dispatchImageOptimization();
        }

        return response()->json([
            "attachment" => $attachment->only($this->attachmentColumns()),
        ], 201);
    }

    public function show(
        Request $request,
        SgcCustomer $sgc_customer,
        SgcCustomerAttachment $sgc_customer_attachment,
    ) {
        $this->assertAttachmentBelongsToCustomer($sgc_customer, $sgc_customer_attachment);

        abort_unless(
            $sgc_customer_attachment->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        abort_unless(
            Storage::disk("local")->exists($sgc_customer_attachment->path),
            Response::HTTP_NOT_FOUND
        );

        $headers = [];

        if( filled($sgc_customer_attachment->mime_type) ) {
            $headers["Content-Type"] = $sgc_customer_attachment->mime_type;
        }

        return response()->download(
            Storage::disk("local")->path($sgc_customer_attachment->path),
            $sgc_customer_attachment->original_name,
            $headers,
            "inline",
        );
    }

    public function update(
        UpdateCustomerAttachmentRequest $request,
        SgcCustomer $sgc_customer,
        SgcCustomerAttachment $sgc_customer_attachment,
    ) {
        $this->assertAttachmentBelongsToCustomer($sgc_customer, $sgc_customer_attachment);

        abort_unless(
            $sgc_customer_attachment->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_customer_attachment->update($request->validated());

        return response()->json([
            "attachment" => $sgc_customer_attachment->fresh()->only($this->attachmentColumns()),
        ], 200);
    }

    public function destroy(
        Request $request,
        SgcCustomer $sgc_customer,
        SgcCustomerAttachment $sgc_customer_attachment,
    ) {
        $this->assertAttachmentBelongsToCustomer($sgc_customer, $sgc_customer_attachment);

        abort_unless(
            $sgc_customer_attachment->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_customer_attachment->deleteStoredFile();
        $sgc_customer_attachment->delete();

        return response()->json([
            "message" => "Anexo removido com sucesso.",
        ], 200);
    }

    private function resolveAccessiblePivot(
        Request $request,
        SgcCustomer $sgc_customer,
        int $company_id,
    ): SgcCompanyCustomer {
        abort_if(
            $sgc_customer->isFinalConsumer(),
            Response::HTTP_FORBIDDEN,
            "O consumidor final não pode receber anexos."
        );

        $pivot = $sgc_customer->companyCustomer($company_id);

        abort_unless($pivot, Response::HTTP_NOT_FOUND);

        abort_unless(
            $pivot->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        return $pivot;
    }

    private function assertAttachmentBelongsToCustomer(
        SgcCustomer $sgc_customer,
        SgcCustomerAttachment $sgc_customer_attachment,
    ): void {
        abort_unless(
            (int) $sgc_customer_attachment->companyCustomer?->customer_id === (int) $sgc_customer->id,
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @return list<string>
     */
    private function attachmentColumns(): array
    {
        return [
            "id",
            "company_customer_id",
            "type",
            "original_name",
            "mime_type",
            "size_bytes",
            "created_at",
            "updated_at",
        ];
    }
}
