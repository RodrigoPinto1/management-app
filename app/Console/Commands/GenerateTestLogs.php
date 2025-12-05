<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class GenerateTestLogs extends Command
{
    protected $signature = 'logs:generate';
    protected $description = 'Gera logs de teste para a aplicação';

    public function handle()
    {
        $user = User::first();

        if (!$user) {
            $this->error('Nenhum utilizador encontrado. Cria um utilizador primeiro.');
            return 1;
        }

        $this->info('A gerar logs de teste...');

        // Logs de diferentes tipos
        $actions = [
            'Criou uma proposta',
            'Atualizou cliente',
            'Eliminou fornecedor',
            'Exportou relatório',
            'Criou evento no calendário',
            'Atualizou dados da empresa',
            'Adicionou artigo',
            'Gerou fatura',
            'Enviou email',
            'Alterou permissões',
        ];

        $ips = [
            '192.168.1.100',
            '192.168.1.101',
            '10.0.0.50',
            '172.16.0.20',
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) Firefox/121.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
        ];

        for ($i = 0; $i < 25; $i++) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => $ips[array_rand($ips)],
                    'user_agent' => $userAgents[array_rand($userAgents)],
                    'device' => $this->getDeviceName($userAgents[array_rand($userAgents)]),
                ])
                ->log($actions[array_rand($actions)]);

            // Espaça os logs ao longo do tempo
            if ($i % 5 == 0) {
                sleep(1);
            }
        }

        $this->info('✓ 25 logs de teste criados com sucesso!');
        $this->info('Acede a /logs para ver os resultados.');

        return 0;
    }

    private function getDeviceName(string $userAgent): string
    {
        if (str_contains($userAgent, 'iPhone')) {
            return 'iPhone';
        } elseif (str_contains($userAgent, 'Macintosh')) {
            return 'macOS';
        } elseif (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        } elseif (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }
        
        return 'Unknown';
    }
}
