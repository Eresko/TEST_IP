<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ReportStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Models\NotificationReport
 */
class NotificationReportResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'author_id'   => $this->author_id,
            'status'      => $this->status->value,
            'status_label'=> $this->status->label(),
            'period' => [
                'from' => $this->period_from->toDateTimeString(),
                'to'   => $this->period_to->toDateTimeString(),
            ],
            'download_url' => $this->when(
                $this->status === ReportStatus::COMPLETED,
                fn() => URL::signedRoute('reports.download', ['id' => $this->id])
            ),
            'error'       => $this->when($this->status === ReportStatus::FAILED, $this->error_message),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
