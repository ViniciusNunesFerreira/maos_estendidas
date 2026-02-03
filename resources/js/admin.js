import Chart from 'chart.js/auto'
import focus from '@alpinejs/focus' 

window.Chart = Chart

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus);
});


// --- Toast Notifications ---
window.toast = function(message, type = 'success') {
    const container = document.getElementById('toast-container')
    if (!container) return
    
    const colors = {
        success: 'bg-success-500',
        error: 'bg-danger-500',
        warning: 'bg-warning-500',
        info: 'bg-primary-500',
    }
    
    const toast = document.createElement('div')
    toast.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg mb-4 animate-slide-up`
    toast.textContent = message
    
    container.appendChild(toast)
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'transition-opacity', 'duration-300')
        setTimeout(() => toast.remove(), 300)
    }, 3000)
}

document.addEventListener('livewire:init', () => {
   
           
    // Listener para notificações Flash
    Livewire.on('flash', (event) => {
        // Garante que pegamos os dados corretamente (Livewire 3 costuma enviar como array)
        const data = Array.isArray(event) ? event[0] : event;
        window.toast(data.message, data.type || 'success');
    });

    // Listener para fechar o modal
    window.addEventListener('close-modal', event => {
        const modalName = event.detail;
        window.dispatchEvent(new CustomEvent('close-modal-' + modalName)); 
        window.dispatchEvent(new CustomEvent('close-modal-window', { detail: modalName }));
    });

    Livewire.on('refresh-page', () => {
        setTimeout(() => window.location.reload(), 1000);
    });
    
    // Listener para refresh após atualização de componente filho
    Livewire.on('filhoUpdated', () => {
        setTimeout(() => {
            window.location.reload();
        }, 500); 
    });
});



// --- Funções de Máscara ---
window.maskCurrency = function(input) {
    // 1. Remove tudo que não é dígito
    let value = input.value.replace(/\D/g, '');
    
    if (value === '') {
        if (input.value !== '') {
            input.value = '';
            input.dispatchEvent(new Event('input'));
        }
        return;
    }

    // 2. Formata o valor
    let amount = (parseInt(value) / 100);
    let formatted = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 2
    }).format(amount);

    // 3. SEGURANÇA: Só atualiza e dispara o evento se o valor mudou de fato
    // Isso quebra o loop infinito
    if (input.value !== formatted) {
        input.value = formatted;
        
        // Dispara o evento para o Livewire/Alpine capturar a mudança
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

window.maskCPF = function(input) {
    let value = input.value.replace(/\D/g, '')
    value = value.replace(/(\d{3})(\d)/, '$1.$2')
    value = value.replace(/(\d{3})(\d)/, '$1.$2')
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2')
    input.value = value
}

window.maskPhone = function(input) {
    let value = input.value.replace(/\D/g, '')
    value = value.replace(/^(\d{2})(\d)/g, '($1) $2')
    value = value.replace(/(\d)(\d{4})$/, '$1-$2')
    input.value = value
}

window.maskCEP = function(input) {
    let value = input.value.replace(/\D/g, '')
    value = value.replace(/^(\d{5})(\d)/, '$1-$2')
    input.value = value
}