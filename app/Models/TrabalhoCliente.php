<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrabalhoCliente extends Model
{
    use SoftDeletes;

    protected $table = 'trabalho_cliente';

    protected $fillable = ['trabalho_id', 'cliente_id', 'token', 'expira_em', 'status_link', 'visualizado_em', 'total_visualizacoes'];

    protected $casts = [
        'expira_em'    => 'datetime',
        'visualizado_em' => 'datetime',
    ];

    public function foiVisualizado(): bool
    {
        return $this->visualizado_em !== null;
    }

    public function estaExpirado(): bool
    {
        if (is_null($this->expira_em)) return false;
        return $this->expira_em->isPast();
    }

    public function marcarComoExpirado(): void
    {
        if ($this->status_link !== 'expirado') {
            $this->update(['status_link' => 'expirado']);
        }
    }

    public function diasRestantes(): ?int
    {
        if (is_null($this->expira_em)) return null;
        if ($this->expira_em->isPast()) return 0;
        return (int) round(now()->diffInDays($this->expira_em));
    }

    public function tempoRestanteFormatado(): string
    {
        if (is_null($this->expira_em)) return 'Sem prazo';
        if ($this->expira_em->isPast()) return 'Expirado';

        $dias = $this->diasRestantes();
        $horas = (int) now()->diffInHours($this->expira_em) % 24;

        if ($dias > 1) return "{$dias} dias restantes";
        if ($dias === 1) return "1 dia e {$horas}h restantes";
        if ($dias === 0) {
            $horas = (int) now()->diffInHours($this->expira_em);
            if ($horas > 0) return "{$horas} horas restantes";
            $minutos = (int) now()->diffInMinutes($this->expira_em);
            return "{$minutos} minutos restantes";
        }
        return 'Expirado';
    }

    public function trabalho()
    {
        return $this->belongsTo(Trabalho::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
