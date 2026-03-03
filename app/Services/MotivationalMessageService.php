<?php

namespace App\Services;

use App\Models\Filho;
use App\Models\MotivationalLog;
use App\Jobs\ProcessMotivationalMessage;
use Illuminate\Support\Collection;

class MotivationalMessageService
{
    protected array $frases = [
        "Como está o coração? Espero que em paz. Passando pra te lembrar que você não está sozinho viu, somos uma familia e você pode contar com a gente sempre.",
        "Passando para deixar uma mensagem de carinho, e reforçar que nossa casa está de portas abertas pra te receber sempre. Qualquer dúvida, estamos aqui!",
        "Oi, meu filho! Passando para te dizer que nenhum obstáculo é maior que a sua fé. Você não está sozinho, conte sempre conosco. 🙏",
        "Que o dia de hoje seja de vitórias. Lembre-se: quem tem guia, nunca caminha no escuro. Muita luz e muito Axé na sua caminhada!",
        "Passei só para deixar um abraço e dizer que estou sempre aqui para te ajudar. O que precisar conta comigo, tá!",
        "Que a proteção dos guias esteja com você em cada passo hoje. 🙏",
        "Que a luz dos Orixás ilumine sua caminhada! ✨",
        "Que a alegria de viver seja sua maior oferenda no dia de hoje. Lembre-se de que você não está só; conte conosco sempre que precisar.",
        "Olhe para o céu e agradeça. A gratidão abre as portas que a reclamação fecha. Lembre-se sempre: nossas palavras têm força, e transformar o nosso dia em bênção é uma escolha que está em nossas mãos.",
        "Erga os olhos para o céu e agradeça. A gratidão escancara caminhos que a reclamação bloqueia.",
        "Desejo que hoje a sua maior oferta seja um coração leve e cheio de alegria. E lembre-se: você nunca caminha sozinho(a); pode contar conosco em qualquer momento.",
        "Oii, essa é a mensagem que tenho pra hoje: Que as estradas se abram e que o movimento da vida te leve para o melhor lugar. Abraço filho(a), se precisar estou por aqui tá.",
        "Que a proteção dos nossos guardiões seja o seu escudo contra qualquer negatividade.",
        "Que o dia de hoje seja de colheita farta de amor e compreensão na sua casa. 🌻",
        "Oi, meu filho! Que a sua fé seja o combustível para realizar todos os seus sonhos.",
        "Oi amor! Passando para te lembrar que você é amado e protegido pela espiritualidade.",
        "A disciplina na fé é o caminho para a vitória. Continue firme no seu propósito.",
        "Meu filho, Pode parecer apenas uma mensagem robótica, mas na verdade é o sinal que a espiritualidade sorteou você para te falar: Quem tem guia, nunca caminha no escuro! Você não está sozinho, conte sempre conosco.",   
        "Que as águas sagradas levem para longe toda a aflição",
        "Oi, meu filho! Passando para dizer que quem tem fé nunca caminha sozinho. Qualquer coisa me chama, tá...",
        "Oi, meu filho! Você pode até pensar que é besteira, né? Mas essa mensagem não chegou até você por acaso; na espiritualidade, tudo tem um propósito. Se estiver precisando de algo, não se sinta sozinho(a). Somos uma família, e você pode contar comigo sempre.",
        "Vá com confiança, meu filho. A espiritualidade caminha com você, sustenta seus passos e fortalece sua alma.",
        "Oi meu filho, como você está? Espero que esteja bem. Se hoje parecer difícil, lembre-se: nenhuma tempestade dura para sempre. Conte sempre comigo, você não está só!",
        "Oi amor, tudo bem? Passando para celebrar contigo o seu acordar... O hoje é um novo começo. A espiritualidade sempre oferece outra chance, aproveite seu dia.",
        "Meu filho, cuide dos seus pensamentos; eles são sementes que florescem na sua realidade. Hoje, escolha falar palavras de bênção. Sua boca tem poder de construir caminhos.",
        "Confie no tempo da espiritualidade. O que demora a chegar vem mais forte e mais firme.",
        "Nenhuma energia negativa é maior do que a proteção que te acompanha. Confie. Mantenha-se firme com seus guias e orixás, eles entendem até o que você não consegue dizer.",
        "Vá em paz e com confiança, meu filho. Onde você pisar, que floresçam proteção e vitória.",
        "Meu filho, antes de qualquer decisão hoje, silencia a mente e escuta o coração; a espiritualidade fala baixinho, mas fala certo. Se precisar estamos sempre por aqui!",
        "Bom dia meu filho! Se algo te feriu, entregue na mão dos guias e siga leve; peso demais atrasa a caminhada. Esta é a mensagem que tenho: Se o mundo te testar, responda com equilíbrio. Quem tem axé não se descontrola à toa.",
        "Meu filho, não duvide da sua força; você já venceu batalhas que ninguém viu.",
        "Quando pensar em desistir, recorde-se de quantas vezes a espiritualidade já te sustentou.",
        "Bom dia! Hoje é dia de recomeço, seja grato até pelo que não deu certo; às vezes foi só livramento. Que seu dia seja iluminado e seus caminhos sejam cruzados apenas por pessoas de luz e intenções verdadeiras. Axé!!",
        "Nenhuma palavra negativa tem força quando sua mente está firmada no bem.",
        "Que seu coração esteja protegido contra inveja e fortalecido pela fé. Nenhuma palavra negativa tem força quando sua mente está firmada no bem. Axé!!",
        "Oi meu filho, tudo bem? Hoje meu conselho é: Antes de falar, vigie suas palavras. A boca do médium precisa ser limpa e consciente.",
        "Axé meu filho! Já fez suas preces hoje? Faça ao menos uma prece diária aos seus Orixás e guias. Conexão se fortalece com constância.",
        "Bom dia meu filho. Acenda sua vela com fé e propósito, nunca por obrigação, sempre por gratidão. A luz representa sua intenção, tudo que você sente quando firma sua vela é sentido pela espiritualidade. Por isso, pratique firmar um compormisso com seus guias por amor",
        "Oi amor tudo bem? Hoje o que tenho a te dizer é: honre seu axé todos os dias com caráter, respeito e amor - é isso que sustenta qualquer fundamento dentro da nossa casa.",
        "Uma mensagem para todos: Sejamos exemplo fora do terreiro. A Umbanda não se vive só de branco, mas de atitude. Lembre-se: disciplina é tão importante quanto mediunidade.",
        "Um conselho a todos: Cuide da sua vibração; médium precisa manter pensamento elevado para não baixar sua frequência.",
        "Aqui mais um conselho a todos: Viva a Umbanda com simplicidade, respeito e amor; é assim que o axé se mantém firme todos os dias",
        ];

    public function sendDailyBlessings(int $limit = 10)
    {
        // Pega filhos ativos sorteados
        $filhos = Filho::active()->inRandomOrder()->limit($limit)->get();

        foreach ($filhos as $filho) {
            $mensagemBase = $this->getUniqueMessageForFilho($filho);

            if ($mensagemBase) {
                // Personalização do Nome
                $nome = explode(' ', $filho->full_name)[0];
                $mensagemFinal = str_replace('Meu filho', "$nome", $mensagemBase);
                $mensagemFinal = str_replace('meu filho', "$nome", $mensagemFinal);

                // Despacha o Job com um delay aleatório longo (espalha durante o dia)
                // Isso evita que a Meta veja um pico de conexões saindo do seu IP
                ProcessMotivationalMessage::dispatch($filho, $mensagemFinal)
                    ->delay(now()->addMinutes(rand(2, 480))); 
            }
        }
    }

    /**
     * Garante que o filho não receba a mesma frase em um período de 30 dias
     */
    private function getUniqueMessageForFilho(Filho $filho): ?string
    {
            // 1. REGRA DE OURO: Verifica se o filho já recebeu QUALQUER mensagem hoje
            $jaRecebeuHoje = MotivationalLog::where('filho_id', $filho->id)
                ->whereDate('created_at', now()->today())
                ->exists();

            if ($jaRecebeuHoje) {
                return null; // Encerra aqui, pois ele já foi abençoado hoje
            }

            // 2. Lógica de Variabilidade (não repetir a mesma frase em 30 dias)
            $colecao = collect($this->frases)->shuffle();

            foreach ($colecao as $frase) {
                $hash = md5($frase);

                $jaEnviadaRecentemente = MotivationalLog::where('filho_id', $filho->id)
                    ->where('message_hash', $hash)
                    ->where('created_at', '>', now()->subDays(30))
                    ->exists();

                if (!$jaEnviadaRecentemente) {
                    // Registra o envio para travar os próximos disparos do dia
                    MotivationalLog::create([
                        'filho_id' => $filho->id,
                        'message_hash' => $hash
                    ]);
                    return $frase;
                }
            }

            return null;
    }
}
