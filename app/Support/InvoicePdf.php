<?php

namespace App\Support;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

/**
 * Renders invoices to PDF.
 */
class InvoicePdf
{
    /**
     * Render the invoice as a PDF document.
     */
    public function render(Invoice $invoice): PDF
    {
        $invoice->loadMissing(['customer', 'items', 'payments']);

        return PdfFacade::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'from' => (array) config('invoicing.from'),
        ])->setPaper('a4');
    }

    /**
     * Download the invoice as a PDF file.
     */
    public function download(Invoice $invoice): Response
    {
        return $this->render($invoice)->download($this->filename($invoice));
    }

    /**
     * Get the file name used for the invoice's PDF.
     */
    public function filename(Invoice $invoice): string
    {
        return sprintf('%s.pdf', str_replace(['/', '\\', ' '], '-', $invoice->number));
    }
}
