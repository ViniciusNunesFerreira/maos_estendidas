<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fatura {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .amount { font-size: 32px; color: #D4AF37; font-weight: bold; }
        .items table { width: 100%; border-collapse: collapse; }
        .items td, .items th { padding: 10px; border-bottom: 1px solid #ddd; }
        .qrcode { text-align: center; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Mãos Estendidas - Fatura</h1>
        <p><strong>Nº {{ $invoice->invoice_number }}</strong></p>
    </div>
    
    <div class="info">
        <p><strong>Cliente:</strong> {{ $invoice->filho->fullname }}</p>
        <p><strong>CPF:</strong> {{ $invoice->filho->cpf }}</p>
        <p><strong>Período:</strong> {{ $invoice->period_start->format('d/m/Y') }} a {{ $invoice->period_end->format('d/m/Y') }}</p>
        <p><strong>Vencimento:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
    </div>
    
    <div class="amount-box">
        <p class="amount">R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</p>
    </div>
    
    <div class="items">
        <h3>Itens</h3>
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Qtd</th>
                    <th>Valor Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if(!empty($invoice->fiscal) && isset($invoice->fiscal['sat_qrcode']))
    <div class="qrcode">
        <img src="data:image/png;base64,{{ $invoice->fiscal['sat_qrcode'] }}" width="200">
        <p><small>Escaneie para pagar via PIX</small></p>
    </div>
    @endif
</body>
</html>