<?php

namespace App\Services\Pdf;

use App\Models\Invoicing\DebitNote;

class DebitNotePdfService extends BasePdfService
{
    protected function getDirectory(): string
    {
        return "debit-notes/{$this->model->id}/pdf";
    }

    protected function getViewName(): string
    {
        return 'pdfs.debit-note';
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
            'debitNote' => $this->model,
            'company' => $this->model->invoice->company,
            'customer' => $this->model->invoice->customer,
            'branch' => $this->model->invoice->branch,
            'lines' => $this->model->lines
        ];
    }

    public static function make(DebitNote $debitNote)
    {
        return new static($debitNote);
    }
}
