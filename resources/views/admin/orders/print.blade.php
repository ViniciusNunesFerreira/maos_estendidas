<!DOCTYPE html>
<html>
<head>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            width: 72mm; padding: 5px; font-size: 12px; line-height: 1.2;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        .justify { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed;}
        .text-right { text-align: right; }
        .spacer { height: 20px; }
    </style>
</head>
<body onload="window.print(); window.onafterprint = function(){ window.close(); }">
    <div class="center">
        <span class="bold">T.U.M.E MAOS ESTENDIDAS</span><br>
        CNPJ: 23.799.859/0001-08<br>
        R. ARAGUAIA, 97 - GUARUJA/SP<br>
        <span class="bold">DOCUMENTO NAO FISCAL</span><br>
        <span>{{ $order->created_at->format('d/m/Y') }}</span>
    </div>
    <div class="line"></div>
    <div class="justify">
        <div>N. PEDIDO: {{ strtoupper($order->order_number) }}</div>
    </div>
    
    <div>CLIENTE: <span class="bold">{{ strtoupper($order->filho->full_name ?? $order->guest_name ?? 'CONSUMIDOR FINAL') }} </span> </div>
    @if($order->filho?->cpf) <div>CPF/CNPJ: {{ $order->filho->cpf }}</div> @endif
    <div class="line"></div>
    
    <table>
        <thead>
            <tr>
                <th align="left">DESC</th>
                <th align="right">QTD</th>
                <th align="right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ substr(strtoupper($item->product_name), 0, 18) }}</td>
                <td align="right">{{ $item->quantity }}</td>
                <td align="right">{{ number_format($item->total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="line"></div>
    <div class="justify bold">
        <span>TOTAL GERAL:</span>
        <span>R$ {{ number_format($order->total, 2, ',', '.') }}</span>
    </div>
    <div class="justify " style="font-size: 13px;">
        <span>FORMA PAGTO:</span>
        <span>
            @php
                $methodLabels = [
                    'pix' => 'PIX',
                    'credito' => 'C. CREDITO',
                    'debito' => 'C. DEBITO',
                    'dinheiro' => 'DINHEIRO',
                    'carteira' => 'CARTEIRA',
                ];
                $method = $order->payment_method_chosen ?? 'carteira';
            @endphp
            {{ $methodLabels[$method] ?? strtoupper($method) }}
        </span>
    </div>
    <div class="line"></div>
    <div class="center">
        Obrigado pela colaboracao!<br>
        Operador: {{ $order->createdBy->name ?? 'SISTEMA' }}
    </div>
    <div class="spacer"></div>
    <div class="center">.</div>
</body>
</html>