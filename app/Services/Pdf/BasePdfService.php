<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

abstract class BasePdfService
{
    protected $model;
    protected $directory;
    protected $prefix;
    protected $number;

    public function __construct($model)
    {
        $this->model = $model;
        $this->directory = $this->getDirectory();
        $this->prefix = $model->prefix;
        $this->number = $model->number;
    }

    abstract protected function getDirectory(): string;
    abstract protected function getViewName(): string;
    abstract protected function loadRelations(): void;
    abstract protected function getViewData(): array;

    public function generate()
    {
        try {
            // Load necessary relations
            $this->loadRelations();

            // Generate PDF
            $pdf = PDF::loadView($this->getViewName(), $this->getViewData());

            // Create directory if it doesn't exist
            if (!Storage::exists($this->directory)) {
                Storage::makeDirectory($this->directory);
            }

            // Save PDF
            $filename = "{$this->prefix}-{$this->number}.pdf";
            $path = "{$this->directory}/{$filename}";
            Storage::put($path, $pdf->output());

            return [
                'success' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'filename' => $path,
                    'url' => "/storage/{$path}"
                ]
            ];

        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return [
                'success' => false,
                'message' => 'Error generating PDF',
                'error' => $e->getMessage()
            ];
        }
    }
}
