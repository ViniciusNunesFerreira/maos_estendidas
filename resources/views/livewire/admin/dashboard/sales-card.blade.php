<x-cards.metric-card 
    title="Vendas de Hoje" 
    :value="'R$ ' . number_format($todaySales, 2, ',', '.')" 
    icon="currency-dollar"
    variant="success"
    :trend="$trend"
    :trend-value="($trend === 'up' ? '+' : '-') . $trendPercentage . '%'"
/>