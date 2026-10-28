import './bootstrap';

// Alpine.js ya viene incluido con Livewire: no se inicia aquí para no cargarlo dos veces.

// Si la sesión expiró (la caja quedó abierta mucho tiempo), avisa en español
// y recarga: la página llevará al inicio de sesión.
const avisarSesionExpirada = () => {
    let avisado = false;
    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status !== 419) return;
            preventDefault();
            if (avisado) return;
            avisado = true;
            alert('Tu sesión expiró por seguridad. Vuelve a entrar para seguir; lo que ya guardaste no se perdió.');
            window.location.reload();
        });
    });
};

if (window.Livewire) avisarSesionExpirada();
else document.addEventListener('livewire:init', avisarSesionExpirada);
