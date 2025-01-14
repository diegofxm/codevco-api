<?php

namespace App\Services\Pdf;

use App\Models\Invoicing\CreditNote;

class CreditNotePdfService extends BasePdfService
{
    protected function getDirectory(): string
    {
        return "credit-notes/{$this->model->id}/pdf";
    }

    protected function getViewName(): string
    {
        return 'pdfs.credit-note';
    }

    protected function loadRelations(): void
    {
        $this->model->load([
            'invoice.company',
            'invoice.customer',
            'invoice.branch',
            'invoice.currency',
            'lines.product'
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'creditNote' => $this->model,
            'company' => $this->model->invoice->company,
            'customer' => $this->model->invoice->customer,
            'branch' => $this->model->invoice->branch,
            'lines' => $this->model->lines
        ];
    }

    public static function make(CreditNote $creditNote)
    {
        return new static($creditNote);
    }
}
