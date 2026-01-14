<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PDFService
{
    public function generateInvoicePDF($invoice)
    {
       $invoice->load(['items', 'filho']);

        return Pdf::loadView('pdfs.invoice', ['invoice' => $invoice])
            ->setPaper('a4')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0);
    }

    public function renderStream($invoice, $fileName)
    {
        $data = ['invoice' => $invoice];
        
        return Pdf::loadView('pdfs.invoice', $data)
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ])
            ->stream($fileName); // O segredo está no ->stream()
    }
}