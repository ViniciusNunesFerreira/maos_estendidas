<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>T.U.M.E | Templo de Umbanda Mãos Estendidas - Guarujá</title>
    <meta name="description" content="O Templo de Umbanda Mãos Estendidas (T.U.M.E) promove cura espiritual, acolhimento e caridade em Guarujá. Sob a guiada de Pai Ton e Pai Rodrigo. Venha conhecer a paz dos Orixás.">
    <meta name="keywords" content="Umbanda Guarujá, Terreiro, Cura Espiritual, Caridade, Pai Ton, Pai Rodrigo, Mãos Estendidas, Religião, Espiritualidade">
    <meta property="og:title" content="T.U.M.E - Acolhimento e Cura Espiritual">
    <meta property="og:image" content="{{ asset('assets/images/logotipo/logo.png') }}">
    <meta name="robots" content="index, follow">

    <link rel="icon" href="{{ asset('assets/images/logotipo/logo.png') }}" type="image/x-icon">
    <!-- Additional favicon links for best practice -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logotipo/logo.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logotipo/logo.png') }}"> 

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Tipografia */
        body { font-family: 'Lato', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }
        
        /* Cores da Marca */
        .gold-text { color: #D4AF37; }
        .bg-gold { background-color: #D4AF37; }
        
        
        /* CORREÇÃO PARALLAX IPHONE (iOS Safari) */
        .hero-bg {
            position: relative;
            height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #000;
        }

        .hero-bg::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            /* Imagem estável de velas e luzes */
            background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/umbanda-energia-que-cura.jpg');
            background-size: cover;
            background-position: center;
            /* No desktop mantém fixed, no mobile usamos scroll para compatibilidade total */
            background-attachment: fixed;
            
        }

        /* Detecta se é um dispositivo touch/iOS para ajustar o parallax */
        @media (pointer: coarse) {
            .hero-bg::before {
                background-attachment: scroll;
            }
        }

        /* Botão Principal */
        .btn-primary { 
            background-color: #C8102E; 
            transition: all 0.3s ease;
        }
        .btn-primary:hover { 
            background-color: #A00C24; 
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(200, 16, 46, 0.4);
        }

        /* Animações Sutis */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up {
            animation: fadeUp 1s ease-out forwards;
        }
        
        /* Smooth Scroll */
        html { scroll-behavior: smooth; }
        
        /* Mobile Menu Transitions */
        #mobile-menu {
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        }
        #mobile-menu.open {
            max-height: 400px; /* Altura suficiente para os links */
            opacity: 1;
        }
    </style>
</head>
<body class="bg-white text-gray-800 antialiased">

    <header class="fixed w-full z-50 bg-white/95 backdrop-blur-md shadow-sm transition-all duration-300">
        <div class="container mx-auto px-6 py-3 md:py-4 flex justify-between items-center relative">
            
            <a href="#" class="flex items-center gap-3 group">
                <img src="{{ asset('assets/images/logotipo/logo.png') }}" alt="T.U.M.E" class="h-10 md:h-14 w-auto group-hover:opacity-80 transition">
                <div class="flex flex-col">
                    <span class="text-lg md:text-xl font-bold tracking-widest text-gray-900 leading-tight">MÃOS ESTENDIDAS</span>
                    <span class="text-[10px] md:text-xs text-[#D4AF37] uppercase tracking-wider hidden md:block">T.U.M.E.</span>
                </div>
            </a>

            <nav class="hidden md:flex gap-8 text-sm font-semibold uppercase tracking-wide text-gray-600 items-center">
                <a href="#sobre" class="hover:text-[#C8102E] transition relative group">
                    O Templo
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-[#C8102E] transition-all group-hover:w-full"></span>
                </a>
                <a href="#lideranca" class="hover:text-[#C8102E] transition">Liderança</a>
                <a href="#atuacao" class="hover:text-[#C8102E] transition">Obras & Cura</a>
                <a href="#contato" class="btn-primary text-white px-5 py-2 rounded-full hover:shadow-lg transition">
                    Visite-nos
                </a>
            </nav>

            <button id="menu-toggle" class="md:hidden p-2" aria-label="Abrir Menu">
                <svg id="icon-open" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                <svg id="icon-close" class="w-8 h-8 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div id="mobile-menu" class="md:hidden bg-white border-t border-gray-100 shadow-xl">
            <nav class="flex flex-col p-6 gap-4 text-center text-sm font-semibold uppercase tracking-wide text-gray-700">
                <a href="#sobre" class="py-2 hover:text-[#C8102E] border-b border-gray-50 mobile-link">O Templo</a>
                <a href="#lideranca" class="py-2 hover:text-[#C8102E] border-b border-gray-50 mobile-link">Liderança</a>
                <a href="#atuacao" class="py-2 hover:text-[#C8102E] border-b border-gray-50 mobile-link">Obras & Cura</a>
                <a href="https://api.whatsapp.com/send/?phone=5513996218127" class="btn-primary text-white py-3 rounded-lg mt-2 shadow-md">
                    Nosso WhatsApp
                </a>
            </nav>
        </div>

    </header>

    <section class="hero-bg h-screen flex items-center justify-center text-center px-4">
        <div class="max-w-4xl text-white fade-in-up pt-16">
            <div class="w-16 h-1 bg-[#D4AF37] mx-auto mb-6"></div>
            
            <p class="text-base md:text-xl font-light italic mb-4 tracking-wider text-gray-200">
                Sob a proteção dos Orixás e de Vovó Maria Conga
            </p>
            
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight drop-shadow-lg">
                Acolhimento que cura.<br>
                <span class="text-[#D4AF37]">Mãos que se estendem.</span>
            </h1>
            
            <p class="text-lg md:text-2xl mb-12 max-w-2xl mx-auto font-light text-gray-100 drop-shadow-md leading-relaxed">
                Um santuário de paz e reconexão espiritual no Guarujá. 
                Onde a caridade abraça quem precisa e a fé restaura quem crê.
            </p>
            
            <div class="flex flex-col md:flex-row gap-5 justify-center items-center">
                <a href="#contato" class="btn-primary w-full md:w-auto text-white px-10 py-4 rounded-full font-bold uppercase tracking-wider text-sm shadow-[0_0_20px_rgba(200,16,46,0.3)] hover:shadow-[0_0_30px_rgba(200,16,46,0.6)] transition-all">
                    Falar conosco
                </a>
                <a href="#sobre" class="group flex items-center gap-2 text-white hover:text-[#D4AF37] transition duration-300 font-semibold uppercase tracking-widest text-sm border-b border-transparent hover:border-[#D4AF37] pb-1">
                    Conheça Nossa Essência
                    <svg class="w-4 h-4 transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        </div>
        
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce hidden md:block">
            <svg class="w-6 h-6 text-white opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
        </div>
    </section>

    <section id="sobre" class="py-20 md:py-32 bg-white relative overflow-hidden">
        <div class="absolute top-0 right-0 opacity-[0.03] pointer-events-none transform translate-x-1/3 -translate-y-1/3">
             <svg width="600" height="600" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="#000" d="M45.7,-76.3C58.9,-69.3,69.1,-55.6,76.5,-41.2C83.9,-26.8,88.5,-11.7,85.8,2.3C83.1,16.2,73.1,29,63.1,40.3C53.1,51.6,43.1,61.4,31.4,68.2C19.7,75,6.3,78.8,-5.8,77.9C-17.8,77,-28.4,71.5,-38.7,64.2C-49,56.9,-59,47.8,-67.3,36.5C-75.6,25.2,-82.2,11.7,-81.2,-1.3C-80.2,-14.3,-71.6,-26.8,-61.6,-37.2C-51.6,-47.6,-40.2,-55.9,-28.2,-63.5C-16.2,-71.1,-3.6,-78.1,5.3,-79.8C14.2,-81.5,23.3,-78,32.5,-83.4L45.7,-76.3Z" transform="translate(100 100)" /></svg>
        </div>

        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="md:w-1/2 relative group">
                    <div class="absolute -top-4 -left-4 w-24 h-24 border-t-4 border-l-4 border-[#D4AF37] opacity-0 group-hover:opacity-100 transition duration-500"></div>
                    <img src="{{ asset('assets/images/vovo-maria-conga-guaruja.jpg') }}" alt="Altar Umbanda Paz" class="rounded-lg shadow-2xl w-full h-auto object-cover grayscale hover:grayscale-0 transition duration-700 transform group-hover:scale-[1.01]">
                    <div class="absolute -bottom-4 -right-4 w-24 h-24 border-b-4 border-r-4 border-[#D4AF37] opacity-0 group-hover:opacity-100 transition duration-500"></div>
                </div>
                <div class="md:w-1/2">
                    <h2 class="text-3xl font-bold mb-6 text-gray-900 leading-tight">A Essência do Templo <span class="gold-text">Mãos Estendidas</span></h2>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        No coração do Guarujá, o <strong>Templo de Umbanda Mãos Estendidas</strong> é mais do que um espaço físico; é um elo sagrado entre o plano material e a Aruanda.
                    </p>
                    <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                        Nossa missão transcende os rituais. Buscamos ser o porto seguro para aqueles que procuram <strong>paz interior, direcionamento e conforto</strong>. Aqui, a Umbanda é praticada com fundamento, amor e, acima de tudo, respeito ao próximo.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-4 text-gray-700 font-medium p-3 bg-gray-50 rounded-lg hover:bg-red-50 transition border-l-4 border-[#C8102E]">
                            <span class="text-[#C8102E] text-xl">✦</span> Fundamento e Tradição Ancestral
                        </li>
                        <li class="flex items-center gap-4 text-gray-700 font-medium p-3 bg-gray-50 rounded-lg hover:bg-red-50 transition border-l-4 border-[#C8102E]">
                            <span class="text-[#C8102E] text-xl">✦</span> Ambiente de Paz e Respeito
                        </li>
                        <li class="flex items-center gap-4 text-gray-700 font-medium p-3 bg-gray-50 rounded-lg hover:bg-red-50 transition border-l-4 border-[#C8102E]">
                            <span class="text-[#C8102E] text-xl">✦</span> Caridade Incondicional
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="lideranca" class="py-20 bg-[#F8F5F2]">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Guardiões da Nossa Fé</h2>
            <p class="text-gray-500 mb-12 max-w-2xl mx-auto">Guiados pela espiritualidade superior para servir à comunidade.</p>
            
            <div class="grid md:grid-cols-2 gap-8 lg:gap-12 max-w-4xl mx-auto">
                <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition duration-300 transform hover:-translate-y-2 relative overflow-hidden">
                     <div class="absolute top-0 left-0 w-full h-1 bg-[#D4AF37]"></div>
                    <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-6 overflow-hidden border-4 border-[#D4AF37] shadow-inner">
                        <img src="{{ asset('assets/images/pai-ton-ayede.jpg') }}" alt="Pai Ton Ayede" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1">Pai Ton</h3>
                    
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Com sabedoria ancestral e firmeza de propósito, Pai Ton lidera o T.U.M.E. garantindo que a lei de Umbanda seja cumprida com amor e retidão.
                    </p>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition duration-300 transform hover:-translate-y-2 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-[#D4AF37]"></div>
                    <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-6 overflow-hidden border-4 border-[#D4AF37] shadow-inner">
                        <img src="{{ asset('assets/images/pai-rodrigo.jpg') }}" alt="Pai Rodrigo - Pai Menor" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1">Pai Rodrigo</h3>
                    
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Trabalhando incansavelmente ao lado da comunidade, Pai Rodrigo é o braço forte que acolhe, orienta e assegura um atendimento especial a todos.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="atuacao" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Mãos que Curam, Mãos que Ajudam</h2>
                <p class="max-w-2xl mx-auto text-gray-500">O equilíbrio sagrado entre cuidar do espírito e amparar a matéria.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-0 shadow-2xl rounded-2xl overflow-hidden">
                <div class="bg-[#1a1a1a] text-white p-10 md:p-16 flex flex-col justify-center relative">
                    <div class="text-[#D4AF37] mb-6">
                       <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                    </div>
                    <h3 class="text-3xl font-bold mb-4 font-serif">Caridade em Ação</h3>
                    <p class="text-gray-400 mb-6 leading-relaxed">
                        Honrando o nome "Mãos Estendidas", realizamos obras sociais constantes. Entendemos que a fome física dói tanto quanto a da alma. Através de doações e ações comunitárias, levamos dignidade e esperança.
                    </p>
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-5 rounded-bl-full"></div>
                </div>
                <div class="h-64 md:h-auto bg-[url('/assets/images/natal-solidario-maos-estendidas.jpg')] bg-cover bg-center grayscale hover:grayscale-0 transition duration-700"></div>

                <div class="h-64 md:h-auto bg-[url('/assets/images/umbanda-cura.jpg')] bg-cover bg-center grayscale hover:grayscale-0 transition duration-700 order-last md:order-none"></div>
                <div class="bg-[#F8F5F2] text-gray-800 p-10 md:p-16 flex flex-col justify-center order-none md:order-last relative">
                     <div class="text-[#C8102E] mb-6">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="text-3xl font-bold mb-4 font-serif">Cura Espiritual</h3>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        Muitas enfermidades começam no espírito. Oferecemos passes e tratamentos espirituais para a restauração da saúde física e emocional. Os guias atuam na limpeza energética, trazendo vigor.
                    </p>
                     <div class="absolute top-0 left-0 w-20 h-20 bg-[#C8102E] opacity-10 rounded-br-full"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-[#111] text-white text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('')] opacity-20"></div>
        <div class="absolute top-10 left-10 w-48 h-48 bg-[#C8102E] rounded-full mix-blend-screen filter blur-[80px] opacity-20"></div>
        <div class="absolute bottom-10 right-10 w-64 h-64 bg-[#D4AF37] rounded-full mix-blend-screen filter blur-[80px] opacity-20"></div>

        <div class="container mx-auto px-6 relative z-10">
            <h2 class="text-3xl md:text-5xl font-bold mb-6 font-serif tracking-wide">Sinta a energia de Vovó Maria Conga</h2>
            <p class="text-lg md:text-xl text-gray-400 mb-10 max-w-2xl mx-auto font-light">
                Não caminhe sozinho. Nossa casa está de portas abertas para receber você e sua família com o axé que transforma.
            </p>
            <a href="https://api.whatsapp.com/send/?phone=5513996218127" target="_blank" class="inline-flex items-center gap-3 btn-primary text-white px-10 py-5 rounded-full font-bold uppercase tracking-widest text-sm shadow-xl transform transition hover:scale-105">
                <span>Fale conosco</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            </a>
        </div>
    </section>

    <footer id="contato" class="bg-white border-t border-gray-100 pt-16 pb-8">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-12 mb-12">
                <div>
                    <div class="flex items-center gap-2 mb-6 opacity-90">
                        <img src="{{ asset('assets/images/logotipo/logo.png') }}" alt="Logo Footer" class="h-14">
                        <span class="font-bold text-gray-800">T.U.M.E</span>
                    </div>
                    <p class="text-gray-500 leading-relaxed text-sm">
                        Um ponto de luz no Guarujá. Trabalhamos com a verdade, a caridade e o amor incondicional dos Orixás.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-bold mb-6 text-gray-900 uppercase tracking-widest border-l-2 border-[#C8102E] pl-3">Contato</h4>
                    <p class="text-gray-600 mb-3 flex items-start gap-2 text-sm">
                        <span>📍</span> Rua Araguaia, 97 - Perequê, Guarujá / SP
                    </p>
                    <p class="text-gray-600 mb-3 flex items-center gap-2 text-sm">
                        <span>📞</span> (13) 99705-5769
                    </p>
                    <p class="text-gray-600 text-sm flex items-center gap-2">
                        <span>✉️</span> instagram.com/maosestendidasguaruja/
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-bold mb-6 text-gray-900 uppercase tracking-widest border-l-2 border-[#C8102E] pl-3">Redes Sociais</h4>
                    <div class="flex gap-4">
                        <a href="https://www.instagram.com/maosestendidasguaruja/" target="_blank" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gradient-to-tr hover:from-yellow-400 hover:via-red-500 hover:to-purple-500 hover:text-white transition shadow-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.76-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>

                        <a href="https://api.whatsapp.com/send/?phone=5513996218127" target="_blank" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gradient-to-tr hover:from-[#25D366] hover:via-[#128C7E] hover:to-[#075E54] hover:text-white transition shadow-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-100 pt-8 text-center">
                <p class="text-xs text-gray-400">
                    &copy; 2026 Templo de Umbanda Mãos Estendidas. Desenvolvido com Axé.
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
           
            const btn = document.getElementById('menu-toggle');
            const menu = document.getElementById('mobile-menu');
            const iconOpen = document.getElementById('icon-open');
            const iconClose = document.getElementById('icon-close');
            const links = document.querySelectorAll('.mobile-link');

            function toggleMenu(e) {
                
                if (e && e.type === 'touchstart') e.preventDefault(); 
                menu.classList.toggle('open');
                // Alterna a visibilidade dos ícones
                if (menu.classList.contains('open')) {
                    iconOpen.classList.add('hidden');
                    iconClose.classList.remove('hidden');
                } else {
                    iconOpen.classList.remove('hidden');
                    iconClose.classList.add('hidden');
                }
            }

            btn.addEventListener('click', toggleMenu);
            links.forEach(link => {
                link.addEventListener('click', function() {
                    menu.classList.remove('open');
                    iconOpen.classList.remove('hidden');
                    iconClose.classList.add('hidden');
                });
            });
        });
    </script>
</body>
</html>