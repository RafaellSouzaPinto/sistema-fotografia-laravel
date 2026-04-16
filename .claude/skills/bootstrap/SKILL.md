# Bootstrap 5.3.2 + CSS Custom

Via CDN (sem npm/Vite):
- bootstrap@5.3.2/dist/css/bootstrap.min.css
- bootstrap-icons@1.11.3/font/bootstrap-icons.min.css
- Google Fonts: Playfair Display (títulos) + Inter (corpo)
- Custom: public/css/custom.css

## Paleta (CSS variables em :root)
--rosa-principal: #c27a8e | --rosa-hover: #a85d73
--rosa-claro: #fce4ec | --rosa-bg: #fdf0f2 | --rosa-borda: #f0d4da
--texto-escuro: #4a2c3d | --texto-secundario: #8c6b7d
--verde-badge: #27ae60 | --verde-claro: #d4f5e9
--vermelho: #c0392b | --vermelho-claro: #fdecea
--header-border: #d4a0ad

## Regras visuais
- Mobile-first, responsivo (1 col mobile, 2 tablet, 3 desktop)
- NUNCA menu hamburger
- Botões SEMPRE com ícone + texto
- Inputs: font-size 16px mínimo (evitar zoom iOS)
- Touch targets: min-height 44px
- Sem dark mode, sem gradientes, sem neon
- border-radius: 8px padrão, 12px cards
- Playfair Display para títulos, Inter para corpo
