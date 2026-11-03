// Contagem regressiva que segue o relógio do servidor (na demonstração, ele pode estar adiantado).
document.addEventListener('alpine:init', () => {
    window.Alpine.data('contagem', (ate, agoraNoServidor) => ({
        resta: 0,
        desvio: agoraNoServidor - Date.now(),
        init() {
            this.tick();
            this.relogio = setInterval(() => this.tick(), 1000);
        },
        destroy() {
            clearInterval(this.relogio);
        },
        tick() {
            this.resta = Math.max(0, ate - (Date.now() + this.desvio));
        },
        get texto() {
            const s = Math.floor(this.resta / 1000);
            const h = Math.floor(s / 3600);
            const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
            const seg = String(s % 60).padStart(2, '0');
            return h ? `${h}:${m}:${seg}` : `${m}:${seg}`;
        },
    }));
});

// QR do Pix desenhado no navegador a partir do "copia e cola".
import { renderSVG } from 'uqr';

window.qrDoPix = (texto) => renderSVG(texto, { ecc: 'M', border: 2, whiteColor: '#fffdfd', blackColor: '#2a1838' });
